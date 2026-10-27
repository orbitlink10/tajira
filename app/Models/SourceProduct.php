<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SourceProduct extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'raw_categories' => 'array',
            'raw_attributes' => 'array',
            'raw_image_urls' => 'array',
            'errors' => 'array',
            'raw_price_minor' => 'integer',
            'source_lastmod' => 'datetime',
            'discovered_at' => 'datetime',
            'fetched_at' => 'datetime',
            'verified_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class, 'import_batch_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function duplicateOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'duplicate_of_id');
    }

    public function statusLabel(): string
    {
        return ucwords(str_replace('_', ' ', $this->status));
    }

    public function isPublishable(): bool
    {
        return $this->status === 'approved' && $this->verified_at !== null && $this->product_id !== null;
    }
}
