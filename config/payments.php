<?php

return [
    /*
    | Passerelle Mobile Money utilisée au checkout :
    | - notchpay : page de paiement hébergée (MTN MoMo, Orange Money, cartes)
    | - campay   : push USSD direct sur le téléphone du client
    | - sandbox  : simulateur local (développement / démo), aucun argent réel
    */
    'driver' => env('PAYMENT_DRIVER', 'sandbox'),

    'currency' => 'XAF',

    // Délai après lequel une commande Mobile Money non payée est annulée (stock libéré)
    'unpaid_timeout_minutes' => (int) env('PAYMENT_UNPAID_TIMEOUT', 120),

    'notchpay' => [
        'base_url' => env('NOTCHPAY_BASE_URL', 'https://api.notchpay.co'),
        'public_key' => env('NOTCHPAY_PUBLIC_KEY'),
        'hash_key' => env('NOTCHPAY_HASH_KEY'),   // clé de signature des webhooks
    ],

    'campay' => [
        'base_url' => env('CAMPAY_BASE_URL', 'https://demo.campay.net/api'), // prod : https://www.campay.net/api
        'username' => env('CAMPAY_USERNAME'),
        'password' => env('CAMPAY_PASSWORD'),
        'webhook_key' => env('CAMPAY_WEBHOOK_KEY'), // clé HS256 du JWT "signature"
    ],
];
