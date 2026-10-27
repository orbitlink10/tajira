<?php

namespace App\Services;

use App\Models\Redirect;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SlugService
{
    public function unique(string $name, string $modelClass, ?int $ignoreId = null, string $column = 'slug'): string
    {
        $base = Str::slug($name) ?: 'item';
        $slug = $base;
        $counter = 2;

        while ($modelClass::withTrashed()->where($column, $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    public function handleSlugChange(Model $model, string $oldSlug, string $newPath, string $oldPath): void
    {
        $oldPath = Redirect::normalisePath($oldPath);
        $newPath = Redirect::normalisePath($newPath);

        if ($oldPath === $newPath) {
            return;
        }

        Redirect::query()->where('to_path', $oldPath)->update(['to_path' => $newPath]);
        Redirect::query()->where('from_path', $newPath)->delete();

        Redirect::query()->updateOrCreate(
            ['from_path' => $oldPath],
            [
                'to_path' => $newPath,
                'status_code' => 301,
                'is_active' => true,
                'note' => 'Automatic redirect suggestion from published slug change ('.$model::class.' #'.$model->getKey().').',
            ]
        );
    }
}
