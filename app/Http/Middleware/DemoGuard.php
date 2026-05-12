<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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
 *       (see LICENSE.commercial, or contact office@exc-d.com).
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

use Closure;
use Illuminate\Http\Request;

/**
 * Blocks destructive admin actions when config('dixlase.demo_mode') is on.
 *
 * Designed for public demo deployments where many anonymous visitors
 * share the same Dixlase install. Per-tenant DB / storage mutations
 * (page CRUD, blog CRUD, menu / inquiry / SEO settings, theme switch)
 * are still allowed because they get wiped by the demo TTL. What gets
 * blocked is mutations that affect resources shared across tenants
 * (plugins/, themes/, extension sources, mail server credentials,
 * backup output) or that could lock the current tenant out of its own
 * demo session (maintenance toggle, safe-mode, IP allowlist, security
 * boundaries).
 */
class DemoGuard
{
    /**
     * Route names blocked under demo mode. Exact match unless the entry
     * ends with ".*", which makes it a namespace prefix match.
     *
     * Plugin / theme routes ship with their own ::-namespaced names
     * (e.g. "dixlase-seo::admin.seo.base.update") and are intentionally
     * not listed here, so plugin-provided settings stay editable for
     * visitors exploring the demo.
     */
    protected const BLOCKED_ROUTE_NAMES = [
        // Plugin lifecycle on shared plugins/
        'settings.plugins.upload',
        'settings.plugins.download-from-source',
        'settings.plugins.install',
        'settings.plugins.delete',
        'settings.plugins.update',
        'settings.plugins.update-all',
        'settings.plugins.add',

        // Theme lifecycle on shared themes/
        'settings.themes.upload',
        'settings.themes.download-from-source',
        'settings.themes.install',
        'settings.themes.delete',
        'settings.themes.update',
        'settings.themes.update-all',
        'settings.themes.add',

        // Extension source registry (controls where plugins / themes
        // can be fetched from)
        'extensions.update',
        'extensions.test-source',

        // Mail server credentials. The mail.test-* endpoints stay open
        // so the admin UI can still report status without exposing the
        // ability to point the server elsewhere.
        'mail.update',

        // Mode / maintenance / safe-mode — toggling these can lock the
        // demo session out of its own admin
        'maintenance.update',
        'safe-mode.disable',
        'safe-mode.disable-all',

        // Security boundaries — admin URL, IP allowlist, login policy,
        // CSP, captcha, environment vars, integrity log
        'admin.update',
        'login.update',
        'ip.update',
        'csp.update',
        'captcha.update',
        'environment.update',
        'integrity.destroy',

        // Backup / restore touches shared storage
        'backup.*',

        // API keys and session config
        'api.update',
        'session.update',
    ];

    public function handle(Request $request, Closure $next)
    {
        if (! config('dixlase.demo_mode')) {
            return $next($request);
        }

        // Read-only requests always pass; the guard only blocks state
        // mutations.
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();
        if ($routeName === null) {
            return $next($request);
        }

        foreach (self::BLOCKED_ROUTE_NAMES as $blocked) {
            if (str_ends_with($blocked, '.*')) {
                $prefix = substr($blocked, 0, -2);
                if ($routeName === $prefix || str_starts_with($routeName, $prefix.'.')) {
                    return $this->deny($request);
                }
            } elseif ($routeName === $blocked) {
                return $this->deny($request);
            }
        }

        return $next($request);
    }

    protected function deny(Request $request)
    {
        $message = __('admin/demo.action_disabled');

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['error' => $message], 403);
        }

        return back()->with('error', $message);
    }
}
