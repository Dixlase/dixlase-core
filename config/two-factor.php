<?php

/*
|--------------------------------------------------------------------------
| Two-Factor Authentication Configuration
|--------------------------------------------------------------------------
|
| This file contains configuration for two-factor authentication including
| device name detection patterns, authentication methods, and other settings.
|
*/

return [
    /*
    |--------------------------------------------------------------------------
    | Device Name Detection Patterns
    |--------------------------------------------------------------------------
    |
    | These patterns are used to detect device names from User-Agent strings.
    | You can customize the browser and OS detection patterns here.
    |
    */
    'device_detection' => [
        'browsers' => [
            'Chrome' => '/Chrome/i',
            'Firefox' => '/Firefox/i',
            'Safari' => '/Safari/i',
            'Edge' => '/Edge/i',
            'Opera' => '/Opera|OPR/i',
            'Internet Explorer' => '/MSIE|Trident/i',
        ],
        
        'operating_systems' => [
            'Windows' => '/Windows/i',
            'macOS' => '/Macintosh|Mac OS X/i',
            'Linux' => '/Linux/i',
            'iPhone' => '/iPhone/i',
            'iPad' => '/iPad/i',
            'Android' => '/Android/i',
            'iOS' => '/iOS/i',
        ],
        
        // デフォルト名称
        'defaults' => [
            'browser' => 'Unknown Browser',
            'os' => 'Unknown OS',
            'device' => 'Unknown Device',
        ],
        
        // 除外パターン（Safariの検出でChromeを除外するなど）
        'exclusions' => [
            'Safari' => ['/Chrome/i'], // SafariとしてマッチしてもChromeが含まれていたら除外
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Methods
    |--------------------------------------------------------------------------
    |
    | Available two-factor authentication methods.
    | 0 = Email, 1 = Device, 2 = Biometric
    |
    */
    'methods' => [
        'email' => 0,
        'device' => 1,
        'biometric' => 2,
    ],

    /*
    |--------------------------------------------------------------------------
    | Code Expiration
    |--------------------------------------------------------------------------
    |
    | The number of minutes that a two-factor authentication code is valid.
    | This applies to email and device authentication codes.
    |
    */
    'code_expiration' => env('TWO_FACTOR_CODE_EXPIRE', 5),

    /*
    |--------------------------------------------------------------------------
    | Resend Interval
    |--------------------------------------------------------------------------
    |
    | The number of seconds that must pass before a user can request
    | a new two-factor authentication code.
    |
    */
    'resend_interval' => env('TWO_FACTOR_RESEND_INTERVAL', 60),

    /*
    |--------------------------------------------------------------------------
    | Device Token Length
    |--------------------------------------------------------------------------
    |
    | The length of the random token generated for trusted device authentication.
    |
    */
    'device_token_length' => 64,

    /*
    |--------------------------------------------------------------------------
    | Device Cookie Settings
    |--------------------------------------------------------------------------
    |
    | Settings for the trusted device cookie.
    |
    */
    'device_cookie' => [
        'name' => 'trusted_device_token',
        'lifetime' => 60 * 24 * 30, // 30 days in minutes
        'path' => '/',
        'domain' => null,
        'secure' => true,
        'http_only' => true,
        'same_site' => 'strict',
    ],

    /*
    |--------------------------------------------------------------------------
    | Trusted Device Expiration
    |--------------------------------------------------------------------------
    |
    | The number of days that a trusted device remains valid.
    | After this period, the device will require re-authentication.
    | This can be overridden by member settings.
    |
    */
    'device_expiration_days' => env('TWO_FACTOR_DEVICE_EXPIRE_DAYS', 30),
];
