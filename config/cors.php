<?php

return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'https://kingskitchen.vercel.app',
        'http://localhost:5173', // local frontend dev
    ],
    // Lets Vercel's own preview deployments (kingskitchen-git-*.vercel.app,
    // kingskitchen-<hash>.vercel.app) through too. Remove this if you only
    // ever want the production frontend origin to be able to call the API.
    'allowed_origins_patterns' => [
        '#^https://kingskitchen(-[a-z0-9-]+)?\.vercel\.app$#',
    ],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false, // Bearer-token auth, not cookies — leave false
];
