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

namespace App\Contracts;

/**
 * CSP Policy Provider Interface
 *
 * Interface for plugins and themes to provide CSP policies.
 * By implementing this interface, extensions can add external resources
 * they require to the CSP.
 *
 * @example
 * class MyPluginServiceProvider implements CspPolicyProvider
 * {
 *     public function getCspDirectives(): array
 *     {
 *         return [
 *             'script-src' => ['https://cdn.example.com'],
 *             'style-src' => ['https://fonts.googleapis.com'],
 *             'connect-src' => ['https://api.example.com'],
 *         ];
 *     }
 * }
 */
interface CspPolicyProvider
{
    /**
     * Get CSP directives.
     *
     * @return array<string, array<string>> Map of directive name => list of allowed sources
     *
     * Available directives:
     * - default-src: Default fallback
     * - script-src: Script sources
     * - style-src: Style sources
     * - img-src: Image sources
     * - font-src: Font sources
     * - connect-src: Connection targets (XHR, fetch, WebSocket, etc.)
     * - media-src: Media sources (audio, video)
     * - object-src: Object sources (plugin, embed, object)
     * - frame-src: Frame sources
     * - frame-ancestors: Frame ancestors
     * - form-action: Form action targets
     * - base-uri: Base URI
     * - manifest-src: Manifest sources
     * - worker-src: Worker sources
     */
    public function getCspDirectives(): array;
}
