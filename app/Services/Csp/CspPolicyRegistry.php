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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * CSP Policy Registry
 *
 * Registry for collecting and managing CSP policies from plugins and themes
 *
 * Everything that reaches the registry comes from an extension (or from core
 * code using the same extension-facing API), so every source expression is
 * checked by CspSourceValidator before it is accepted. Values that would
 * relax the policy for the whole site ('unsafe-inline', a bare `*`, `data:`
 * in script-src, ...) are dropped, logged, and recorded per source so the
 * admin can see them. The admin's own CSP settings are applied by
 * CspBuilder outside the registry and are not filtered.
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
     * Values dropped by validation, keyed by source name
     *
     * @var array<string, array<int, array{directive: string, value: string, reason: string}>>
     */
    protected array $rejected = [];

    protected ?CspSourceValidator $validator = null;

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
        $filtered = $this->validate([$directive => $values], $source ?? 'unknown');

        foreach ($filtered as $name => $accepted) {
            if (! isset($this->directives[$name])) {
                $this->directives[$name] = [];
            }

            foreach ($accepted as $value) {
                if (! in_array($value, $this->directives[$name], true)) {
                    $this->directives[$name][] = $value;
                }
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

            if (! is_array($providerDirectives)) {
                continue;
            }

            foreach ($this->validate($providerDirectives, $name) as $directive => $values) {
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
     * Values dropped by validation so far, keyed by source name
     *
     * Provider values are validated when collectDirectives() runs, so call
     * this afterwards to see them.
     *
     * @return array<string, array<int, array{directive: string, value: string, reason: string}>>
     */
    public function getRejected(): array
    {
        return $this->rejected;
    }

    /**
     * Validate extension-supplied directives, recording and logging what is dropped
     *
     * @param  array<mixed, mixed>  $directives
     * @return array<string, array<int, string>> Accepted values per directive
     */
    protected function validate(array $directives, string $source): array
    {
        $this->validator ??= new CspSourceValidator();
        $result = $this->validator->filter($directives);

        foreach ($result['rejected'] as $entry) {
            $known = $this->rejected[$source] ?? [];
            if (in_array($entry, $known, true)) {
                continue;
            }

            $this->rejected[$source][] = $entry;
            $this->logRejection($source, $entry);
        }

        return $result['accepted'];
    }

    /**
     * Log a dropped value, at most once an hour per source/directive/value
     *
     * The registry is rebuilt on every request, so an unthrottled warning
     * would repeat for every page view while the extension stays enabled.
     *
     * @param  array{directive: string, value: string, reason: string}  $entry
     */
    protected function logRejection(string $source, array $entry): void
    {
        try {
            $key = 'csp_rejected_source:'.sha1($source.'|'.$entry['directive'].'|'.$entry['value']);
            if (! Cache::add($key, true, 3600)) {
                return;
            }
        } catch (\Throwable) {
            // No cache available: log every time rather than not at all.
        }

        Log::warning('CSP source from an extension was rejected and left out of the policy', [
            'source' => $source,
            'directive' => $entry['directive'],
            'value' => $entry['value'],
            'reason' => $entry['reason'],
        ]);
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
        $this->rejected = [];
    }
}
