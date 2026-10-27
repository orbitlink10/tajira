<?php

namespace App\Services;

use App\Models\ContentRevision;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class RevisionService
{
    public function record(Model $model, ?User $user = null, ?string $note = null): void
    {
        ContentRevision::query()->create([
            'revisionable_type' => $model->getMorphClass(),
            'revisionable_id' => $model->getKey(),
            'user_id' => $user?->id,
            'snapshot' => $model->attributesToArray(),
            'note' => $note,
            'created_at' => now(),
        ]);

        $keep = 30;
        $ids = ContentRevision::query()
            ->where('revisionable_type', $model->getMorphClass())
            ->where('revisionable_id', $model->getKey())
            ->orderByDesc('id')
            ->skip($keep)
            ->take(PHP_INT_MAX)
            ->pluck('id');

        if ($ids->isNotEmpty()) {
            ContentRevision::query()->whereIn('id', $ids)->delete();
        }
    }
}
