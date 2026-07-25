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

namespace App\Contracts\PluginIntegration;

use App\Contracts\Plugin\PluginCapabilityInterface;
use App\DTO\PluginIntegration\PreviewDTO;

/**
 * Contract for plugins that provide preview data
 *
 * Enables themes to preview plugin content (forms, widgets, content blocks)
 * without directly referencing plugin internals.
 * Extends PluginCapabilityInterface for auto-discovery via PluginServiceResolver.
 */
interface PreviewProviderInterface extends PluginCapabilityInterface
{
    public const TYPE_FORM = 'form';

    public const TYPE_CONTENT = 'content';

    public const TYPE_WIDGET = 'widget';

    /**
     * Get the list of available preview keys
     *
     * @return string[] e.g. ['inquiry_form']
     */
    public function getPreviewKeys(): array;

    /**
     * Get preview data for a specific key
     *
     * @param  string  $key  Preview key
     * @return PreviewDTO|null Preview data, or null if not supported
     */
    public function getPreview(string $key): ?PreviewDTO;

    /**
     * Get all available preview data
     *
     * @return PreviewDTO[]
     */
    public function getPreviews(): array;
}
