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
 * Create an API key and record its creation.
 *
 * The plain key is returned to the caller through
 * `ActionResult::$metadata['plain_key']` because the screen shows it once and
 * never again. It is deliberately absent from the audit context.
 */
final class GenerateApiKeyAction extends AbstractApiKeyAction
{
    protected function requiredPermission(): Permission
    {
        return Permission::API_KEYS_CREATE;
    }

    protected function auditAction(): string
    {
        return AuditLog::ACTION_API_KEY_CREATED;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handle(Actor $actor, array $data): ActionResult
    {
        $createdBy = $actor->getActorId();

        $result = ApiKey::generate(
            name: (string) $data['name'],
            environment: (string) $data['environment'],
            scopes: (array) ($data['scopes'] ?? []),
            createdBy: is_int($createdBy) ? $createdBy : null,
            options: [
                'rate_limit' => $data['rate_limit'] ?? null,
                'allowed_ips' => self::parseAllowedIps($data['allowed_ips'] ?? null),
                'expires_at' => $data['expires_at'] ?? null,
                'description' => $data['description'] ?? null,
            ],
        );

        return ActionResult::success(
            model: $result['model'],
            label: $result['model']->name,
            metadata: ['plain_key' => $result['plain_key']],
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
            'rate_limit' => $key->rate_limit,
            'expires_at' => $key->expires_at?->toIso8601String(),
            'allowed_ips' => $key->allowed_ips ?? [],
        ];
    }

    /**
     * Turn the comma-separated form field into a list, or null when empty.
     *
     * @return list<string>|null
     */
    public static function parseAllowedIps(mixed $value): ?array
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $ips = array_values(array_filter(array_map('trim', explode(',', $value))));

        return $ips === [] ? null : $ips;
    }
}
