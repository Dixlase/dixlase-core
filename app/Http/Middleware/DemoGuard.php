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

use App\Enums\MemberRole;
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
     * Names are listed with the full prefix Laravel registers them
     * under — `admin.settings.<group>.<action>.<verb>` — because
     * routes/admin.php nests Route::name() group calls and the registered
     * name has the full chain. Plugin / theme routes ship with their
     * own ::-namespaced names (e.g. "dixlase-seo::admin.seo.base.update")
     * and are intentionally not listed here, so plugin-provided settings
     * stay editable for visitors exploring the demo.
     */
    protected const BLOCKED_ROUTE_NAMES = [
        // Profile self-service authentication changes. These routes carry
        // no check.menu.* gate (a member may always edit their own
        // account), so this guard is the ONLY barrier: without it a visitor
        // could change the shared demo account's password / email / 2FA and
        // lock every other visitor out. Cosmetic profile routes
        // (appearance, notifications, sidebar, passkey prompt) stay editable
        // so the profile screens remain explorable.
        'admin.profile.basic.update',
        'admin.profile.password.update',
        'admin.profile.two-fa.update',
        'admin.profile.passkey.register-options',
        'admin.profile.passkey.register',
        'admin.profile.passkey.revoke',
        'admin.profile.passkey.revoke-all',
        'admin.profile.recovery-codes.generate',
        'admin.profile.recovery-codes.regenerate',
        'admin.profile.recovery-codes.clear-session',

        // Plugin lifecycle on shared plugins/
        'admin.settings.plugins.upload',
        'admin.settings.plugins.download-from-source',
        'admin.settings.plugins.install',
        'admin.settings.plugins.delete',
        'admin.settings.plugins.update',
        'admin.settings.plugins.update-all',
        'admin.settings.plugins.add',

        // Theme lifecycle on shared themes/
        'admin.settings.themes.upload',
        'admin.settings.themes.download-from-source',
        'admin.settings.themes.install',
        'admin.settings.themes.delete',
        'admin.settings.themes.update',
        'admin.settings.themes.update-all',
        'admin.settings.themes.add',

        // Base settings — admin URL, mail server, maintenance toggle.
        // The mail.test-* endpoints stay open so the admin UI can still
        // report status without exposing the ability to point the
        // server elsewhere.
        'admin.settings.base.admin.update',
        'admin.settings.base.mail.update',
        'admin.settings.base.maintenance.update',

        // Security settings — password policy, login policy, session
        // config, captcha, IP allowlist, extension sources, CSP,
        // environment vars, integrity baseline, two-factor policy, error
        // notifications (the notifications target is operator-supplied,
        // so unrestricted edits would let a visitor redirect outbound
        // alerts to an attacker-controlled endpoint). API-key management
        // lives under systems/, blocked in the systems block below.
        'admin.settings.security.password.update',
        'admin.settings.security.two-fa.update',
        'admin.settings.security.notifications.update',
        'admin.settings.security.login.update',
        'admin.settings.security.session.update',
        'admin.settings.security.captcha.update',
        'admin.settings.security.ip.update',
        'admin.settings.security.extensions.update',
        'admin.settings.security.extensions.test-source',
        'admin.settings.security.csp.update',
        'admin.settings.security.csp.confirm',
        'admin.settings.security.csp.rollback',
        'admin.settings.security.environment.update',
        'admin.settings.security.integrity.scan',
        'admin.settings.security.integrity.destroy',
        'admin.settings.security.integrity.bulk-delete',
        'admin.settings.security.integrity.regenerate-baseline',

        // Backup / restore touches shared storage
        'admin.settings.systems.backup.*',

        // System database cleanup is a one-click "delete soft-deleted
        // rows / orphan files" action that can drop tenant data, and
        // cache clear / rebuild mutates shared bootstrap state.
        'admin.settings.systems.database.cleanup',
        'admin.settings.systems.cache.clear',
        'admin.settings.systems.cache.rebuild',

        // API-key management lives under systems/ (not security/): issuing,
        // revoking or regenerating keys on the shared install would let a
        // visitor mint credentials against every tenant.
        'admin.settings.systems.api.update',
        'admin.settings.systems.api.generate-key',
        'admin.settings.systems.api.revoke-key',
        'admin.settings.systems.api.regenerate-key',

        // Integrated updater — checking/applying/rolling back core (or
        // plugin/theme) versions mutates the shared install for every tenant.
        'admin.settings.systems.updates.check',
        'admin.settings.systems.updates.apply',
        'admin.settings.systems.updates.apply-core',
        'admin.settings.systems.updates.rollback-core',

        // Log + audit-trail destruction — clearing log files or purging the
        // audit log would let a visitor erase evidence of demo activity.
        'admin.settings.systems.logs.clear',
        'admin.settings.systems.logs.audit.cleanup',

        // Safe-mode toggle — disabling it could unmask plugins the
        // tenant had not noticed were unsafe in the current session
        'admin.safe-mode.disable',
        'admin.safe-mode.disable-all',
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

        // Super admins (the maintainer running the demo deployment)
        // bypass the guard so they can still operate the host without
        // toggling DIXLASE_DEMO_MODE off in .env. Demo visitors get a
        // lower-privilege role (admin or below) by design; member and role
        // management is kept read-only for them through per-site
        // role_permission_overrides, not through this blocklist.
        $user = $request->user();
        if ($user !== null
            && $user->role instanceof MemberRole
            && $user->role === MemberRole::SUPER_ADMIN) {
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
