<?php

use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Laravel\Sanctum\Http\Middleware\AuthenticateSession;

return [
    /*
    |--------------------------------------------------------------------------
    | Sanctum Statefulness
    |--------------------------------------------------------------------------
    |
    | The SPA must be included here so Sanctum starts the web session for
    | credentialed cookie-authenticated API requests. Values may be a
    | comma-separated SANCTUM_STATEFUL_DOMAINS environment variable.
    |
    */

    'stateful' => array_values(array_filter(array_map(
        static function (string $domain): string {
            return preg_replace('/^https?:\\/\\//', '', trim($domain)) ?? trim($domain);
        },
        explode(',', (string) env(
            'SANCTUM_STATEFUL_DOMAINS',
            env('FRONTEND_URL', 'localhost:5173,127.0.0.1:5173'),
        )),
    ))),

    'guard' => ['web'],

    'expiration' => null,

    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

    'middleware' => [
        'authenticate_session' => AuthenticateSession::class,
        'encrypt_cookies' => EncryptCookies::class,
        'validate_csrf_token' => ValidateCsrfToken::class,
    ],
];
