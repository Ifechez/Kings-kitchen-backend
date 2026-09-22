<?php

return [
    'paystack' => [
        'public' => env('PAYSTACK_PUBLIC_KEY'),
        'secret' => env('PAYSTACK_SECRET_KEY'),
        'url' => env('PAYSTACK_PAYMENT_URL', 'https://api.paystack.co'),
    ],

    'vercel_blob' => [
        'token' => env('BLOB_READ_WRITE_TOKEN'),
    ],
];
