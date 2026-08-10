<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
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

namespace App\Services\Csp;

use App\Contracts\CspPolicyProvider;
use Illuminate\Support\Facades\Log;

/**
 * CSP Policy Registry
 *
 * Registry for collecting and managing CSP policies from plugins and themes
 */
class CspPolicyRegistry
{
    /**
     * Registered policy providers
     *
     * @var array<string, CspPolicyProvider>
     */
    protected array $providers = [];

    /**
     * Directly registered directives
     *
     * @var array<string, array<string>>
     */
    protected array $directives = [];

    /**
     * Register a policy provider
     *
     * @param  string  $name  Provider name (plugin/theme name)
     * @param  CspPolicyProvider  $provider  Provider instance
     */
    public function registerProvider(string $name, CspPolicyProvider $provider): void
    {
        $this->providers[$name] = $provider;
    }

    /**
     * Unregister a policy provider
     */
    public function unregisterProvider(string $name): void
    {
        unset($this->providers[$name]);
    }

    /**
     * Add a directive directly
     *
     * @param  string  $directive  Directive name
     * @param  array<string>  $values  Array of values
     * @param  string|null  $source  Source name (for debugging)
     */
    public function addDirective(string $directive, array $values, ?string $source = null): void
    {
        if (! isset($this->directives[$directive])) {
            $this->directives[$directive] = [];
        }

        foreach ($values as $value) {
            if (! in_array($value, $this->directives[$directive], true)) {
                $this->directives[$directive][] = $value;
            }
        }
    }

    /**
     * Add multiple directives in bulk
     *
     * @param  array<string, array<string>>  $directives
     * @param  string|null  $source  Source name (for debugging)
     */
    public function addDirectives(array $directives, ?string $source = null): void
    {
        foreach ($directives as $directive => $values) {
            $this->addDirective($directive, $values, $source);
        }
    }

    /**
     * Collect all registered directives
     *
     * @return array<string, array<string>>
     */
    public function collectDirectives(): array
    {
        $collected = $this->directives;

        // Collect directives from providers.
        //
        // A provider is third-party code reached from the CSP middleware, which
        // runs on EVERY request — so an exception here is not a CSP problem, it
        // is a site outage. That is not hypothetical: DixlaseSEO read its own
        // settings table in getCspDirectives(), and while the plugin was
        // enabled without its schema (right after install, after a failed
        // migration, mid-rollback) the resulting PDOException returned 500 for
        // every page, front pages included.
        //
        // A failing provider is therefore skipped rather than allowed to
        // propagate. The cost is narrow and known: only that provider's
        // directives are missing, so the policy is looser for the resources it
        // would have allow-listed — while every other provider still applies.
        // Serving a page under a slightly looser policy beats serving no page.
        //
        // The failure is logged rather than swallowed: a silent skip would hide
        // exactly the kind of defect this catch exists to survive.
        foreach ($this->providers as $name => $provider) {
            try {
                $providerDirectives = $provider->getCspDirectives();
            } catch (\Throwable $e) {
                // \Throwable, not \Exception: a missing class or a driver-level
                // fault arrives as an \Error and would sail past \Exception.
                Log::error('CSP provider failed; its directives are omitted from this response', [
                    'provider' => $name,
                    'exception' => $e::class,
                    'error' => $e->getMessage(),
                ]);

                continue;
            }

            foreach ($providerDirectives as $directive => $values) {
                if (! isset($collected[$directive])) {
                    $collected[$directive] = [];
                }

                foreach ($values as $value) {
                    if (! in_array($value, $collected[$directive], true)) {
                        $collected[$directive][] = $value;
                    }
                }
            }
        }

        return $collected;
    }

    /**
     * Get list of registered providers
     *
     * @return array<string>
     */
    public function getProviderNames(): array
    {
        return array_keys($this->providers);
    }

    /**
     * Clear the registry
     */
    public function clear(): void
    {
        $this->providers = [];
        $this->directives = [];
    }
}
