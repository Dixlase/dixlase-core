<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * TEMP DEBUG: CSRF 419 原因調査用。
 *
 * bootstrap/app.php の web グループの StartSession の後、VerifyCsrfToken の前に
 * 差し込んで使う。調査完了後は削除する。
 */
class DebugCsrf
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $session = $request->session();
            $sessionId = $session->getId();
            $sessionToken = $session->token();

            $data = [
                'path' => $request->path(),
                'method' => $request->method(),
                'session_id' => $sessionId,
                'session_token_prefix' => $sessionToken ? substr($sessionToken, 0, 10) : null,
                'form_token_prefix' => $request->input('_token') ? substr($request->input('_token'), 0, 10) : null,
                'x_csrf_header_prefix' => $request->header('X-CSRF-TOKEN') ? substr($request->header('X-CSRF-TOKEN'), 0, 10) : null,
                'session_cookie_present' => $request->cookies->has(config('session.cookie')),
                'members_sessions_row_exists' => DB::table('members_sessions')->where('id', $sessionId)->exists(),
                'sessions_row_exists' => DB::table('sessions')->where('id', $sessionId)->exists(),
                'auth_member_id' => auth('member')->id(),
                'admin_url_config' => config('admin.url.admin_url'),
            ];

            Log::debug('DebugCsrf', $data);
        } catch (\Throwable $e) {
            Log::error('DebugCsrf failed: '.$e->getMessage());
        }

        return $next($request);
    }
}
