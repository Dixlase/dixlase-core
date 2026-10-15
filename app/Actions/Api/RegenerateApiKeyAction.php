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
 * Replace an API key with a new one carrying the same settings.
 *
 * This is a create and a delete in one operation, so the row names both ids:
 * the trail has to show which credential stopped working and which one took
 * over. The plain key comes back through `metadata['plain_key']`.
 */
final class RegenerateApiKeyAction extends AbstractApiKeyAction
{
    protected function requiredPermission(): Permission
    {
        return Permission::API_KEYS_CREATE;
    }

    protected function auditAction(): string
    {
        return AuditLog::ACTION_API_KEY_REGENERATED;
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

        $createdBy = $actor->getActorId();

        $generated = ApiKey::generate(
            name: $apiKey->name,
            environment: $apiKey->environment,
            scopes: $apiKey->scopes ?? [],
            createdBy: is_int($createdBy) ? $createdBy : null,
            options: [
                'rate_limit' => $apiKey->rate_limit,
                'allowed_ips' => $apiKey->allowed_ips,
                'expires_at' => $apiKey->expires_at,
                'description' => $apiKey->description,
            ],
        );

        $oldId = $apiKey->id;
        $apiKey->delete();

        return ActionResult::success(
            model: $generated['model'],
            label: $generated['model']->name,
            metadata: [
                'plain_key' => $generated['plain_key'],
                'old_api_key_id' => $oldId,
            ],
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
            'old_api_key_id' => $result->metadata['old_api_key_id'] ?? null,
            'new_api_key_id' => $key->id,
            'name' => $key->name,
            'environment' => $key->environment,
            'scopes' => $key->scopes ?? [],
        ];
    }
}
