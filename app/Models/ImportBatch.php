<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportBatch extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'params' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'last_checkpoint_at' => 'datetime',
        ];
    }

    public function sourceProducts(): HasMany
    {
        return $this->hasMany(SourceProduct::class);
    }

    public function rows(): HasMany
    {
        return $this->hasMany(ImportRow::class);
    }

    public function progressPercent(): int
    {
        if ($this->total_rows === 0) {
            return 0;
        }

        return (int) min(100, round($this->processed_rows / $this->total_rows * 100));
    }

    public function statusLabel(): string
    {
        return ucwords(str_replace('_', ' ', $this->status));
    }
}
