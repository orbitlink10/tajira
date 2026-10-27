<?php

use App\Support\KenyaCounties;
use App\Support\Money;
use App\Support\Settings;

if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        return Settings::get($key, $default);
    }
}

if (! function_exists('money')) {
    function money(?int $minor, string $currency = 'KES'): string
    {
        return Money::format($minor, $currency);
    }
}

if (! function_exists('counties')) {
    function counties(): array
    {
        return KenyaCounties::all();
    }
}

if (! function_exists('whatsapp_url')) {
    function whatsapp_url(?string $message = null): string
    {
        $number = preg_replace('/\D+/', '', (string) setting('business.whatsapp', ''));

        if ($number === '') {
            return '';
        }

        $url = 'https://wa.me/'.$number;

        if ($message) {
            $url .= '?text='.rawurlencode($message);
        }

        return $url;
    }
}

if (! function_exists('tel_url')) {
    function tel_url(?string $number = null): string
    {
        $number = preg_replace('/[^\d+]/', '', (string) ($number ?? setting('business.phone', '')));

        return $number ? 'tel:'.$number : '';
    }
}
