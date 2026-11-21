<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Facades\Audit;
use App\Models\AuditLog;
use App\Services\Security\RecentAuthentication;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal Core only. Do not reference from plugins/themes
 *
 * Require the signed-in member to have confirmed their password or a passkey
 * within the last N minutes (dixlase-core#675).
 *
 * Usage on a core route: `->middleware('recent_auth')`, or `recent_auth:5`
 * for a shorter window than `security.recent_auth.minutes`.
 *
 * Without a recent confirmation, a page request is sent to the confirmation
 * screen and comes back to the page afterwards. A form submission cannot be
 * replayed safely (it may carry a file, and repeating it silently is how a
 * forged one would get through), so the member comes back to the page the
 * form was on and submits it again. A request that expects JSON gets 423
 * with the confirmation URL, both in the body and in the
 * `X-Dixlase-Recent-Auth` header, which the admin scripts follow.
 */
class RequireRecentAuthentication
{
    public const HEADER = 'X-Dixlase-Recent-Auth';

    public function __construct(private readonly RecentAuthentication $recent) {}

    public function handle(Request $request, Closure $next, ?string $minutes = null): Response
    {
        $member = $request->user('member');

        // The authentication middleware runs first on every guarded route;
        // without a member there is nothing to confirm and nothing to allow.
        if ($member === null) {
            abort(403);
        }

        $window = $minutes !== null && ctype_digit($minutes) && (int) $minutes > 0
            ? (int) $minutes
            : RecentAuthentication::defaultMinutes();

        if ($this->recent->isRecent($member->getAuthIdentifier(), $window)) {
            return $next($request);
        }

        $this->recent->rememberIntended(
            $this->intendedUrl($request),
            ! self::isPageRequest($request),
        );

        Audit::log([
            'category' => AuditLog::getCategoryForAction(AuditLog::ACTION_STEP_UP_AUTH_REQUIRED),
            'action' => AuditLog::ACTION_STEP_UP_AUTH_REQUIRED,
            'severity' => AuditLog::getDefaultSeverity(AuditLog::ACTION_STEP_UP_AUTH_REQUIRED),
            'actor' => $member,
            'context' => [
                'route' => $request->route()?->getName(),
                'method' => $request->getMethod(),
            ],
        ]);

        $confirmUrl = route('admin.recent-auth.show');

        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('admin/recent-auth.required'),
                'redirect' => $confirmUrl,
            ], 423)->header(self::HEADER, $confirmUrl);
        }

        return redirect()->to($confirmUrl);
    }

    /**
     * Where the member should land after confirming.
     *
     * A page request comes back to itself. Anything else comes back to the
     * page it was sent from, as long as that page is on this site.
     */
    private function intendedUrl(Request $request): string
    {
        if (self::isPageRequest($request)) {
            return $request->fullUrl();
        }

        $fallback = route('admin.dashboard');
        $previous = url()->previous($fallback);

        // The referring page is the one the form was on. It can share its
        // path with the form's action (a GET page posting to itself), which
        // is fine: the member comes back to the page, not to the action.
        return self::isOwnUrl($request, $previous) ? $previous : $fallback;
    }

    private static function isPageRequest(Request $request): bool
    {
        return $request->isMethod('GET') && ! $request->expectsJson();
    }

    public static function isOwnUrl(Request $request, string $url): bool
    {
        $origin = $request->getSchemeAndHttpHost();

        return $url === $origin || str_starts_with($url, $origin.'/');
    }
}
