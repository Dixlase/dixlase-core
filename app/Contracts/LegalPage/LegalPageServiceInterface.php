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

namespace App\Contracts\LegalPage;

/**
 * Contract for legal page registry service
 *
 * Manages Core and plugin legal page types in a unified manner,
 * and provides URL retrieval, settings, required checks, etc.
 */
interface LegalPageServiceInterface
{
    /**
     * Get unified list of Core + plugin page types
     *
     * @return array<string, array{name: string, description: string, required: bool, icon: string, required_by?: list<string>}>
     */
    public function getPageTypes(): array;

    /**
     * Determine if the specified page type is required
     */
    public function isRequired(string $slug): bool;

    /**
     * Determine if the URL for the specified page type is configured
     */
    public function exists(string $slug): bool;

    /**
     * Get the URL for the specified page type
     */
    public function url(string $slug): ?string;

    /**
     * Get list of required page types with unconfigured URLs
     *
     * @return array<string, array{name: string, description: string, required: bool, icon: string}>
     */
    public function missingRequired(): array;

    /**
     * Set the URL for the specified page type (null to delete)
     */
    public function setUrl(string $slug, ?string $url): void;
}
