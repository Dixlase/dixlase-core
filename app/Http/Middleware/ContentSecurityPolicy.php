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

use App\Services\Csp\CspBuilder;
use App\Services\Csp\CspExtensionLoader;
use App\Services\Csp\CspNonceGenerator;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Content Security Policy Middleware
 *
 * Middleware that adds CSP headers to responses
 */
class ContentSecurityPolicy
{
    protected CspBuilder $builder;

    protected CspNonceGenerator $nonceGenerator;

    protected CspExtensionLoader $extensionLoader;

    protected bool $extensionsLoaded = false;

    public function __construct(
        CspBuilder $builder,
        CspNonceGenerator $nonceGenerator,
        CspExtensionLoader $extensionLoader
    ) {
        $this->builder = $builder;
        $this->nonceGenerator = $nonceGenerator;
        $this->extensionLoader = $extensionLoader;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ?string $context = null): Response
    {
        // The non-CSP security headers (X-Frame-Options, X-Content-Type-Options,
        // Referrer-Policy, Permissions-Policy) must be present on EVERY response
        // — error pages (>=400), non-HTML responses, excluded paths, and when CSP
        // is disabled. Only the Content-Security-Policy header itself is gated on
        // a successful HTML response.

        // CSP disabled: still emit the baseline security headers.
        if (! $this->builder->isEnabled()) {
            $response = $next($request);
            $this->addSecurityHeaders($response);

            return $response;
        }

        // Excluded path: skip CSP processing but still emit security headers.
        if ($this->isExcludedPath($request)) {
            $response = $next($request);
            $this->addSecurityHeaders($response);

            return $response;
        }

        // Store nonce in request (for use in Blade)
        $request->attributes->set('csp_nonce', $this->nonceGenerator->getNonce());

        // Load CSP settings from plugins and themes (once only)
        if (! $this->extensionsLoaded) {
            $this->extensionLoader->loadAll();
            $this->extensionsLoaded = true;
        }

        // Set context (admin/front)
        $context = $context ?? $this->detectContext($request);
        $this->builder->setContext($context);

        // Get response
        $response = $next($request);

        // Baseline security headers on every response (including >=400 / non-HTML).
        $this->addSecurityHeaders($response);

        // Add the CSP header only to successful HTML responses.
        if ($this->shouldAddCspHeader($response)) {
            // Add nonce to scripts inserted by Laravel Boost (development environment only)
            if (! app()->environment('production')) {
                $this->addNonceToBoostScripts($response);
            }

            $headerName = $this->builder->getHeaderName();
            $headerValue = $this->builder->build();

            // Do not send header if empty string is returned due to safe mode, etc.
            // Since empty CSP is interpreted as either "ignore" or "deny all" depending on the browser,
            // defer to default browser behavior by not adding the header itself
            if ($headerValue !== '') {
                $response->headers->set($headerName, $headerValue);

                // Route-level frame-ancestors override (for iframe preview in admin panel)
                $this->overrideFrameAncestorsIfRequested($request, $response, $headerName);
            }
        }

        return $response;
    }

    /**
     * Check if path is excluded
     */
    protected function isExcludedPath(Request $request): bool
    {
        $excludedPaths = config('csp.excluded_paths', []);
        $path = $request->path();

        foreach ($excludedPaths as $pattern) {
            // Convert wildcard pattern to regex
            $regex = str_replace(['*', '/'], ['.*', '\/'], $pattern);
            if (preg_match("/^{$regex}$/", $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Auto-detect context
     */
    protected function detectContext(Request $request): string
    {
        $path = $request->path();

        // Determine admin panel path
        $adminPath = config('admin.path', 'admin');
        if (str_starts_with($path, $adminPath) || str_starts_with($path, 'admin')) {
            return 'admin';
        }

        return 'front';
    }

    /**
     * Whether CSP header should be added
     */
    protected function shouldAddCspHeader(Response $response): bool
    {
        // Skip if status code is not successful
        if ($response->getStatusCode() >= 400) {
            return false;
        }

        // Only when Content-Type is text/html
        $contentType = $response->headers->get('Content-Type', '');
        if (str_contains($contentType, 'text/html') || empty($contentType)) {
            return true;
        }

        return false;
    }

    /**
     * Add nonce to scripts inserted by Laravel Boost
     *
     * Dynamically inserted by Laravel Boost (MCP Server) in development environment
     * Add CSP nonce to browser-logger-active script
     */
    protected function addNonceToBoostScripts(Response $response): void
    {
        $content = $response->getContent();

        if ($content === false || empty($content)) {
            return;
        }

        $nonce = $this->nonceGenerator->getNonce();

        // Add nonce to <script id="browser-logger-active">
        $pattern = '/<script\s+id=["\']browser-logger-active["\']\s*>/i';
        $replacement = '<script id="browser-logger-active" nonce="'.$nonce.'">';

        $newContent = preg_replace($pattern, $replacement, $content);

        if ($newContent !== null && $newContent !== $content) {
            $response->setContent($newContent);
        }
    }

    /**
     * Override frame-ancestors directive based on request attributes
     *
     * For routes that use iframe preview within the admin panel,
     * change frame-ancestors from 'none' to 'self'
     * Set request()->attributes->set('csp_frame_ancestors_self', true) in the controller
     */
    protected function overrideFrameAncestorsIfRequested(Request $request, Response $response, string $headerName): void
    {
        if (! $request->attributes->get('csp_frame_ancestors_self')) {
            return;
        }

        $cspHeader = $response->headers->get($headerName, '');
        if (empty($cspHeader)) {
            return;
        }

        $updatedHeader = preg_replace(
            "/frame-ancestors\s+'none'/",
            "frame-ancestors 'self'",
            $cspHeader
        );

        if ($updatedHeader !== $cspHeader) {
            $response->headers->set($headerName, $updatedHeader);
        }
    }

    /**
     * Add additional security headers
     */
    protected function addSecurityHeaders(Response $response): void
    {
        // X-Content-Type-Options: Prevent MIME type sniffing
        if (! $response->headers->has('X-Content-Type-Options')) {
            $response->headers->set('X-Content-Type-Options', 'nosniff');
        }

        // X-Frame-Options: Prevent clickjacking (used in conjunction with CSP frame-ancestors)
        if (! $response->headers->has('X-Frame-Options')) {
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        }

        // Referrer-Policy: Control referrer information
        if (! $response->headers->has('Referrer-Policy')) {
            $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        }

        // Permissions-Policy: Restrict browser features
        if (! $response->headers->has('Permissions-Policy')) {
            $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
        }
    }
}
