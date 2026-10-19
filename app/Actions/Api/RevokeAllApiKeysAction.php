<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

namespace App\Actions\Api;

use App\Contracts\Action\Actor;
use App\DTO\Action\ActionResult;
use App\Enums\Permission;
use App\Models\ApiKey;
use App\Models\AuditLog;

/**
 * Delete every API key of the current site at once, for incident response
 * (#674).
 *
 * Keys are deleted the same way {@see RevokeApiKeyAction} deletes one, so a
 * revoked key is refused on its next request on every instance: lookups go
 * to the database with no cache in between. Network keys (no site) are
 * shared with other sites and are left alone: one site's administrator
 * must not be able to cut off the others.
 *
 * One audit row records the whole operation, naming each key by id and
 * name — never by key material.
 */
final class RevokeAllApiKeysAction extends AbstractApiKeyAction
{
    protected function requiredPermission(): Permission
    {
        return Permission::API_KEYS_DELETE;
    }

    protected function auditAction(): string
    {
        return AuditLog::ACTION_API_KEYS_REVOKED_ALL;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handle(Actor $actor, array $data): ActionResult
    {
        // The site scope already excludes network keys; whereNotNull keeps
        // them out even where the scope cannot resolve a site.
        $keys = ApiKey::query()->whereNotNull('site_id')->orderBy('id')->get();

        $keys->each(fn (ApiKey $key) => $key->delete());

        return new ActionResult(
            success: true,
            targetType: ApiKey::class,
            targetLabel: 'all',
            metadata: [
                'count' => $keys->count(),
                'keys' => $keys->map(fn (ApiKey $key) => ['id' => $key->id, 'name' => $key->name])->all(),
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function buildAuditContext(array $data, ActionResult $result): array
    {
        return [
            'count' => $result->metadata['count'],
            'keys' => $result->metadata['keys'],
        ];
    }
}
