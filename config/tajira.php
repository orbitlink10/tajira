<?php

return [
    'business' => [
        'name' => env('TAJIRA_BUSINESS_NAME', 'Tajira'),
        'legal_name' => env('TAJIRA_LEGAL_NAME', ''),
        'domain' => env('TAJIRA_DOMAIN', env('APP_URL', 'http://localhost')),
        'phone' => env('TAJIRA_PHONE', ''),
        'whatsapp' => env('TAJIRA_WHATSAPP', ''),
        'email' => env('TAJIRA_EMAIL', ''),
        'support_email' => env('TAJIRA_SUPPORT_EMAIL', ''),
        'address_line' => env('TAJIRA_ADDRESS_LINE', ''),
        'city' => env('TAJIRA_CITY', 'Nairobi'),
        'country' => env('TAJIRA_COUNTRY', 'Kenya'),
        'postal_code' => env('TAJIRA_POSTAL_CODE', ''),
        'tax_pin' => env('TAJIRA_TAX_PIN', ''),
        'map_url' => env('TAJIRA_MAP_URL', ''),
        'social' => [
            'facebook' => env('TAJIRA_FACEBOOK_URL', ''),
            'instagram' => env('TAJIRA_INSTAGRAM_URL', ''),
            'linkedin' => env('TAJIRA_LINKEDIN_URL', ''),
            'x' => env('TAJIRA_X_URL', ''),
        ],
        'is_local_business' => (bool) env('TAJIRA_LOCAL_BUSINESS', false),
    ],

    'currency' => env('TAJIRA_CURRENCY', 'KES'),

    'tax' => [
        'rate_percent' => (float) env('TAJIRA_TAX_RATE_PERCENT', 16),
        'inclusive_by_default' => (bool) env('TAJIRA_TAX_INCLUSIVE', true),
        'label' => 'VAT',
        'note' => 'Tax treatment is configurable and must be confirmed by Tajira before launch. No eTIMS compliance is claimed.',
    ],

    'checkout' => [
        'reservation_minutes' => (int) env('TAJIRA_STOCK_RESERVATION_MINUTES', 30),
        'order_prefix' => env('TAJIRA_ORDER_PREFIX', 'TJ'),
        'quote_prefix' => 'QT',
        'pickup_enabled' => (bool) env('TAJIRA_PICKUP_ENABLED', true),
        'pickup_instructions' => env('TAJIRA_PICKUP_INSTRUCTIONS', ''),
        'delivery_notice' => env('TAJIRA_DELIVERY_NOTICE', ''),
        'free_delivery_threshold_minor' => (int) round(((float) env('TAJIRA_FREE_DELIVERY_THRESHOLD', 0)) * 100),
    ],

    'policies' => [
        'returns_summary' => env('TAJIRA_RETURNS_SUMMARY', ''),
        'warranty_summary' => env('TAJIRA_WARRANTY_SUMMARY', ''),
        'returns_days' => (int) env('TAJIRA_RETURNS_DAYS', 0),
    ],

    'payments' => [
        'mpesa' => [
            'enabled' => (bool) env('MPESA_ENABLED', false),
            'env' => env('MPESA_ENV', 'sandbox'),
            'consumer_key' => env('MPESA_CONSUMER_KEY', ''),
            'consumer_secret' => env('MPESA_CONSUMER_SECRET', ''),
            'shortcode' => env('MPESA_SHORTCODE', ''),
            'passkey' => env('MPESA_PASSKEY', ''),
            'callback_url' => env('MPESA_CALLBACK_URL', ''),
            'transaction_type' => env('MPESA_TRANSACTION_TYPE', 'CustomerPayBillOnline'),
            'timeout' => (int) env('MPESA_TIMEOUT_SECONDS', 30),
            'callback_ips' => array_values(array_filter(array_map('trim', explode(',', (string) env('MPESA_CALLBACK_IPS', ''))))),
        ],
        'bank_transfer' => [
            'enabled' => (bool) env('BANK_TRANSFER_ENABLED', true),
            'bank_name' => env('BANK_NAME', ''),
            'account_name' => env('BANK_ACCOUNT_NAME', ''),
            'account_number' => env('BANK_ACCOUNT_NUMBER', ''),
            'branch' => env('BANK_BRANCH', ''),
        ],
        'card' => [
            'enabled' => (bool) env('CARD_PAYMENTS_ENABLED', false),
            'provider' => env('CARD_PROVIDER', ''),
            'public_key' => env('CARD_PUBLIC_KEY', ''),
            'secret_key' => env('CARD_SECRET_KEY', ''),
        ],
    ],

    'analytics' => [
        'ga4_id' => env('GA4_MEASUREMENT_ID', ''),
        'gsc_verification' => env('GSC_VERIFICATION', ''),
        'meta_pixel_id' => env('META_PIXEL_ID', ''),
        'require_consent' => (bool) env('ANALYTICS_REQUIRE_CONSENT', true),
    ],

    'feeds' => [
        'merchant_enabled' => (bool) env('MERCHANT_CENTER_ENABLED', false),
        'merchant_token' => env('MERCHANT_FEED_TOKEN', ''),
    ],

    'imports' => [
        'enabled' => (bool) env('IMPORT_ENABLED', true),
        'source_base' => env('IMPORT_SOURCE_BASE', 'https://ctcsolutions.co.ke'),
        'rate_limit_per_minute' => (int) env('IMPORT_RATE_LIMIT_PER_MINUTE', 20),
        'max_bytes' => (int) env('IMPORT_MAX_BYTES', 5_000_000),
        'user_agent' => env('IMPORT_USER_AGENT', 'TajiraCatalogueBot/1.0'),
        'allowed_hosts' => array_filter(explode(',', (string) env('IMPORT_ALLOWED_HOSTS', 'ctcsolutions.co.ke,www.ctcsolutions.co.ke'))),
    ],

    'seo' => [
        'brand_suffix' => env('TAJIRA_SEO_BRAND_SUFFIX', 'Tajira'),
        'default_robots' => 'index,follow',
        'pagination_per_page' => 24,
        'search_per_page' => 24,
        'global_noindex' => (bool) env('TAJIRA_GLOBAL_NOINDEX', false),
    ],

    'staging' => [
        'enabled' => (bool) env('TAJIRA_STAGING_PROTECT', false),
        'username' => env('TAJIRA_STAGING_USER', ''),
        'password' => env('TAJIRA_STAGING_PASSWORD', ''),
    ],

    'security' => [
        'require_admin_mfa' => (bool) env('TAJIRA_REQUIRE_ADMIN_MFA', false),
    ],
];
