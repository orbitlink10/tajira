<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Redirect extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'hits' => 'integer',
        ];
    }

    public static function normalisePath(string $path): string
    {
        $path = trim($path);
        $path = preg_replace('#^https?://[^/]+#i', '', $path) ?? $path;

        if ($path === '') {
            return '/';
        }

        if (! str_starts_with($path, '/')) {
            $path = '/'.$path;
        }

        $path = preg_replace('#/+#', '/', $path) ?? $path;

        return $path !== '/' ? rtrim($path, '/') : '/';
    }
}
