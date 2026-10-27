<?php

namespace App\Services;

use App\Models\ShippingMethod;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ShippingService
{
    /**
     * @return Collection<int, ShippingMethod>
     */
    public function methodsFor(string $county, int $subtotalMinor, string $deliveryType = 'delivery'): Collection
    {
        $methods = ShippingMethod::query()
            ->with('zone')
            ->where('is_active', true)
            ->orderBy('position')
            ->get()
            ->filter(function (ShippingMethod $method) use ($county, $subtotalMinor, $deliveryType) {
                if ($deliveryType === 'pickup') {
                    return $method->type === 'pickup';
                }

                if ($method->type === 'pickup') {
                    return false;
                }

                if ($method->shipping_zone_id !== null && ! $method->zone?->coversCounty($county)) {
                    return false;
                }

                return $method->appliesTo($subtotalMinor);
            })
            ->values();

        $freeThreshold = (int) (setting('checkout.free_delivery_threshold_minor', config('tajira.checkout.free_delivery_threshold_minor', 0)));
        $defaultInclusive = config('tajira.tax.inclusive_by_default', true);

        return $methods->map(function (ShippingMethod $method) use ($subtotalMinor, $freeThreshold) {
            $price = $method->price_minor;

            if ($method->type !== 'pickup' && $freeThreshold > 0 && $subtotalMinor >= $freeThreshold) {
                $price = 0;
            }

            $method->setAttribute('effective_price_minor', $price);

            return $method;
        });
    }

    /**
     * @return array{method:ShippingMethod,price_minor:int,lead_time:?string}
     */
    public function resolve(?int $methodId, string $county, int $subtotalMinor, string $deliveryType = 'delivery'): array
    {
        if ($deliveryType === 'pickup') {
            $method = ShippingMethod::query()
                ->where('is_active', true)
                ->where('type', 'pickup')
                ->when($methodId, fn ($q) => $q->whereKey($methodId))
                ->orderBy('position')
                ->first();

            if (! $method) {
                throw ValidationException::withMessages([
                    'shipping_method_id' => 'Pickup is not currently available. Please choose delivery.',
                ]);
            }

            return ['method' => $method, 'price_minor' => 0, 'lead_time' => $method->lead_time];
        }

        if (! $methodId) {
            throw ValidationException::withMessages([
                'shipping_method_id' => 'Choose a delivery method for your county.',
            ]);
        }

        $method = $this->methodsFor($county, $subtotalMinor, 'delivery')->firstWhere('id', $methodId);

        if (! $method) {
            throw ValidationException::withMessages([
                'shipping_method_id' => 'The selected delivery method is not available for this order and county.',
            ]);
        }

        return [
            'method' => $method,
            'price_minor' => (int) $method->getAttribute('effective_price_minor'),
            'lead_time' => $method->lead_time,
        ];
    }
}
