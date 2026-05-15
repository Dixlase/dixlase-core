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

declare(strict_types=1);

namespace App\DTO\PluginPrivacy;

use App\Enums\PluginPrivacy\DeletionMode;
use JsonSerializable;

/**
 * Result of a single privacy data provider's deleteUserData() call.
 *
 * The aggregator (UserPrivacyEraser) collects one DTO per provider. The
 * eraser does not abort on per-provider failures: a provider that cannot
 * complete should return a DTO with non-empty $errors so the eraser can
 * continue with the remaining providers and surface the partial outcome
 * to the operator.
 *
 * $deletedRecords and $anonymizedRecords are reported separately so the
 * UI can distinguish "X rows physically removed" from "Y rows kept but
 * anonymized" without inspecting $mode.
 */
final readonly class UserDataDeletionDTO implements JsonSerializable
{
    /**
     * @param  string  $providerKey  Stable identifier (typically the plugin slug).
     * @param  DeletionMode  $mode  The mode that was requested by the caller.
     * @param  int  $deletedRecords  Number of rows physically or soft-deleted.
     * @param  int  $anonymizedRecords  Number of rows whose identifying fields
     *                                  were replaced with irreversible hashes.
     * @param  array<string>  $errors  Per-provider failure messages. Empty
     *                                 array means the provider completed cleanly.
     */
    public function __construct(
        public string $providerKey,
        public DeletionMode $mode,
        public int $deletedRecords = 0,
        public int $anonymizedRecords = 0,
        public array $errors = [],
    ) {}

    /**
     * Whether the provider reported any failures for this subject.
     */
    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }

    /**
     * Total rows touched by this provider, regardless of strategy.
     */
    public function totalAffected(): int
    {
        return $this->deletedRecords + $this->anonymizedRecords;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'provider_key' => $this->providerKey,
            'mode' => $this->mode->value,
            'deleted_records' => $this->deletedRecords,
            'anonymized_records' => $this->anonymizedRecords,
            'errors' => $this->errors,
        ];
    }
}
