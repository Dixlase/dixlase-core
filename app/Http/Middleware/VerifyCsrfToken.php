<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as BaseVerifyCsrfToken;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class VerifyCsrfToken extends BaseVerifyCsrfToken
{
    protected $except = [
        //'admin/login', // テストで除外
    ];

    protected function tokensMatch($request)
    {
        $sessionToken = $request->session()->token();
        $tokenFromRequest = $this->getTokenFromRequest($request);

        // デバッグ出力
        Log::info('CSRF Debug', [
            'sessionToken' => $sessionToken,
            'requestToken' => $tokenFromRequest,
            'equal' => $sessionToken && $tokenFromRequest
                ? Str::length($sessionToken) . '|' . Str::length($tokenFromRequest) . ' => ' . ($sessionToken === $tokenFromRequest)
                : null,
        ]);

        return is_string($sessionToken) &&
            is_string($tokenFromRequest) &&
            hash_equals($sessionToken, $tokenFromRequest);
    }
}
