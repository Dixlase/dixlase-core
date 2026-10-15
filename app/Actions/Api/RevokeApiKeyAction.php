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
 * Delete an API key and record which key was deleted.
 *
 * The row is written after the delete, from the model's in-memory attributes,
 * so the key's id and name survive in the trail.
 */
final class RevokeApiKeyAction extends AbstractApiKeyAction
{
    protected function requiredPermission(): Permission
    {
        return Permission::API_KEYS_DELETE;
    }

    protected function auditAction(): string
    {
        return AuditLog::ACTION_API_KEY_REVOKED;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handle(Actor $actor, array $data): ActionResult
    {
        $apiKey = $data['api_key'] ?? null;

        if (! $apiKey instanceof ApiKey) {
            return ActionResult::failure('An ApiKey model is required in api_key');
        }

        $apiKey->delete();

        return ActionResult::success(
            model: $apiKey,
            label: $apiKey->name,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function buildAuditContext(array $data, ActionResult $result): array
    {
        /** @var ApiKey $key */
        $key = $result->model;

        return [
            'api_key_id' => $key->id,
            'name' => $key->name,
            'environment' => $key->environment,
            'scopes' => $key->scopes ?? [],
        ];
    }
}
