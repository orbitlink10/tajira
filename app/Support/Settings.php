<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class Settings
{
    public const CACHE_KEY = 'tajira.settings.v1';

    public static function defaults(): array
    {
        return [
            'business.name' => config('tajira.business.name'),
            'business.legal_name' => config('tajira.business.legal_name'),
            'business.phone' => config('tajira.business.phone'),
            'business.whatsapp' => config('tajira.business.whatsapp'),
            'business.email' => config('tajira.business.email'),
            'business.support_email' => config('tajira.business.support_email'),
            'business.address_line' => config('tajira.business.address_line'),
            'business.city' => config('tajira.business.city'),
            'business.postal_code' => config('tajira.business.postal_code'),
            'business.tax_pin' => config('tajira.business.tax_pin'),
            'business.domain' => config('tajira.business.domain'),
            'business.map_url' => config('tajira.business.map_url'),
            'social.facebook' => config('tajira.business.social.facebook'),
            'social.instagram' => config('tajira.business.social.instagram'),
            'social.linkedin' => config('tajira.business.social.linkedin'),
            'social.x' => config('tajira.business.social.x'),
            'checkout.delivery_notice' => config('tajira.checkout.delivery_notice'),
            'checkout.pickup_enabled' => config('tajira.checkout.pickup_enabled'),
            'checkout.pickup_instructions' => config('tajira.checkout.pickup_instructions'),
            'checkout.returns_summary' => config('tajira.policies.returns_summary'),
            'checkout.warranty_summary' => config('tajira.policies.warranty_summary'),
            'checkout.returns_days' => config('tajira.policies.returns_days'),
            'checkout.reservation_minutes' => config('tajira.checkout.reservation_minutes'),
            'tax.rate_percent' => config('tajira.tax.rate_percent'),
            'tax.inclusive' => config('tajira.tax.inclusive_by_default'),
            'tax.note' => config('tajira.tax.note'),
            'seo.default_title' => config('tajira.business.name').' | Networking equipment in Kenya',
            'seo.default_description' => 'Shop networking equipment for homes, businesses, installers and ISPs from Tajira in Kenya.',
            'seo.ga4_id' => config('tajira.analytics.ga4_id'),
            'seo.gsc_verification' => config('tajira.analytics.gsc_verification'),
            'seo.merchant_feed_enabled' => config('tajira.feeds.merchant_enabled'),
            'analytics.require_consent' => config('tajira.analytics.require_consent'),
            'payments.bank_enabled' => config('tajira.payments.bank_transfer.enabled'),
            'payments.bank_name' => config('tajira.payments.bank_transfer.bank_name'),
            'payments.bank_account_name' => config('tajira.payments.bank_transfer.account_name'),
            'payments.bank_account_number' => config('tajira.payments.bank_transfer.account_number'),
            'payments.bank_branch' => config('tajira.payments.bank_transfer.branch'),
        ];
    }

    public static function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            $stored = Setting::query()->pluck('value', 'key')->all();

            return array_merge(self::defaults(), $stored);
        });
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = self::all()[$key] ?? $default;

        if ($value === '0' || $value === '1') {
            return (bool) (int) $value;
        }

        return $value;
    }

    public static function boolean(string $key): bool
    {
        $value = self::get($key);

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public static function set(string $key, mixed $value, ?int $userId = null): void
    {
        $meta = config('settings.'.$key, []);
        $type = $meta['type'] ?? 'string';

        if (is_bool($value)) {
            $value = $value ? '1' : '0';
        }

        Setting::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $value === null ? null : (string) $value,
                'type' => $type,
                'group' => $meta['group'] ?? 'General',
                'label' => $meta['label'] ?? $key,
                'is_public' => $meta['public'] ?? false,
                'updated_by' => $userId,
            ]
        );

        self::flush();
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
