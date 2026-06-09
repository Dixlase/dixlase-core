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

namespace App\DTO\PluginIntegration;

use JsonSerializable;

/**
 * CAPTCHA form definition DTO
 *
 * Immutable data object for plugins to declare forms where CAPTCHA validation should be enabled.
 * Passed to Core's CaptchaService via CaptchaFormProviderInterface,
 * used for CAPTCHA settings UI in the admin panel and managing enabled state in the database.
 *
 * Pass a unique identifier within the plugin for key (e.g., 'inquiry_contact', 'user_login').
 * The final form key will be aggregated in the format "{plugin_slug}.{key}".
 */
final readonly class CaptchaFormDTO implements JsonSerializable
{
    /**
     * @param  string  $key  Unique form identifier within the plugin (e.g., 'inquiry_contact')
     * @param  string  $name  Translation key for display name (e.g., 'dixlase-inquiry::captcha.forms.inquiry_contact')
     * @param  string  $route  Route name for form submission (e.g., 'inquiry.send')
     * @param  string  $category  Category for UI grouping (e.g., 'contact', 'users')
     * @param  bool  $defaultEnabled  Default enabled state on initial registration
     * @param  int  $priority  Display order (smaller values appear first)
     */
    public function __construct(
        public string $key,
        public string $name,
        public string $route,
        public string $category,
        public bool $defaultEnabled = false,
        public int $priority = 1000,
    ) {}

    /**
     * Serialize to JSON format
     *
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'key' => $this->key,
            'name' => $this->name,
            'route' => $this->route,
            'category' => $this->category,
            'default_enabled' => $this->defaultEnabled,
            'priority' => $this->priority,
        ];
    }

    /**
     * Convert to array format
     *
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return $this->jsonSerialize();
    }

    /**
     * Create DTO from array
     *
     * @param  array<string,mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            key: (string) ($data['key'] ?? ''),
            name: (string) ($data['name'] ?? ''),
            route: (string) ($data['route'] ?? ''),
            category: (string) ($data['category'] ?? 'general'),
            defaultEnabled: (bool) ($data['default_enabled'] ?? false),
            priority: (int) ($data['priority'] ?? 1000),
        );
    }
}
