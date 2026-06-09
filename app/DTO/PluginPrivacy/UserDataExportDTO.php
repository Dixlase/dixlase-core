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

declare(strict_types=1);

namespace App\DTO\PluginPrivacy;

use JsonSerializable;

/**
 * Result of a single privacy data provider's exportUserData() call.
 *
 * The aggregator (UserPrivacyExporter) collects one DTO per provider and
 * writes them into a ZIP archive under the providerKey directory:
 *
 *   {providerKey}/data.json   <- $data, JSON-encoded
 *   {providerKey}/files/...   <- entries from $files
 *
 * Providers that have no data for the requested user/site MUST return an
 * empty DTO (data: [], files: []) rather than throwing. Optional
 * informational messages (e.g. "provider is not site-scoped") go into
 * $warnings so the caller can surface them to operators.
 */
final readonly class UserDataExportDTO implements JsonSerializable
{
    /**
     * @param  string  $providerKey  Stable identifier (typically the plugin slug)
     *                               used as the root directory inside the ZIP archive.
     * @param  array<string, mixed>  $data  Structured data, JSON-encoded as data.json.
     *                                      Should be a plain associative array
     *                                      (no objects, no resources) so it round-trips
     *                                      cleanly through json_encode/json_decode.
     * @param  array<string, string>  $files  Map of relative ZIP path => absolute
     *                                        source file path. Example:
     *                                        ['avatars/1.jpg' => '/var/www/.../1.jpg'].
     * @param  array<string>  $warnings  Operator-facing notes that do not constitute
     *                                   an error (e.g. site scope mismatch).
     */
    public function __construct(
        public string $providerKey,
        public array $data = [],
        public array $files = [],
        public array $warnings = [],
    ) {}

    /**
     * Whether the provider returned no data and no files for this subject.
     */
    public function isEmpty(): bool
    {
        return $this->data === [] && $this->files === [];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'provider_key' => $this->providerKey,
            'data' => $this->data,
            'files' => array_keys($this->files),
            'warnings' => $this->warnings,
        ];
    }
}
