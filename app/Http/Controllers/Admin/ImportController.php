<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\ImportBatch;
use App\Models\SourceProduct;
use App\Services\AuditLogger;
use App\Services\Imports\CatalogueImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class ImportController extends Controller
{
    public function index(CatalogueImportService $imports)
    {
        $this->authorize('imports.manage');

        $metrics = $imports->metrics();
        $batches = ImportBatch::query()->latest()->limit(15)->get();
        $staged = SourceProduct::query()
            ->whereIn('status', ['needs_review', 'duplicate', 'failed', 'rejected'])
            ->latest('fetched_at')
            ->limit(25)
            ->get();

        return view('admin.imports.index', compact('metrics', 'batches', 'staged'));
    }

    public function discovery(Request $request, CatalogueImportService $imports, AuditLogger $audit)
    {
        $this->authorize('imports.manage');

        $data = $request->validate([
            'limit' => ['required', 'integer', 'min:1', 'max:1000'],
            'source' => ['nullable', 'url', 'max:255'],
        ]);

        try {
            $batch = $imports->discoverSitemap($request->user()->id, (int) $data['limit'], $data['source'] ?? null);
        } catch (Throwable $exception) {
            return back()->with('error', 'Discovery could not start: '.$exception->getMessage());
        }

        $audit->log('import.discovery.created', $batch);

        return redirect()->route('admin.imports.show', $batch)->with('success', 'Discovery staged '.$batch->total_rows.' unique source URL(s). Review before fetching and publishing.');
    }

    public function uploadCsv()
    {
        $this->authorize('imports.manage');

        return redirect()->route('admin.products.import.form')
            ->with('info', 'Use the CSV importer on the products screen. It supports a dry run with row-level validation and never overwrites existing products.');
    }

    public function show(Request $request, ImportBatch $batch, CatalogueImportService $imports)
    {
        $this->authorize('imports.manage');

        $sources = SourceProduct::query()
            ->where('import_batch_id', $batch->id)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.trim((string) $request->query('q')).'%';
                $q->where(fn ($inner) => $inner->where('raw_name', 'like', $term)->orWhere('raw_brand', 'like', $term)->orWhere('source_url', 'like', $term));
            })
            ->orderBy('id')
            ->paginate(25)
            ->withQueryString();

        $metrics = $imports->metrics();
        $brands = Brand::query()->orderBy('name')->get(['id', 'name']);
        $categories = Category::query()->orderBy('name')->get(['id', 'name']);
        $statusCounts = SourceProduct::query()
            ->where('import_batch_id', $batch->id)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.imports.show', compact('batch', 'sources', 'metrics', 'brands', 'categories', 'statusCounts'));
    }

    public function fetch(Request $request, ImportBatch $batch, CatalogueImportService $imports, AuditLogger $audit)
    {
        $this->authorize('imports.manage');
        $data = $request->validate(['limit' => ['nullable', 'integer', 'min:1', 'max:200']]);

        try {
            $result = $imports->fetchBatch($batch, (int) ($data['limit'] ?? 50));
        } catch (Throwable $exception) {
            return back()->with('error', 'Fetching failed: '.$exception->getMessage());
        }

        $audit->log('import.batch.fetched', $batch, [], $result);

        return redirect()->route('admin.imports.show', $batch)->with('success', 'Fetched '.$result['fetched'].' page(s); '.$result['failed'].' failed; '.$result['duplicates'].' duplicate(s) flagged; '.$result['remaining'].' remaining.');
    }

    public function resume(Request $request, ImportBatch $batch, CatalogueImportService $imports, AuditLogger $audit)
    {
        return $this->fetch($request, $batch, $imports, $audit);
    }

    public function approve(Request $request, SourceProduct $sourceProduct, CatalogueImportService $imports, AuditLogger $audit)
    {
        $this->authorize('imports.manage');
        $data = $request->validate([
            'sku' => ['nullable', 'string', 'max:80'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
        ]);

        try {
            $product = $imports->approve($sourceProduct, $request->user(), $data['sku'] ?? null, $data['brand_id'] ?? null, $data['category_id'] ?? null);
        } catch (Throwable $exception) {
            return back()->with('error', $exception instanceof ValidationException
                ? collect($exception->errors())->flatten()->first()
                : $exception->getMessage());
        }

        $audit->log('import.source.approved', $sourceProduct, [], ['product_id' => $product->id]);

        return redirect()->route('admin.products.edit', $product)
            ->with('success', 'Staged record approved as a draft product. Verify specifications and set Tajira pricing before publishing.');
    }

    public function reject(Request $request, SourceProduct $sourceProduct, CatalogueImportService $imports, AuditLogger $audit)
    {
        $this->authorize('imports.manage');
        $data = $request->validate(['note' => ['nullable', 'string', 'max:500']]);
        $imports->reject($sourceProduct, $data['note'] ?? null);
        $audit->log('import.source.rejected', $sourceProduct);

        return back()->with('success', 'Source record rejected.');
    }

    public function retry(SourceProduct $sourceProduct, CatalogueImportService $imports, AuditLogger $audit)
    {
        $this->authorize('imports.manage');
        $imports->retry($sourceProduct);
        $audit->log('import.source.retried', $sourceProduct);

        return back()->with('success', 'Source record queued for retry. Use “Fetch next batch” to run it.');
    }
}
