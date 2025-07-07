<?php
return [
    // Title used across the application
    'site_title' => 'Series Episode Sorter',

    // Default language for the UI
    'language' => 'en',

    // If true, all pages require authentication to view
    'require_login' => false,

    // Database configuration. Driver can be switched later (e.g. mysql).
    'db' => [
        'driver' => 'sqlite',
        'sqlite' => __DIR__ . '/data/app.db',
        'mysql' => [
            'host' => 'localhost',
            'dbname' => 'series',
            'user' => 'user',
            'pass' => 'pass',
        ],
    ],

    // API keys for external services can be placed here
    'api_keys' => [
        // OMDb API key used for IMDb imports
        'omdb' => ''
    ],
];
