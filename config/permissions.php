<?php

return [
    'roles' => [
        'super_admin' => ['*'],

        'catalogue_manager' => [
            'dashboard.view',
            'catalogue.view',
            'catalogue.manage',
            'imports.manage',
            'media.manage',
            'seo.manage',
            'marketing.manage',
            'reports.view',
        ],

        'content_editor' => [
            'dashboard.view',
            'content.view',
            'content.manage',
            'media.manage',
            'reviews.moderate',
            'seo.manage',
            'reports.view',
        ],

        'order_manager' => [
            'dashboard.view',
            'orders.view',
            'orders.manage',
            'quotations.view',
            'quotations.manage',
            'marketing.manage',
            'reports.view',
        ],
    ],

    'abilities' => [
        'dashboard.view',
        'catalogue.view',
        'catalogue.manage',
        'orders.view',
        'orders.manage',
        'quotations.view',
        'quotations.manage',
        'content.view',
        'content.manage',
        'reviews.moderate',
        'imports.manage',
        'media.manage',
        'seo.manage',
        'marketing.manage',
        'settings.manage',
        'users.manage',
        'audit.view',
        'reports.view',
    ],
];
