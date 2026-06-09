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

use App\Services\Site\SettingResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip maintenance check if installation is not complete
        // Fallback to $_SERVER / $_ENV to handle env() returning null when config is cached
        $installed = $_SERVER['INSTALLED'] ?? $_ENV['INSTALLED'] ?? env('INSTALLED') ?? config('app.installed');
        if ($installed !== 'true' && $installed !== true) {
            return $next($request);
        }

        // Admin panel and installation screen are always accessible
        if (\App\Helpers\AdminHelper::isAdminRequest($request) || $request->is('install') || $request->is('install/*')) {
            return $next($request);
        }

        // Allow preview requests
        if ($request->is('maintenance-preview')) {
            return $next($request);
        }

        // Get maintenance mode settings
        $settings = $this->getMaintenanceSettings();

        // Proceed normally if maintenance mode is disabled
        if (! $settings['maintenance_mode']) {
            return $next($request);
        }

        // Check if not yet started when start datetime is set
        if ($settings['maintenance_start_at']) {
            $startAt = \Carbon\Carbon::parse($settings['maintenance_start_at']);
            if (now()->lt($startAt)) {
                // Maintenance has not started yet
                return $next($request);
            }
        }

        // Check if already finished when end datetime is set
        if ($settings['maintenance_release_at']) {
            $releaseAt = \Carbon\Carbon::parse($settings['maintenance_release_at']);
            if (now()->gte($releaseAt)) {
                // Maintenance has finished (automatic release is handled by command)
                return $next($request);
            }
        }

        // Display maintenance screen
        return $this->showMaintenancePage($settings);
    }

    /**
     * Get maintenance settings
     */
    private function getMaintenanceSettings(): array
    {
        // maintenance_* keys are PerSite scope. SettingResolver routes
        // through site_settings filtered by the current site automatically.
        $resolver = app(SettingResolver::class);

        return [
            'maintenance_mode' => (bool) $resolver->get('maintenance_mode'),
            'maintenance_message' => $resolver->get('maintenance_message') ?: __('http/middleware/check_maintenance_mode.currently_under_maintenance_please_wait'),
            'maintenance_auto_release' => (bool) $resolver->get('maintenance_auto_release'),
            'maintenance_start_at' => $resolver->get('maintenance_start_at'),
            'maintenance_release_at' => $resolver->get('maintenance_release_at'),
        ];
    }

    /**
     * Display maintenance screen
     */
    private function showMaintenancePage(array $settings): Response
    {
        $retryAfter = null;

        // Calculate Retry-After header when auto-release is enabled and end datetime is set
        if ($settings['maintenance_auto_release'] && $settings['maintenance_release_at']) {
            $releaseAt = \Carbon\Carbon::parse($settings['maintenance_release_at']);
            $retryAfter = max(0, now()->diffInSeconds($releaseAt, false));
        }

        // Pass context to display admin bar and banner if logged in as admin member
        $member = auth()->guard('member')->user();
        $isAdmin = $member !== null;
        $appearance = $isAdmin
            ? ($member->appearance?->value ?? \App\Enums\AppearanceMode::Auto->value)
            : \App\Enums\AppearanceMode::Auto->value;

        $response = response()->view('maintenance', [
            'message' => $settings['maintenance_message'],
            'releaseAt' => $settings['maintenance_release_at'],
            'isAdmin' => $isAdmin,
            'appearance' => (string) $appearance,
        ], 503);

        // Set Retry-After header
        if ($retryAfter !== null) {
            $response->header('Retry-After', (string) $retryAfter);
        }

        return $response;
    }
}
