<?php

namespace App\Services\Imports;

use App\Models\Brand;
use App\Models\Category;
use App\Models\ImportBatch;
use App\Models\Product;
use App\Models\SourceProduct;
use App\Models\User;
use App\Services\SkuGenerator;
use App\Services\SlugService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class CatalogueImportService
{
    public function __construct(
        private readonly SafeUrlFetcher $fetcher,
        private readonly ProductPageParser $parser,
        private readonly SkuGenerator $skus,
    ) {}

    public function discoverSitemap(?int $createdBy = null, int $limit = 1000, ?string $sourceBase = null): ImportBatch
    {
        $base = rtrim($sourceBase ?: (string) config('tajira.imports.source_base'), '/');
        $this->assertRobotsAllowed($base);

        $sitemapUrl = $base.'/product-sitemap.xml';
        $response = $this->fetcher->fetch($sitemapUrl);
        preg_match_all('#<loc>\s*(https?://[^<]+/product/[^<]+)\s*</loc>#i', $response['body'], $matches);
        $urls = array_values(array_unique(array_map([$this, 'normaliseUrl'], $matches[1] ?? [])));

        if ($urls === []) {
            throw new \RuntimeException('No product URLs were found in the source sitemap.');
        }

        $urls = array_slice($urls, 0, max(1, $limit));

        return DB::transaction(function () use ($urls, $base, $createdBy) {
            $batch = ImportBatch::query()->create([
                'reference' => 'DISC-'.now()->format('Ymd-His').'-'.Str::upper(Str::random(4)),
                'type' => 'discovery',
                'source' => $base,
                'status' => 'running',
                'total_rows' => count($urls),
                'params' => ['sitemap' => $base.'/product-sitemap.xml', 'discovered_at' => now()->toIso8601String()],
                'started_at' => now(),
                'last_checkpoint_at' => now(),
                'created_by' => $createdBy,
            ]);

            $created = 0;

            foreach ($urls as $url) {
                $source = SourceProduct::query()->firstOrCreate(
                    ['source_url' => $url],
                    [
                        'import_batch_id' => $batch->id,
                        'source_site' => parse_url($base, PHP_URL_HOST),
                        'discovered_at' => now(),
                        'status' => 'discovered',
                    ]
                );

                if ($source->wasRecentlyCreated) {
                    $created++;
                }
            }

            $batch->update([
                'total_rows' => $created,
                'status' => 'paused',
                'notes' => $created.' new source URL(s) staged. Fetching has not started. Competitor prices and stock are reference observations only.',
            ]);

            return $batch->fresh();
        });
    }

    /**
     * @return array{fetched:int,failed:int,duplicates:int,remaining:int}
     */
    public function fetchBatch(ImportBatch $batch, int $limit = 50): array
    {
        $limit = max(1, min(500, $limit));
        $batch->update(['status' => 'running', 'started_at' => $batch->started_at ?? now()]);

        $sources = SourceProduct::query()
            ->where('import_batch_id', $batch->id)
            ->whereIn('status', ['discovered', 'failed'])
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $fetched = 0;
        $failed = 0;
        $duplicates = 0;

        foreach ($sources as $source) {
            try {
                $this->fetchOne($source);
                $fetched++;

                if ($source->fresh()->status === 'duplicate') {
                    $duplicates++;
                }
            } catch (Throwable $exception) {
                $source->update([
                    'status' => 'failed',
                    'fetched_at' => now(),
                    'errors' => [['message' => $exception->getMessage(), 'at' => now()->toIso8601String()]],
                ]);
                $failed++;
            }

            $batch->increment('processed_rows');
            $batch->update(['last_checkpoint_at' => now()]);
        }

        $remaining = SourceProduct::query()
            ->where('import_batch_id', $batch->id)
            ->whereIn('status', ['discovered', 'failed'])
            ->count();

        $counts = SourceProduct::query()
            ->where('import_batch_id', $batch->id)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $batch->update([
            'status' => $remaining === 0 ? 'completed' : 'paused',
            'failed_rows' => (int) ($counts['failed'] ?? 0),
            'imported_rows' => (int) ($counts['imported'] ?? 0),
            'duplicate_rows' => (int) ($counts['duplicate'] ?? 0),
            'finished_at' => $remaining === 0 ? now() : null,
        ]);

        return ['fetched' => $fetched, 'failed' => $failed, 'duplicates' => $duplicates, 'remaining' => $remaining];
    }

    public function fetchOne(SourceProduct $source): SourceProduct
    {
        $response = $this->fetcher->fetch($source->source_url);

        if ($response['url'] !== $source->source_url) {
            $source->update(['source_url' => $this->normaliseUrl($response['url'])]);
        }

        $parsed = $this->parser->parse($response['body'], $source->source_url);
        $errors = $parsed['errors'];
        unset($parsed['source_url'], $parsed['errors'], $parsed['content_hash']);

        $status = $errors === [] ? 'needs_review' : 'failed';
        $proposedSku = $this->proposedSku($parsed['raw_brand'] ?? null, $parsed['raw_model'] ?? null, $parsed['raw_name'] ?? null);

        $source->update(array_merge($parsed, [
            'fetched_at' => now(),
            'status' => $status,
            'proposed_sku' => $proposedSku,
            'errors' => $errors !== [] ? array_map(fn ($message) => ['message' => $message], $errors) : null,
            'content_hash' => hash('sha256', json_encode($parsed, JSON_UNESCAPED_UNICODE)),
        ]));

        $this->detectDuplicate($source->fresh());

        return $source->fresh();
    }

    public function approve(SourceProduct $source, User $user, ?string $sku = null, ?int $brandId = null, ?int $categoryId = null): Product
    {
        if ($source->product_id) {
            throw ValidationException::withMessages(['source' => 'This source record already has a product.']);
        }

        if (! in_array($source->status, ['needs_review'], true)) {
            throw ValidationException::withMessages([
                'source' => 'Only a parsed record awaiting review can be approved. Duplicates, failed rows and rejected records must be resolved first.',
            ]);
        }

        if (blank($source->raw_name)) {
            throw ValidationException::withMessages(['source' => 'The source record has no product name.']);
        }

        $brand = $brandId ? Brand::query()->find($brandId) : $this->matchBrand($source->raw_brand);

        if (! $brand && filled($source->raw_brand)) {
            throw ValidationException::withMessages([
                'brand' => 'Create the brand “'.$source->raw_brand.'” first, then approve this staged record.',
            ]);
        }

        $product = DB::transaction(function () use ($source, $user, $sku, $brand, $categoryId) {
            $sku = $sku ?: $source->proposed_sku ?: $this->skus->forProduct($brand?->name, $source->raw_model, $source->raw_name);

            if (Product::query()->where('sku', $sku)->exists()) {
                $sku = $this->skus->forProduct($brand?->name, $source->raw_model, $source->raw_name);
            }

            $product = Product::query()->create([
                'name' => Str::limit($source->raw_name, 220, ''),
                'slug' => app(SlugService::class)->unique($source->raw_name, Product::class),
                'sku' => $sku,
                'brand_id' => $brand?->id,
                'model' => $source->raw_model,
                'mpn' => $source->raw_mpn,
                'gtin' => $source->raw_gtin,
                'product_type' => 'simple',
                'status' => Product::STATUS_DRAFT,
                'price_minor' => null,
                'price_on_request' => false,
                'stock_quantity' => 0,
                'is_indexable' => true,
                'source_notes' => 'Staged from '.$source->source_url.' discovered '.$source->discovered_at?->toDateString().'. Competitor price/stock observations are not Tajira commercial data. Verify specifications from manufacturer documentation before publication.',
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            $categories = $categoryId
                ? array_values(array_filter([Category::query()->find($categoryId)]))
                : $this->matchCategories($source->raw_categories ?? []);

            if ($categories !== []) {
                $product->forceFill(['primary_category_id' => $categories[0]->id])->save();
                $payload = [];

                foreach ($categories as $index => $category) {
                    $payload[$category->id] = ['is_primary' => $index === 0, 'position' => $index];
                }

                $product->categories()->sync($payload);
            }

            $source->update([
                'status' => 'imported',
                'disposition' => 'created_draft',
                'product_id' => $product->id,
                'verified_at' => null,
                'published_at' => null,
            ]);

            return $product;
        });

        return $product;
    }

    public function reject(SourceProduct $source, ?string $note = null): void
    {
        $source->update([
            'status' => 'rejected',
            'disposition' => 'rejected',
            'verification_notes' => $note,
        ]);
    }

    public function retry(SourceProduct $source): void
    {
        $source->update(['status' => 'discovered', 'errors' => null]);
    }

    public function metrics(): array
    {
        $counts = SourceProduct::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $total = (int) $counts->sum();
        $imported = (int) ($counts['imported'] ?? 0);
        $duplicates = (int) ($counts['duplicate'] ?? 0);
        $failed = (int) ($counts['failed'] ?? 0);
        $pending = (int) ($counts['needs_review'] ?? 0) + (int) ($counts['discovered'] ?? 0);
        $rejected = (int) ($counts['rejected'] ?? 0);
        $pendingVerification = SourceProduct::query()->whereNotNull('product_id')->whereNull('verified_at')->count();
        $unresolved = SourceProduct::query()->whereIn('status', ['discovered', 'failed'])->count();

        $brandCoverage = SourceProduct::query()
            ->select('raw_brand', DB::raw('COUNT(*) as discovered'), DB::raw("SUM(CASE WHEN status = 'imported' THEN 1 ELSE 0 END) as imported"))
            ->whereNotNull('raw_brand')
            ->groupBy('raw_brand')
            ->orderByDesc('discovered')
            ->limit(40)
            ->get();

        $categoryCoverage = [];
        $rows = SourceProduct::query()->select('raw_categories', 'status')->get();

        foreach ($rows as $row) {
            foreach ((array) ($row->raw_categories ?? []) as $category) {
                $category = trim((string) $category);

                if ($category === '') {
                    continue;
                }

                $categoryCoverage[$category] ??= ['discovered' => 0, 'imported' => 0];
                $categoryCoverage[$category]['discovered']++;

                if ($row->status === 'imported') {
                    $categoryCoverage[$category]['imported']++;
                }
            }
        }

        arsort($categoryCoverage);

        return [
            'total_discovered' => $total,
            'imported' => $imported,
            'duplicates' => $duplicates,
            'pending_verification' => $pendingVerification,
            'blocked' => $rejected,
            'failed' => $failed,
            'unresolved' => $unresolved,
            'pending_review' => $pending,
            'rejected' => $rejected,
            'status_counts' => $counts->all(),
            'brand_coverage' => $brandCoverage,
            'category_coverage' => array_slice($categoryCoverage, 0, 40, true),
            'product_total' => Product::query()->count(),
            'product_published' => Product::query()->published()->count(),
        ];
    }

    public function recheckDuplicates(): array
    {
        SourceProduct::query()
            ->where('status', 'duplicate')
            ->update([
                'status' => 'needs_review',
                'disposition' => null,
                'duplicate_of_id' => null,
                'product_id' => null,
                'errors' => null,
            ]);

        $flagged = 0;

        SourceProduct::query()
            ->where('status', 'needs_review')
            ->orderBy('id')
            ->get()
            ->each(function (SourceProduct $source) use (&$flagged) {
                $this->detectDuplicate($source);

                if ($source->fresh()->status === 'duplicate') {
                    $flagged++;
                }
            });

        foreach (ImportBatch::query()->cursor() as $batch) {
            $counts = SourceProduct::query()
                ->where('import_batch_id', $batch->id)
                ->select('status', DB::raw('COUNT(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status');

            $batch->update([
                'duplicate_rows' => (int) ($counts['duplicate'] ?? 0),
                'failed_rows' => (int) ($counts['failed'] ?? 0),
                'imported_rows' => (int) ($counts['imported'] ?? 0),
            ]);
        }

        return ['duplicates' => $flagged];
    }

    private function detectDuplicate(SourceProduct $source): void
    {
        if (blank($source->raw_name)) {
            return;
        }

        $nameSignature = $this->signature(null, $source->raw_name);
        $brandSignature = $this->signature($source->raw_brand, null);
        $modelSignature = filled($source->raw_model) ? $this->signature(null, $source->raw_model) : '';

        $candidates = SourceProduct::query()
            ->whereKeyNot($source->id)
            ->whereNotNull('raw_name')
            ->whereIn('status', ['needs_review', 'imported'])
            ->get();

        foreach ($candidates as $candidate) {
            $candidateBrand = $this->signature($candidate->raw_brand, null);
            $brandCompatible = $brandSignature === '' || $candidateBrand === '' || $brandSignature === $candidateBrand;

            if (! $brandCompatible) {
                continue;
            }

            $sameName = $nameSignature !== '' && $this->signature(null, $candidate->raw_name) === $nameSignature;
            $sameModel = $modelSignature !== ''
                && filled($candidate->raw_model)
                && $this->signature(null, $candidate->raw_model) === $modelSignature;

            if ($sameName || $sameModel) {
                $source->update([
                    'status' => 'duplicate',
                    'disposition' => 'duplicate_merged',
                    'duplicate_of_id' => $candidate->id,
                    'errors' => [['message' => 'Possible duplicate of '.$candidate->source_url]],
                ]);

                return;
            }
        }

        $existingProduct = Product::query()
            ->whereRaw('LOWER(name) = ?', [Str::lower(trim($source->raw_name))])
            ->first();

        if ($existingProduct) {
            $source->update([
                'status' => 'duplicate',
                'disposition' => 'duplicate_merged',
                'product_id' => $existingProduct->id,
                'errors' => [['message' => 'Possible duplicate of existing Tajira product '.$existingProduct->sku]],
            ]);

            return;
        }

        if ($source->raw_mpn) {
            $product = Product::query()->where('mpn', $source->raw_mpn)->orWhere('sku', $source->raw_mpn)->first();

            if ($product) {
                $source->update([
                    'status' => 'duplicate',
                    'disposition' => 'duplicate_merged',
                    'product_id' => $product->id,
                    'errors' => [['message' => 'Possible duplicate of existing Tajira product '.$product->sku]],
                ]);
            }
        }
    }

    private function signature(?string $brand, ?string $model): string
    {
        $value = Str::lower(trim((string) $brand.' '.(string) $model));

        return preg_replace('/[^a-z0-9]+/', '', $value) ?? '';
    }

    private function proposedSku(?string $brand, ?string $model, ?string $name): string
    {
        $base = Str::upper(Str::substr(Str::slug(trim((string) $brand.' '.(string) ($model ?: $name)), '-'), 0, 60));

        return $base !== '' ? $base : 'TAJIRA-STAGED';
    }

    private function matchBrand(?string $name): ?Brand
    {
        if (blank($name)) {
            return null;
        }

        return Brand::query()
            ->whereRaw('LOWER(name) = ?', [Str::lower(trim($name))])
            ->orWhere('slug', Str::slug($name))
            ->first();
    }

    /**
     * @return array<int, Category>
     */
    private function matchCategories(array $names): array
    {
        $aliases = [
            'networking tools & accessories' => 'structured-cabling',
            'cctv security cameras' => 'cctv-cameras',
            'dahua cctv security cameras' => 'cctv-cameras',
            'imou cctv security cameras' => 'cctv-cameras',
            'ctc fibre optics solutions' => 'fibre-optic-solutions',
            'fiber' => 'fibre-optic-solutions',
            'wireless outdoor cpe' => 'outdoor-cpe',
            'tp-link wireless access points' => 'wireless-access-points',
            'ubiquiti unifi wireless access points' => 'wireless-access-points',
            'fiber fat box' => 'fibre-enclosures',
            'splicing machine' => 'splicing-machines',
            'optical line terminal' => 'optical-line-terminals',
            'dac cable' => 'dac-cables',
            'fiber patch cords & pigtails' => 'fibre-patch-cords-and-pigtails',
            'fiber optic cables' => 'fibre-optic-cables',
            'fiber optic enclosurers' => 'fibre-enclosures',
            'fibre optic bare plc splitters' => 'plc-splitters',
            'pfm ups' => 'power-supplies-ups',
            'nvr' => 'network-video-recorders',
            'usb wifi adapters' => 'usb-wifi-adapters',
            'point to point antennas' => 'point-to-point-antennas',
            'poe injectors' => 'poe-injectors',
            'poe adapters' => 'poe-injectors',
            'rg59 cables' => 'coaxial-cables',
            'telephones' => 'analog-phones',
            'excelltel pbx systems' => 'analog-pbx',
            'fanvil ip phones' => 'ip-phones',
            'yealink ip phones' => 'ip-phones',
            'mobile mifi' => 'mobile-mifi',
            'crimping tool' => 'network-tools',
            'fiber cleaver' => 'fibre-tool-kits',
            'fiber tool kit' => 'fibre-tool-kits',
            'laser pen' => 'fibre-test-equipment',
            'optical powermeter' => 'fibre-test-equipment',
            'optical time domain reflectometr' => 'fibre-test-equipment',
            'optical fiber identifier' => 'fibre-test-equipment',
            'pbx & phones' => 'pbx-ip-telephony',
        ];

        $matched = [];

        foreach ($names as $name) {
            $key = Str::lower(trim((string) $name));

            if ($key === '') {
                continue;
            }

            $slug = $aliases[$key] ?? Str::slug($key);
            $category = Category::query()
                ->where('slug', $slug)
                ->orWhereRaw('LOWER(name) = ?', [$key])
                ->first();

            if ($category) {
                $matched[$category->id] = $category;
            }
        }

        $matched = array_values($matched);

        usort($matched, fn (Category $a, Category $b) => ($a->parent_id === null ? 1 : 0) <=> ($b->parent_id === null ? 1 : 0));

        return $matched;
    }

    private function normaliseUrl(string $url): string
    {
        $url = trim($url);
        $parts = parse_url($url);
        $scheme = $parts['scheme'] ?? 'https';
        $host = strtolower($parts['host'] ?? '');
        $path = preg_replace('#/+#', '/', $parts['path'] ?? '/') ?? '/';

        return $scheme.'://'.$host.($path !== '/' ? rtrim($path, '/') : '/');
    }

    private function assertRobotsAllowed(string $base): void
    {
        try {
            $robots = $this->fetcher->fetch($base.'/robots.txt');

            foreach (preg_split('/\r\n|\r|\n/', $robots['body']) as $line) {
                if (preg_match('/^\s*disallow:\s*\/\s*$/i', $line)) {
                    throw new \RuntimeException('The source robots.txt disallows crawling. Discovery stopped.');
                }
            }
        } catch (\RuntimeException $exception) {
            if (str_contains($exception->getMessage(), 'robots.txt disallows')) {
                throw $exception;
            }

            // A missing robots.txt is not treated as blanket permission bypass; the sitemap request still respects the allowlist.
        }
    }
}
