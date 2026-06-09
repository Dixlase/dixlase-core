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

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckInstallationSteps
{
    /**
     * Get step number from route name
     */
    private function getStepFromRoute($routeName)
    {
        $routeToStep = [
            'install.settings' => 'settings',
            'install.environment' => 'environment',
            'install.environment.store' => 'environment',
            'install.database' => 'database',
            'install.database.store' => 'database',
            'install.mail' => 'mail',
            'install.mail.store' => 'mail',
            'install.confirm' => 'confirm',
            'install.confirm.store' => 'confirm',
        ];

        $stepOrder = [
            'settings', // 1
            'environment', // 2
            'database', // 3
            'mail', // 4
            'confirm', // 5
            'complete', // 6
        ];

        $stepName = $routeToStep[$routeName] ?? null;
        $step = array_search($stepName, $stepOrder);

        return $step !== false ? $step + 1 : 0;
    }

    /**
     * Check if required fields for each step exist in session
     */
    private function checkStepFields($stepName, $data)
    {
        $requiredKeys = [
            'settings' => ['site_name', 'admin_account_name', 'admin_email', 'admin_password'],
            'environment' => ['app_env', 'app_url', 'app_timezone', 'admin_url'],
            'database' => ['db_connection', 'db_host', 'db_port', 'db_database', 'db_username'],
            'mail' => [],
        ];

        if (! isset($requiredKeys[$stepName])) {
            return true; // Steps not subject to checking are always considered successful
        }

        foreach ($requiredKeys[$stepName] as $field) {
            if (! isset($data[$field])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if all steps up to the specified step are completed
     *
     * @param  int  $step  Step number to check
     * @param  array  $installData  Session data
     * @return array [bool $isCompleted, int|null $firstIncompleteStep] Completion status and first incomplete step number
     */
    private function isStepCompleted($step, $installData)
    {
        $stepOrder = [
            'settings', // 1
            'environment', // 2
            'database', // 3
            'mail', // 4
            'confirm', // 5
            'complete', // 6
        ];

        // Get the actual install data, or an empty array if not set
        $data = $installData['install_data'] ?? [];

        // First, always check if the very first step (settings) is complete.
        // This handles cases where install_data exists but is incomplete.
        if (! $this->checkStepFields('settings', $data)) {
            return [false, 1];
        }

        // Check up to the step immediately before the specified step
        for ($i = 0; $i < ($step - 1); $i++) {
            $stepName = $stepOrder[$i];
            if (! $this->checkStepFields($stepName, $data)) {
                return [false, $i + 1];
            }
        }

        return [true, null];
    }

    /**
     * Get route name corresponding to step number
     */
    private function getRouteForStep($step)
    {
        $routes = [
            1 => 'install.settings',     // Basic settings
            2 => 'install.environment', // Environment settings
            3 => 'install.database',  // Database settings
            4 => 'install.mail',  // Mail settings
            5 => 'install.confirm',   // Confirmation screen
            6 => 'install.complete',   // completion screen
        ];

        return $routes[$step] ?? 'install.index';
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // print_r(session()->all());
        // Get current route name
        $currentRoute = $request->route() ? $request->route()->getName() : null;

        if (! $currentRoute) {
            return $next($request);
        }

        $currentRoute = $request->route()->getName();

        // Allow completion page if installation completed flag exists
        if ($currentRoute === 'install.complete') {
            return $next($request);
        }

        // Always allow index page and mode selection page
        if (in_array($currentRoute, ['install.index', 'install.mode', 'install.mode.store'])) {
            return $next($request);
        }

        // Get current step
        $currentStep = $this->getStepFromRoute($currentRoute);

        if ($currentStep > 0) {
            $installData = session()->all();

            // Skip step check before validation if request is POST
            if ($request->isMethod('post')) {
                return $next($request);
            }

            // Skip check if current step is 1 (basic settings)
            if ($currentStep === 1) {
                return $next($request);
            }

            // Check if all steps up to the current step are completed
            [$allStepsCompleted, $firstIncompleteStep] = $this->isStepCompleted($currentStep, $installData);

            // If there are incomplete steps, redirect to the first incomplete step
            if (! $allStepsCompleted) {
                $targetRoute = $this->getRouteForStep($firstIncompleteStep);
                // Redirect only if different from the current route
                if ($currentRoute !== $targetRoute) {
                    return redirect()->route($targetRoute)
                        ->with('error', __('install/common.please_complete_previous_steps'));
                }
            }
        }

        return $next($request);
    }
}
