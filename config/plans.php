<?php

return [
    'free' => [
        'label' => 'Free',
        'products' => 50,
        'members' => 1,
        'warehouses' => 1,
        'reports' => false,
        'audit_days' => 7,
    ],
    'pro' => [
        'label' => 'Pro',
        'products' => 2000,
        'members' => 5,
        'warehouses' => 3,
        'reports' => true,
        'audit_days' => 90,
    ],
    'business' => [
        'label' => 'Business',
        'products' => null,
        'members' => null,
        'warehouses' => null,
        'reports' => true,
        'audit_days' => 365,
    ],
];
