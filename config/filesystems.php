<?php

return [
    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
        ],

        // NOTE: We intentionally do NOT rely on the default 'public' disk + `storage:link`.
        // Many shared cPanel hosts (like this project's host) disable PHP's symlink()
        // function for security, which makes `php artisan storage:link` fail silently
        // or throw, leaving product images broken on the live site.
        //
        // Instead, product images are uploaded straight into /public/uploads (a real
        // folder inside the web root, no symlink required) via the 'uploads' disk below.
        'uploads' => [
            'driver' => 'local',
            'root' => public_path('uploads'),
            'url' => env('APP_URL') . '/uploads',
            'visibility' => 'public',
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL') . '/storage',
            'visibility' => 'public',
            'throw' => false,
        ],
    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],
];
