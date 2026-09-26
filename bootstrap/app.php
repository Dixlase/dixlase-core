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

use App\Helpers\LocaleHelper;
use App\Http\Controllers\System\FpmCacheResetController;
use App\Http\Middleware\AdminIpFilter;
use App\Http\Middleware\AppendSourceCodeHeader;
use App\Http\Middleware\ApplySessionConfig;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\AuthenticateApiKey;
use App\Http\Middleware\AuthenticateIap;
use App\Http\Middleware\AuthenticateMtls;
use App\Http\Middleware\BlockPluginRoutes;
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\CheckInstallationSteps;
use App\Http\Middleware\CheckLockdown;
use App\Http\Middleware\CheckMaintenanceMode;
use App\Http\Middleware\CheckMenuAccess;
use App\Http\Middleware\CheckMenuEdit;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\ContentSecurityPolicy;
use App\Http\Middleware\DemoGuard;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Http\Middleware\EnsurePluginAdminAccess;
use App\Http\Middleware\ForceHttps;
use App\Http\Middleware\FrontIpFilter;
use App\Http\Middleware\LogAdminActivity;
use App\Http\Middleware\LogApiRequest;
use App\Http\Middleware\PreventAdminResponseCaching;
use App\Http\Middleware\ResolveSiteContext;
use App\Http\Middleware\SafeMode;
use App\Http\Middleware\SetAdminLocale;
use App\Http\Middleware\SetMemberLocale;
use App\Http\Middleware\ThrottleApiRequest;
use App\Support\Api\ApiErrorResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ThrottleRequestsException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: [
            __DIR__.'/../routes/front.php',
            __DIR__.'/../routes/install.php',
            __DIR__.'/../routes/admin.php',
        ],
        // REST API routes. Mounted under /api by Laravel's default API
        // prefix. Lives outside the front locale infrastructure so when
        // the future multilingual plugin re-introduces a path-prefix
        // locale group + Route::fallback(), /api/* must stay opaque to
        // it (no locale segment, no 302 to /{locale}/api/...).
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Bare internal route: FPM cache reset hook invoked by
            // dls:core:update / dls:core:rollback from CLI to refresh
            // opcache + realpath cache inside the PHP-FPM SAPI after a
            // source/vendor swap. Token-gated in the controller; kept
            // outside every route group (no session, no auth, no
            // locale prefix) so it survives even mid-swap.
            Route::post('/system/fpm-cache-reset', [
                FpmCacheResetController::class,
                'reset',
            ])->name('system.fpm-cache-reset');
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Trust forwarded headers only from the upstream IPs declared in
        // config/trustedproxy.php (driven by the TRUSTED_PROXIES env var).
        // Default is `[]` (no proxies trusted); operators behind CDNs,
        // reverse proxies, or IAPs must explicitly populate TRUSTED_PROXIES.
        // See SECURITY.md "Reverse-proxy / IAP deployment".
        //
        // The config file is required directly rather than read via config()
        // because withMiddleware() runs before the config service is bound
        // to the container (LoadConfiguration bootstrapper has not run yet).
        // The file itself only depends on env() and Symfony's Request class,
        // both of which are already available at this point.
        $trustedProxyConfig = require __DIR__.'/../config/trustedproxy.php';
        $middleware->trustProxies(
            at: $trustedProxyConfig['proxies'],
            headers: $trustedProxyConfig['headers'],
        );

        // Register global middlewares.
        //
        // PreventRequestsDuringMaintenance MUST be at the top of the
        // stack: it short-circuits every request when
        // storage/framework/maintenance.php exists so the rest of the
        // middleware (session, CSP, source-code header, …) never runs
        // against a mid-swap tree. Round 5 root cause for Finding D:
        // Dixlase's custom `use([...])` replaces Laravel's default
        // global stack, which historically dropped this middleware —
        // so `Artisan::call('down')` from the CoreUpdater / CoreRollback
        // wrote the maintenance sentinel but nothing observed it, and
        // the "maintenance window" was effectively non-existent. That
        // let requests reach a half-swapped source tree and produced
        // the transient 500s (`Session driver [guard-aware-database] is
        // not supported`, `require(AssetHelper): No such file`,
        // half-written composer/installed.php parse errors) the sandbox
        // caught during dryrun-10 verification.
        //
        // Note: Dixlase also has its own `CheckMaintenanceMode`
        // middleware (bootstrap/app.php `appendToGroup('web', …)`
        // below), which is a DB-driven "maintenance mode" toggle for
        // the operator-facing site UI — a totally different concept
        // from `Artisan::call('down')`. Both coexist safely.
        $middleware->use([
            PreventRequestsDuringMaintenance::class, // Round 5 Finding D root fix: make `Artisan::call('down')` actually block requests
            TrustProxies::class, // Apply config/trustedproxy.php to incoming forwarded headers
            CheckInstallationReady::class, // Check installation readiness + installation status
            ResolveSiteContext::class, // Resolve current site for multi-site support (fixed to primary site in v0.1.0)
            ForceHttps::class, // Force HTTPS redirect when FORCE_SSL is enabled
            ApplySessionConfig::class, // Apply session settings dynamically
            ContentSecurityPolicy::class, // Add CSP headers
            AppendSourceCodeHeader::class, // AGPL §13: attach X-Source-Code header
        ]);

        // Exclude CSP report endpoint from CSRF verification. The
        // fpm-cache-reset internal hook (see routes registered via
        // withRouting's `then:` above) is POST but token-authenticated
        // in the controller — CSRF would fail here because the call
        // originates from a CLI subprocess with no session context.
        $middleware->preventRequestForgery(except: [
            'csp-report',
            'system/fpm-cache-reset',
        ]);

        // The fpm-cache-reset hook must remain reachable while the site
        // is in maintenance mode — that IS its point (called between
        // the source swap and the maintenance lift). Now that
        // PreventRequestsDuringMaintenance is on the global stack, this
        // except list actually takes effect: fpm-cache-reset passes
        // through the sentinel check while every other request 503s.
        // `/up` is Laravel's default health-check path and is exempt
        // by default at the framework level.
        $middleware->preventRequestsDuringMaintenance(except: [
            'system/fpm-cache-reset',
        ]);

        // The language switcher cookie is a non-sensitive preference and
        // is read in plaintext (e.g. by curl tests, reverse proxies, JS).
        $middleware->encryptCookies(except: [
            LocaleHelper::COOKIE_NAME,
        ]);

        // Middleware to execute after session starts
        $middleware->appendToGroup('web', [
            CheckMaintenanceMode::class, // Maintenance mode check (executed after session to reference authentication state)
            SafeMode::class, // Safe mode detection (executed after authentication, supports CSP/plugin/theme)
            BlockPluginRoutes::class, // Route blocking when plugin safe mode is active
            SetAdminLocale::class, // Admin locale resolver: member.locale -> Site.primary_locale -> Accept-Language -> fallback
            SetMemberLocale::class, // Install-screen locale + member-specific overrides (admin only). Front locale is handled by SetFrontLocale on the locale-prefixed route group.
            DemoGuard::class, // Block destructive admin actions when DIXLASE_DEMO_MODE is on (must run after routing so route name is available)
        ]);

        // Register route middleware aliases
        $middleware->alias([
            'auth' => Authenticate::class, // Authentication
            'verified' => EnsureEmailIsVerified::class, // Email verification
            'member.active' => \App\Http\Middleware\EnsureMemberCanAuthenticate::class, // End the session of a deactivated member
            'admin.ip' => AdminIpFilter::class, // IP address filter
            'admin.no-cache' => PreventAdminResponseCaching::class, // Force browsers not to cache authenticated admin responses
            'front.ip' => FrontIpFilter::class, // Front IP filter
            'log.admin.activity' => LogAdminActivity::class, // Admin panel operation log
            'check.menu.access' => CheckMenuAccess::class, // Admin panel menu access permission
            'check.menu.edit' => CheckMenuEdit::class, // Admin panel menu edit permission
            'plugin.admin.access' => EnsurePluginAdminAccess::class, // Plugin admin route authorization
            'install.steps' => CheckInstallationSteps::class, // Installation step check
            'auth.api' => AuthenticateApiKey::class, // API key authentication
            'throttle.api' => ThrottleApiRequest::class, // API rate limit
            'log.api' => LogApiRequest::class, // API request log
            'role' => CheckRole::class, // Role check
            'permission' => CheckPermission::class, // Permission check
            // Reserved Zero Trust extension-point aliases. Default
            // implementations abort(501) to fail fast when a route applies
            // them without an integration plugin in place. See
            // docs/development/extension-points.md.
            'auth.iap' => AuthenticateIap::class, // Identity-Aware Proxy auth (reserved hook)
            'auth.mtls' => AuthenticateMtls::class, // Mutual TLS / client cert auth (reserved hook)
        ]);

        // For plugin API (API key authentication + rate limit + log)
        $middleware->group('plugin.api', [
            SubstituteBindings::class,
            CheckLockdown::class,
            AuthenticateApiKey::class,
            ThrottleApiRequest::class,
            LogApiRequest::class,
        ]);

        // For public plugin API (no authentication required, rate limit + log only)
        $middleware->group('plugin.api.public', [
            SubstituteBindings::class,
            CheckLockdown::class,
            ThrottleApiRequest::class,
            LogApiRequest::class,
        ]);

        // Middleware group for plugin (basic).
        //
        // Mirrors Laravel's `web` stack order (cookie encryption -> queued
        // cookies -> session -> errors -> CSRF -> bindings) so plugin routes
        // are stateful-safe by default. EncryptCookies and PreventRequestForgery
        // were previously omitted, leaving these groups CSRF-unsafe — a serious
        // gap for the session-authenticated plugin.admin group below.
        $middleware->group('plugin', [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            ShareErrorsFromSession::class,
            PreventRequestForgery::class,
            SubstituteBindings::class,
        ]);

        // For plugin frontend (IP restriction enforced)
        $middleware->group('plugin.web', [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            ShareErrorsFromSession::class,
            PreventRequestForgery::class,
            SubstituteBindings::class,
            FrontIpFilter::class, // Enforce IP restriction
        ]);

        // For plugin admin panel (authentication + IP restriction enforced)
        $middleware->group('plugin.admin', [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            ShareErrorsFromSession::class,
            PreventRequestForgery::class,
            SubstituteBindings::class,
            Authenticate::class.':member', // Enforce authentication
            AdminIpFilter::class, // Enforce IP restriction
            PreventAdminResponseCaching::class, // Force browsers not to cache authenticated plugin-admin responses
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions) {
        // Render API exceptions in the unified envelope documented in
        // docs/development/api-reference/versioning.md. Each typed render
        // returns null for non-API requests so Laravel's default behavior
        // (HTML error pages, login redirects) keeps working for the web.

        $isApi = static fn (Request $request): bool => $request->is('api/*') || $request->wantsJson();

        $exceptions->render(function (ValidationException $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return;
            }

            return ApiErrorResponse::make(
                code: 'validation_failed',
                status: 422,
                message: 'The given data was invalid.',
                details: $e->errors(),
            );
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return;
            }

            return ApiErrorResponse::make(
                code: 'unauthenticated',
                status: 401,
                message: 'Authentication required.',
            );
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return;
            }

            return ApiErrorResponse::make(
                code: 'forbidden',
                status: 403,
                message: 'You do not have permission to perform this action.',
            );
        });

        $exceptions->render(function (ModelNotFoundException $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return;
            }

            return ApiErrorResponse::make(
                code: 'not_found',
                status: 404,
                message: 'The requested resource was not found.',
            );
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return;
            }

            return ApiErrorResponse::make(
                code: 'not_found',
                status: 404,
                message: 'The requested resource was not found.',
            );
        });

        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return;
            }

            return ApiErrorResponse::make(
                code: 'method_not_allowed',
                status: 405,
                message: 'The HTTP method is not supported for this endpoint.',
            );
        });

        $exceptions->render(function (ThrottleRequestsException $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return;
            }

            $response = ApiErrorResponse::make(
                code: 'too_many_requests',
                status: 429,
                message: 'Rate limit exceeded.',
            );

            // Forward Retry-After when the throttler provided one so
            // well-behaved clients can back off correctly.
            $retryAfter = $e->getHeaders()['Retry-After'] ?? null;
            if ($retryAfter !== null) {
                $response->headers->set('Retry-After', (string) $retryAfter);
            }

            return $response;
        });

        // Generic catch-all for any other HttpException-shaped failure
        // (400, 503, …) on /api/* routes. Specific renderers above have
        // already returned for the common cases; this preserves the
        // status code while still using the unified envelope.
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return;
            }

            $status = $e->getStatusCode();

            return ApiErrorResponse::make(
                code: 'http_error',
                status: $status,
                message: $e->getMessage() !== '' ? $e->getMessage() : 'An HTTP error occurred.',
            );
        });

        // Final fallback for genuinely unhandled exceptions on /api/*.
        // Web requests fall through to Laravel's default 500 page.
        $exceptions->render(function (Throwable $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return;
            }

            return ApiErrorResponse::make(
                code: 'server_error',
                status: 500,
                message: 'An internal server error occurred.',
            );
        });
    })->create();
