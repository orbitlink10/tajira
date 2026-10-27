<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingZone extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'counties' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function methods(): HasMany
    {
        return $this->hasMany(ShippingMethod::class)->orderBy('position');
    }

    public function coversCounty(string $county): bool
    {
        $counties = $this->counties;

        return empty($counties) || in_array($county, $counties, true);
    }
}
