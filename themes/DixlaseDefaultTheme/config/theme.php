<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Theme Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration options for the Dixlase Default Theme.
    | You can customize colors, fonts, and other theme settings here.
    |
    */

    'name' => 'Dixlase Default Theme',
    'version' => '1.0.0',

    /*
    |--------------------------------------------------------------------------
    | Color Scheme
    |--------------------------------------------------------------------------
    */

    'colors' => [
        'primary' => '#3b82f6',
        'secondary' => '#6b7280',
        'accent' => '#10b981',
        'success' => '#10b981',
        'warning' => '#f59e0b',
        'error' => '#ef4444',
        'info' => '#3b82f6',
    ],

    /*
    |--------------------------------------------------------------------------
    | Typography
    |--------------------------------------------------------------------------
    */

    'fonts' => [
        'body' => 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
        'heading' => 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
        'mono' => 'ui-monospace, SFMono-Regular, "SF Mono", Menlo, Monaco, Consolas, monospace',
    ],

    /*
    |--------------------------------------------------------------------------
    | Layout
    |--------------------------------------------------------------------------
    */

    'layout' => [
        'container_max_width' => '1280px',
        'header_height' => '64px',
        'footer_height' => 'auto',
    ],

    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    */

    'features' => [
        'dark_mode' => true,
        'responsive' => true,
        'lazy_loading' => true,
        'smooth_scroll' => true,
        'external_links_new_tab' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Navigation
    |--------------------------------------------------------------------------
    */

    'navigation' => [
        'position' => 'top', // top, bottom, both
        'sticky' => true,
        'mobile_breakpoint' => '768px',
    ],

    /*
    |--------------------------------------------------------------------------
    | Footer
    |--------------------------------------------------------------------------
    */

    'footer' => [
        'show_copyright' => true,
        'show_powered_by' => true,
        'columns' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | SEO
    |--------------------------------------------------------------------------
    */

    'seo' => [
        'meta_description' => 'Dixlase - Modern CMS Platform',
        'meta_keywords' => 'dixlase, cms, laravel, php',
        'og_image' => '/images/og-image.jpg',
    ],
];
