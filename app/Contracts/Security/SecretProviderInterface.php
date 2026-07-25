<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes (reserved hook; default implementation is a no-op)
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

namespace App\Contracts\Security;

/**
 * Pluggable secret-store backend.
 *
 * Reserved Phase 1 extension point for the Zero Trust roadmap. The default
 * implementation reads from the local `.env` file via Laravel's `env()`
 * helper. Plugins or operator integrations may rebind this contract to a
 * managed secret store (HashiCorp Vault, AWS KMS, GCP Secret Manager, …)
 * so that production deployments can rotate credentials and signing keys
 * without editing application config.
 *
 * Compatibility: the method signature below is part of the Plugin API
 * stability pledge (see PLUGIN-API.md). Adding new optional parameters or
 * separate methods is non-breaking; renaming or changing the return type
 * follows the Plugin API deprecation policy.
 *
 * Implementers must:
 *  - Return `null` when the key is unknown rather than throwing.
 *  - Return the supplied `$default` when the key is unknown and a default
 *    is provided.
 *  - Treat the key as opaque; no quoting, no parsing.
 *  - Be safe to call repeatedly (callers may not cache).
 *
 * @see \App\Services\Security\EnvSecretProvider Default implementation
 */
interface SecretProviderInterface
{
    /**
     * Retrieve the secret value bound to `$key`.
     *
     * @param  string  $key  The secret identifier (e.g. `"DB_PASSWORD"`, `"DIXLASE_SIGNER_PRIVATE_KEY"`).
     * @param  string|null  $default  Value to return when the key is unknown.
     * @return string|null The secret value, or `$default` (which may itself be null) when not found.
     */
    public function get(string $key, ?string $default = null): ?string;
}
