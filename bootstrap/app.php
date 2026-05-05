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

use App\Support\Api\ApiErrorResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
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
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Recognize HTTPS/IP correctly behind reverse proxies like Traefik
        $middleware->trustProxies(at: '*');

        // Register global middlewares
        $middleware->use([
            \Illuminate\Http\Middleware\TrustProxies::class, // Recognize HTTPS/IP behind reverse proxy
            \App\Http\Middleware\CheckInstallationReady::class, // Check installation readiness + installation status
            \App\Http\Middleware\ResolveSiteContext::class, // Resolve current site for multi-site support (fixed to primary site in v0.1.0)
            \App\Http\Middleware\ForceHttps::class, // Force HTTPS redirect when FORCE_SSL is enabled
            \App\Http\Middleware\ApplySessionConfig::class, // Apply session settings dynamically
            \App\Http\Middleware\ContentSecurityPolicy::class, // Add CSP headers
            \App\Http\Middleware\AppendSourceCodeHeader::class, // AGPL §13: attach X-Source-Code header
        ]);

        // Exclude CSP report endpoint from CSRF verification
        $middleware->validateCsrfTokens(except: [
            'csp-report',
        ]);

        // The language switcher cookie is a non-sensitive preference and
        // is read in plaintext (e.g. by curl tests, reverse proxies, JS).
        $middleware->encryptCookies(except: [
            \App\Helpers\LocaleHelper::COOKIE_NAME,
        ]);

        // Middleware to execute after session starts
        $middleware->appendToGroup('web', [
            \App\Http\Middleware\CheckMaintenanceMode::class, // Maintenance mode check (executed after session to reference authentication state)
            \App\Http\Middleware\SafeMode::class, // Safe mode detection (executed after authentication, supports CSP/plugin/theme)
            \App\Http\Middleware\BlockPluginRoutes::class, // Route blocking when plugin safe mode is active
            \App\Http\Middleware\SetAdminLocale::class, // Admin locale resolver: member.locale -> Site.primary_locale -> Accept-Language -> fallback
            \App\Http\Middleware\SetMemberLocale::class, // Install-screen locale + member-specific overrides (admin only). Front locale is handled by SetFrontLocale on the locale-prefixed route group.
        ]);

        // Register route middleware aliases
        $middleware->alias([
            'auth' => \App\Http\Middleware\Authenticate::class, // Authentication
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class, // Email verification
            'admin.ip' => \App\Http\Middleware\AdminIpFilter::class, // IP address filter
            'front.ip' => \App\Http\Middleware\FrontIpFilter::class, // Front IP filter
            'log.admin.activity' => \App\Http\Middleware\LogAdminActivity::class, // Admin panel operation log
            'check.menu.access' => \App\Http\Middleware\CheckMenuAccess::class, // Admin panel menu access permission
            'check.menu.edit' => \App\Http\Middleware\CheckMenuEdit::class, // Admin panel menu edit permission
            'install.steps' => \App\Http\Middleware\CheckInstallationSteps::class, // Installation step check
            'auth.api' => \App\Http\Middleware\AuthenticateApiKey::class, // API key authentication
            'throttle.api' => \App\Http\Middleware\ThrottleApiRequest::class, // API rate limit
            'log.api' => \App\Http\Middleware\LogApiRequest::class, // API request log
            'role' => \App\Http\Middleware\CheckRole::class, // Role check
            'permission' => \App\Http\Middleware\CheckPermission::class, // Permission check
        ]);

        // For plugin API (API key authentication + rate limit + log)
        $middleware->group('plugin.api', [
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\CheckLockdown::class,
            \App\Http\Middleware\AuthenticateApiKey::class,
            \App\Http\Middleware\ThrottleApiRequest::class,
            \App\Http\Middleware\LogApiRequest::class,
        ]);

        // For public plugin API (no authentication required, rate limit + log only)
        $middleware->group('plugin.api.public', [
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\CheckLockdown::class,
            \App\Http\Middleware\ThrottleApiRequest::class,
            \App\Http\Middleware\LogApiRequest::class,
        ]);

        // Middleware group for plugin (basic)
        $middleware->group('plugin', [
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        // For plugin frontend (IP restriction enforced)
        $middleware->group('plugin.web', [
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\FrontIpFilter::class, // Enforce IP restriction
        ]);

        // For plugin admin panel (authentication + IP restriction enforced)
        $middleware->group('plugin.admin', [
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\Authenticate::class.':member', // Enforce authentication
            \App\Http\Middleware\AdminIpFilter::class, // Enforce IP restriction
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
        $exceptions->render(function (\Throwable $e, Request $request) use ($isApi) {
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
