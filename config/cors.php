<?php

return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'https://kings-kishen.vercel.app',
        'http://localhost:5173', // local frontend dev
    ],
    // Vercel preview deployments for your account only
    'allowed_origins_patterns' => [
        '#^https://kings-kitchen(-[a-z0-9]+)*-ifechez11-1808s-projects\.vercel\.app$#',
    ],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];