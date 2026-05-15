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
 *       (see LICENSE.commercial, or contact info@dixlase.org).
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

namespace App\DTO\Plugin;

use App\Contracts\Plugin\PluginCapabilityInterface;
use JsonSerializable;

/**
 * DTO for capability resolution result
 *
 * Holds the result of capability resolution by PluginServiceResolver.
 * Contains the instance on successful resolution, or the reason on failure.
 */
final readonly class CapabilityResolutionResult implements JsonSerializable
{
    /**
     * @param  bool  $resolved  Whether resolution succeeded
     * @param  PluginCapabilityInterface|null  $instance  Resolved instance
     * @param  string|null  $pluginSlug  Target plugin slug
     * @param  string|null  $failureReason  Failure reason
     * @param  string|null  $deniedPermission  Denied permission key
     */
    public function __construct(
        public bool $resolved,
        public ?PluginCapabilityInterface $instance = null,
        public ?string $pluginSlug = null,
        public ?string $failureReason = null,
        public ?string $deniedPermission = null,
    ) {}

    /**
     * Create a successful resolution result
     */
    public static function success(PluginCapabilityInterface $instance): self
    {
        return new self(
            resolved: true,
            instance: $instance,
            pluginSlug: $instance->getPluginSlug(),
        );
    }

    /**
     * Create a failure result due to insufficient permission
     */
    public static function permissionDenied(string $pluginSlug, string $permission): self
    {
        return new self(
            resolved: false,
            pluginSlug: $pluginSlug,
            failureReason: 'permission_denied',
            deniedPermission: $permission,
        );
    }

    /**
     * Create a failure result due to unavailable capability
     */
    public static function unavailable(string $pluginSlug): self
    {
        return new self(
            resolved: false,
            pluginSlug: $pluginSlug,
            failureReason: 'capability_unavailable',
        );
    }

    /**
     * Create a failure result due to implementation not found
     */
    public static function notFound(?string $pluginSlug = null): self
    {
        return new self(
            resolved: false,
            pluginSlug: $pluginSlug,
            failureReason: 'not_found',
        );
    }

    /**
     * Whether resolution succeeded
     */
    public function isResolved(): bool
    {
        return $this->resolved;
    }

    /**
     * Whether it failed due to insufficient permission
     */
    public function isPermissionDenied(): bool
    {
        return $this->failureReason === 'permission_denied';
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'resolved' => $this->resolved,
            'plugin_slug' => $this->pluginSlug,
            'failure_reason' => $this->failureReason,
            'denied_permission' => $this->deniedPermission,
        ];
    }
}
