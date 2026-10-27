<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class NavigationItem extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('position');
    }

    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }

    public function resolvedUrl(): string
    {
        if ($this->linkable) {
            return match (true) {
                $this->linkable instanceof Category => route('categories.show', $this->linkable->slug),
                $this->linkable instanceof Brand => route('brands.show', $this->linkable->slug),
                $this->linkable instanceof Page => route('pages.show', $this->linkable->slug),
                $this->linkable instanceof Article => route('guides.show', $this->linkable->slug),
                default => $this->url ?: '#',
            };
        }

        return $this->url ?: '#';
    }
}
