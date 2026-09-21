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

namespace App\Http\Middleware;

use App\Enums\SafeMode as SafeModeEnum;
use App\Services\SafeModeService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Safe mode detection middleware
 *
 * Detects safe mode from ?safe= parameter and stores it in the session
 * ?safe=1 is treated as ?safe=csp for backward compatibility
 * Multiple modes can be specified comma-separated (e.g. ?safe=csp,plugins)
 * When theme safe mode is active, override the view namespace on the frontend
 */
class SafeMode
{
    public function __construct(
        protected SafeModeService $safeModeService
    ) {}

    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Detect ?safe= parameter.
        //
        // Safe mode disables CSP / plugins / theme for the current session, so
        // activating it downgrades that session's security posture. Restrict
        // activation to a SUPER_ADMIN (the site operator): otherwise any
        // authenticated member could strip their own CSP, and a crafted
        // "?safe=csp" link could trick a privileged user into disabling
        // protections. Deactivation already requires a CSRF-protected POST
        // (admin.safe-mode.disable).
        $safeParam = $request->query('safe');
        $user = $request->user();

        if ($safeParam !== null && $user && $user->role === \App\Enums\MemberRole::SUPER_ADMIN) {
            $modes = SafeModeEnum::fromUrlParam($safeParam);

            foreach ($modes as $mode) {
                if (! $this->safeModeService->isActive($mode)) {
                    $this->safeModeService->activate($mode);
                }
            }
        }

        // Override view namespace if theme safe mode is enabled and on frontend
        if ($this->safeModeService->isActive(SafeModeEnum::Theme) && ! $this->isAdminRoute($request)) {
            $this->overrideThemeViewNamespace();
        }

        return $next($request);
    }

    /**
     * Determine if this is an admin panel route
     *
     * Note: `config/admin/url.php` is expanded by Laravel to the `admin.url` key, so
     * to get the actual admin URL, you need to reference `admin.url.admin_url`
     * `config('admin.url')` alone returns the entire file array and str_starts_with throws a TypeError
     */
    protected function isAdminRoute(Request $request): bool
    {
        $path = $request->path();
        // The operator's configured admin URL (site setting), with the config
        // default as the fallback the helper applies when the database is not
        // reachable — which is exactly the situation safe mode exists for.
        $adminUrl = \App\Helpers\AdminHelper::getAdminUrl();

        return str_starts_with($path, 'admin') || str_starts_with($path, $adminUrl);
    }

    /**
     * Override theme view namespace to safe theme
     */
    protected function overrideThemeViewNamespace(): void
    {
        $viewFactory = app('view');
        $safeThemePath = resource_path('views/safe-theme');

        if (is_dir($safeThemePath)) {
            // Override themes:: namespace to safe theme
            $viewFactory->getFinder()->replaceNamespace('themes', [$safeThemePath]);
        }
    }
}
