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

namespace App\Enums\PluginPrivacy;

/**
 * How a privacy data provider should erase user data.
 *
 * Used by PrivacyDataProviderInterface::deleteUserData() to indicate
 * the deletion strategy. Each provider decides how to apply the mode
 * to its own tables; some providers may not support every mode and
 * should report the unsupported case via UserDataDeletionDTO::$errors.
 */
enum DeletionMode: string
{
    /**
     * Physically remove rows from the database.
     */
    case HardDelete = 'hard_delete';

    /**
     * Mark rows as deleted via a soft-delete column (e.g. deleted_at).
     * Providers without soft-delete support should fall back to HardDelete
     * or report the limitation in the result DTO.
     */
    case SoftDelete = 'soft_delete';

    /**
     * Replace identifying fields (email, name, phone, IP, etc.) with
     * irreversible HMAC-SHA256 hashes derived from app.key, while keeping
     * the row in place. Used when historical aggregates must be preserved
     * but the subject must no longer be identifiable.
     */
    case Anonymize = 'anonymize';

    /**
     * Translation key for the mode label, resolved against
     * lang/{locale}/admin/privacy/users.php.
     */
    public function translationKey(): string
    {
        return 'admin/privacy/users.deletion_mode.'.$this->value;
    }
}
