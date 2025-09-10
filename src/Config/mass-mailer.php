<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Mass Mailer Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the Mass Mailer package.
    | You can publish this file to your config directory and modify it.
    |
    */

    'enabled' => env('MASS_MAILER_ENABLED', true),

    'queue' => [
        'connection' => env('MASS_MAILER_QUEUE_CONNECTION', 'database'),
        'name' => env('MASS_MAILER_QUEUE_NAME', 'mass-mailer'),
    ],

    'batch_size' => env('MASS_MAILER_BATCH_SIZE', 50),

    'rate_limiting' => [
        'enabled' => env('MASS_MAILER_RATE_LIMITING_ENABLED', true),
        'max_per_minute' => env('MASS_MAILER_MAX_PER_MINUTE', 100),
    ],

    'attachments' => [
        'max_size' => env('MASS_MAILER_MAX_ATTACHMENT_SIZE', 1024), // KB (1MB)
        'allowed_types' => ['pdf', 'doc', 'docx', 'txt', 'jpg', 'jpeg', 'png'],
        'storage_disk' => env('MASS_MAILER_STORAGE_DISK', 'public'),
    ],

    'templates' => [
        'enabled' => env('MASS_MAILER_TEMPLATES_ENABLED', true),
        'path' => env('MASS_MAILER_TEMPLATES_PATH', 'mass-mailer/templates'),
    ],

    'logging' => [
        'enabled' => env('MASS_MAILER_LOGGING_ENABLED', true),
        'table' => env('MASS_MAILER_LOG_TABLE', 'mass_mailer_logs'),
    ],

    'email_providers' => [
        'default' => env('MASS_MAILER_DEFAULT_PROVIDER', 'smtp'),
        'providers' => [
            'smtp' => [
                'host' => env('MAIL_HOST'),
                'port' => env('MAIL_PORT'),
                'username' => env('MAIL_USERNAME'),
                'password' => env('MAIL_PASSWORD'),
                'encryption' => env('MAIL_ENCRYPTION'),
            ],
            // Add other providers like SendGrid, Mailgun, etc.
        ],
    ],

    'ui' => [
        'framework' => env('MASS_MAILER_UI_FRAMEWORK', 'bootstrap'), // bootstrap, tailwind
        'theme' => env('MASS_MAILER_UI_THEME', 'default'),
        'variables' => ['email', 'first_name', 'last_name'],
        'editor' => [
            'type' => env('MASS_MAILER_EDITOR_TYPE', 'quill'), // quill, tinymce, etc.
        ],
    ],
];
