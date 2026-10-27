<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class CouponService
{
    public function findValid(string $code, int $subtotalMinor, ?User $user = null, ?string $email = null): Coupon
    {
        $code = strtoupper(trim($code));
        $coupon = Coupon::query()->where('code', $code)->first();

        if (! $coupon || ! $coupon->is_active) {
            throw ValidationException::withMessages(['coupon_code' => 'This coupon code is not valid.']);
        }

        $now = now();

        if ($coupon->starts_at && $coupon->starts_at->isFuture()) {
            throw ValidationException::withMessages(['coupon_code' => 'This coupon is not active yet.']);
        }

        if ($coupon->ends_at && $coupon->ends_at->isPast()) {
            throw ValidationException::withMessages(['coupon_code' => 'This coupon has expired.']);
        }

        if ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit) {
            throw ValidationException::withMessages(['coupon_code' => 'This coupon has reached its usage limit.']);
        }

        if ($coupon->min_subtotal_minor !== null && $subtotalMinor < $coupon->min_subtotal_minor) {
            throw ValidationException::withMessages([
                'coupon_code' => 'This coupon requires a minimum subtotal of '.money($coupon->min_subtotal_minor).'.',
            ]);
        }

        if ($coupon->per_customer_limit !== null) {
            $redemptions = CouponRedemption::query()
                ->where('coupon_id', $coupon->id)
                ->where(function ($query) use ($user, $email) {
                    if ($user) {
                        $query->where('user_id', $user->id);
                    }

                    if ($email) {
                        $query->orWhere('email', strtolower($email));
                    }
                })
                ->count();

            if ($redemptions >= $coupon->per_customer_limit) {
                throw ValidationException::withMessages(['coupon_code' => 'This coupon has already been used the maximum number of times.']);
            }
        }

        return $coupon;
    }

    public function record(Coupon $coupon, int $orderId, int $discountMinor, ?User $user = null, ?string $email = null): void
    {
        CouponRedemption::query()->firstOrCreate(
            ['coupon_id' => $coupon->id, 'order_id' => $orderId],
            [
                'user_id' => $user?->id,
                'email' => $email ? strtolower($email) : null,
                'amount_minor' => $discountMinor,
            ]
        );

        $coupon->increment('used_count');
    }
}
