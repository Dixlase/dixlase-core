<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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

namespace App\Services\Plugin;

use App\Contracts\Plugin\PluginCapabilityInterface;
use App\DTO\Plugin\CapabilityResolutionResult;
use Illuminate\Support\Facades\Log;

/**
 * Plugin capability resolution service with permission checking
 *
 * Resolves plugin capabilities (implementations of PluginCapabilityInterface)
 * with permission checking
 *
 * Register with tags in the plugin's ServiceProvider as follows:
 * ```php
 * $this->app->tag([MyMailCapable::class], 'plugin.capabilities');
 * ```
 *
 * Usage:
 * ```php
 * $resolver = app(PluginServiceResolver::class);
 * $result = $resolver->resolve(MailCapableInterface::class);
 * ```
 */
class PluginServiceResolver
{
    /**
     * Service container tag name
     */
    public const CAPABILITY_TAG = 'plugin.capabilities';

    /**
     * Mapping of interfaces to required permissions
     *
     * @var array<class-string<PluginCapabilityInterface>, string>
     */
    protected array $permissionMap = [];

    /**
     * Manually registered capability instances
     *
     * @var array<class-string<PluginCapabilityInterface>, array<PluginCapabilityInterface>>
     */
    protected array $registered = [];

    public function __construct(
        protected PluginPermissionService $permissionService,
    ) {}

    /**
     * Register required permission for an interface
     *
     * @param  class-string<PluginCapabilityInterface>  $interface  Capability interface
     * @param  string  $permission  Required permission key (e.g., 'mail.send')
     */
    public function registerPermission(string $interface, string $permission): void
    {
        $this->permissionMap[$interface] = $permission;
    }

    /**
     * Manually register a capability instance
     *
     * @param  class-string<PluginCapabilityInterface>  $interface  Capability interface
     * @param  PluginCapabilityInterface  $instance  Implementation instance
     */
    public function register(string $interface, PluginCapabilityInterface $instance): void
    {
        $this->registered[$interface][] = $instance;
    }

    /**
     * Resolve the first plugin that implements a specific interface with permission checking
     *
     * @param  class-string<PluginCapabilityInterface>  $interface  Capability interface
     * @param  string|null  $pluginSlug  When limiting to a specific plugin
     */
    public function resolve(string $interface, ?string $pluginSlug = null): CapabilityResolutionResult
    {
        $instances = $this->getInstances($interface);
        $lastFailure = null;

        foreach ($instances as $instance) {
            if ($pluginSlug !== null && $instance->getPluginSlug() !== $pluginSlug) {
                continue;
            }

            $result = $this->checkAndWrap($interface, $instance);
            if ($result->isResolved()) {
                return $result;
            }

            $lastFailure = $result;
        }

        return $lastFailure ?? CapabilityResolutionResult::notFound($pluginSlug);
    }

    /**
     * Resolve all plugins that implement a specific interface with permission checking
     *
     * @param  class-string<PluginCapabilityInterface>  $interface  Capability interface
     * @return array<CapabilityResolutionResult>
     */
    public function resolveAll(string $interface): array
    {
        $instances = $this->getInstances($interface);
        $results = [];

        foreach ($instances as $instance) {
            $results[] = $this->checkAndWrap($interface, $instance);
        }

        return $results;
    }

    /**
     * Check if a specific interface is available
     *
     * @param  class-string<PluginCapabilityInterface>  $interface  Capability interface
     * @param  string|null  $pluginSlug  When limiting to a specific plugin
     */
    public function has(string $interface, ?string $pluginSlug = null): bool
    {
        return $this->resolve($interface, $pluginSlug)->isResolved();
    }

    /**
     * Get list of registered capability interfaces
     *
     * @return array<class-string<PluginCapabilityInterface>>
     */
    public function getRegisteredInterfaces(): array
    {
        return array_unique(array_merge(
            array_keys($this->registered),
            array_keys($this->permissionMap),
        ));
    }

    /**
     * Get all instances of a specific interface (without permission checking)
     *
     * @param  class-string<PluginCapabilityInterface>  $interface
     * @return array<PluginCapabilityInterface>
     */
    protected function getInstances(string $interface): array
    {
        $instances = $this->registered[$interface] ?? [];

        // Also retrieve from service container tags
        try {
            $tagged = app()->tagged(self::CAPABILITY_TAG);
            foreach ($tagged as $service) {
                if ($service instanceof $interface && ! $this->isDuplicate($instances, $service)) {
                    $instances[] = $service;
                }
            }
        } catch (\Throwable) {
            // Ignore if tag is not registered
        }

        return $instances;
    }

    /**
     * Perform permission check and wrap result
     *
     * @param  class-string<PluginCapabilityInterface>  $interface
     */
    protected function checkAndWrap(string $interface, PluginCapabilityInterface $instance): CapabilityResolutionResult
    {
        $pluginSlug = $instance->getPluginSlug();

        // When feature is unavailable
        if (! $instance->isCapabilityAvailable()) {
            return CapabilityResolutionResult::unavailable($pluginSlug);
        }

        // Permission check
        $requiredPermission = $this->getRequiredPermission($interface);
        if ($requiredPermission !== null && ! $this->permissionService->check($pluginSlug, $requiredPermission)) {
            Log::warning('Plugin feature permission check failed', [
                'plugin' => $pluginSlug,
                'interface' => $interface,
                'permission' => $requiredPermission,
            ]);

            return CapabilityResolutionResult::permissionDenied($pluginSlug, $requiredPermission);
        }

        return CapabilityResolutionResult::success($instance);
    }

    /**
     * Get required permission key for interface
     *
     * @param  class-string<PluginCapabilityInterface>  $interface
     */
    protected function getRequiredPermission(string $interface): ?string
    {
        // Prioritize manually registered permission map
        if (isset($this->permissionMap[$interface])) {
            return $this->permissionMap[$interface];
        }

        // Check REQUIRED_PERMISSION constant of interface
        if (defined("{$interface}::REQUIRED_PERMISSION")) {
            return constant("{$interface}::REQUIRED_PERMISSION");
        }

        return null;
    }

    /**
     * Check if duplicate instance of same plugin
     *
     * @param  array<PluginCapabilityInterface>  $existing
     */
    protected function isDuplicate(array $existing, PluginCapabilityInterface $candidate): bool
    {
        foreach ($existing as $instance) {
            if ($instance->getPluginSlug() === $candidate->getPluginSlug()
                && get_class($instance) === get_class($candidate)) {
                return true;
            }
        }

        return false;
    }
}
