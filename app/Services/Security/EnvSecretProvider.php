<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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

namespace App\Services\Security;

use App\Contracts\Security\SecretProviderInterface;

/**
 * Default secret provider: reads from the local `.env` via Laravel's env() helper.
 *
 * Acts as the Phase 1 stand-in until the Zero Trust roadmap delivers
 * managed-secret-store integrations (Vault, AWS KMS, GCP Secret Manager).
 * Operators who require rotation, audit, or KMS-backed keys can rebind
 * {@see SecretProviderInterface} to a plugin-supplied implementation.
 */
final class EnvSecretProvider implements SecretProviderInterface
{
    public function get(string $key, ?string $default = null): ?string
    {
        $value = env($key, $default);

        if ($value === null || $value === false) {
            return null;
        }

        return (string) $value;
    }
}
