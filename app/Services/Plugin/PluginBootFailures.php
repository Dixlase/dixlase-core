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

declare(strict_types=1);

namespace App\Services\Plugin;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * The plugins that could not register or boot on this request
 *
 * A plugin provider that throws is contained rather than allowed to stop the
 * application (see {@see \App\Providers\GuardedPluginProvider}). The failure
 * still has to be visible, so it is collected here for the request, written
 * to the log, and recorded in the audit log once an hour per plugin -- often
 * enough for an operator to find it, rarely enough not to flood the log of a
 * site that is serving every request with the same broken plugin.
 *
 * @api Stable API available for plugins/themes
 */
class PluginBootFailures
{
    /**
     * @var array<string, array{plugin: string, provider: string, phase: string, message: string, class: string}>
     */
    private array $failures = [];

    /**
     * Record a provider that threw, and make it findable
     */
    public function record(string $plugin, string $provider, string $phase, Throwable $e): void
    {
        $this->failures[$plugin] = [
            'plugin' => $plugin,
            'provider' => $provider,
            'phase' => $phase,
            'message' => $e->getMessage(),
            'class' => $e::class,
        ];

        Log::error('Plugin provider failed to '.$phase.'; the plugin is disabled for this request', [
            'plugin' => $plugin,
            'provider' => $provider,
            'phase' => $phase,
            'exception' => $e::class,
            'message' => $e->getMessage(),
            'file' => $e->getFile().':'.$e->getLine(),
        ]);

        $this->audit($plugin, $provider, $phase, $e);
    }

    /**
     * @return array<string, array{plugin: string, provider: string, phase: string, message: string, class: string}>
     */
    public function all(): array
    {
        return $this->failures;
    }

    /**
     * Forget a plugin's failure because a later attempt succeeded
     *
     * Core offers a provider from two places and the earlier one runs before
     * some core bindings exist, so a first failure can be followed by a
     * working registration. Reporting the plugin as broken after it loaded
     * would send an operator after a problem that is not there.
     */
    public function forget(string $plugin): void
    {
        unset($this->failures[$plugin]);
    }

    public function any(): bool
    {
        return $this->failures !== [];
    }

    /**
     * @return list<string>
     */
    public function plugins(): array
    {
        return array_keys($this->failures);
    }

    /**
     * Write one audit row per plugin per hour
     *
     * Everything here is best effort and must not throw: this runs while the
     * application is still coming up, so the cache or the database may not be
     * usable yet, and a failure to report a broken plugin must not become a
     * second broken thing.
     */
    private function audit(string $plugin, string $provider, string $phase, Throwable $e): void
    {
        try {
            $key = 'plugin-boot-failure:'.$plugin.':'.$phase;
            if (Cache::get($key) !== null) {
                return;
            }
            Cache::put($key, true, now()->addHour());

            \App\Facades\Audit::logExtension(\App\Models\AuditLog::ACTION_PLUGIN_BOOT_FAILED, [
                'outcome' => \App\Models\AuditLog::OUTCOME_FAILURE,
                'severity' => \App\Models\AuditLog::SEVERITY_CRITICAL,
                'context' => [
                    'plugin' => $plugin,
                    'provider' => $provider,
                    'phase' => $phase,
                    'exception' => $e::class,
                    'message' => $e->getMessage(),
                ],
            ]);
        } catch (Throwable) {
            // The log line above is the floor. Reporting must never be the
            // thing that takes the request down.
        }
    }
}
