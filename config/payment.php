<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Payment Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for payment gateways (Stripe, PayPal)
    |
    */

    'currency' => env('PAYMENT_CURRENCY', 'EUR'),
    'currency_symbol' => env('PAYMENT_CURRENCY_SYMBOL', '€'),
    
    /*
    |--------------------------------------------------------------------------
    | Stripe Configuration
    |--------------------------------------------------------------------------
    */
    'stripe' => [
        'enabled' => env('STRIPE_ENABLED', true),
        'public_key' => env('STRIPE_PUBLIC_KEY', ''),
        'secret_key' => env('STRIPE_SECRET_KEY', ''),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET', ''),
        'api_version' => '2024-12-18.acacia',
    ],

    /*
    |--------------------------------------------------------------------------
    | PayPal Configuration
    |--------------------------------------------------------------------------
    */
    'paypal' => [
        'enabled' => env('PAYPAL_ENABLED', true),
        'mode' => env('PAYPAL_MODE', 'sandbox'), // sandbox or live
        'sandbox' => [
            'client_id' => env('PAYPAL_SANDBOX_CLIENT_ID', ''),
            'secret' => env('PAYPAL_SANDBOX_SECRET', ''),
        ],
        'live' => [
            'client_id' => env('PAYPAL_LIVE_CLIENT_ID', ''),
            'secret' => env('PAYPAL_LIVE_SECRET', ''),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Fee Configuration
    |--------------------------------------------------------------------------
    */
    'fees' => [
        'platform_percentage' => env('PLATFORM_FEE_PERCENTAGE', 10), // Platform takes 10% commission
        'stripe_fee_percentage' => 2.9, // Stripe's percentage fee
        'stripe_fee_fixed' => 0.30, // Stripe's fixed fee in EUR
        'paypal_fee_percentage' => 3.4, // PayPal's percentage fee
        'paypal_fee_fixed' => 0.35, // PayPal's fixed fee in EUR
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Status
    |--------------------------------------------------------------------------
    */
    'statuses' => [
        'pending' => 'pending',
        'processing' => 'processing',
        'completed' => 'completed',
        'failed' => 'failed',
        'cancelled' => 'cancelled',
        'refunded' => 'refunded',
        'partially_refunded' => 'partially_refunded',
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Methods
    |--------------------------------------------------------------------------
    */
    'methods' => [
        'stripe' => 'Carte bancaire (Stripe)',
        'paypal' => 'PayPal',
        'cash' => 'Espèces (à la remise)',
    ],
];