<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

namespace App\DTO\Logging;

use Illuminate\Http\Request;
use JsonSerializable;

/**
 * Log Context DTO
 *
 * Immutable data object that holds context information for log output
 */
final readonly class LogContextDTO implements JsonSerializable
{
    /**
     * @param  string|null  $userId  User ID
     * @param  string|null  $userType  User type (member, user, guest)
     * @param  string|null  $ipAddress  IP address
     * @param  string|null  $userAgent  User agent
     * @param  string|null  $url  Request URL
     * @param  string|null  $method  HTTP method
     * @param  string|null  $action  Operation
     * @param  string|null  $source  Source (core, plugin slug)
     * @param  array<string,mixed>  $details  Details
     * @param  array<string,mixed>  $meta  Metadata
     */
    public function __construct(
        public ?string $userId = null,
        public ?string $userType = null,
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
        public ?string $url = null,
        public ?string $method = null,
        public ?string $action = null,
        public ?string $source = 'core',
        public array $details = [],
        public array $meta = [],
    ) {}

    /**
     * Generate context from request
     *
     * @param  Request|null  $request  Request
     * @param  string|null  $userId  User ID
     * @param  string|null  $userType  User type
     * @param  string  $source  Source
     */
    public static function fromRequest(
        ?Request $request = null,
        ?string $userId = null,
        ?string $userType = null,
        string $source = 'core'
    ): self {
        $request = $request ?? request();

        return new self(
            userId: $userId,
            userType: $userType,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
            url: $request->fullUrl(),
            method: $request->method(),
            source: $source,
        );
    }

    /**
     * Generate administrator context
     *
     * @param  int|string|null  $memberId  Member ID
     * @param  string  $source  Source
     */
    public static function forAdmin(?int $memberId = null, string $source = 'core'): self
    {
        $request = request();
        $member = auth()->user();

        return new self(
            userId: $memberId ? (string) $memberId : ($member ? (string) $member->id : null),
            userType: 'member',
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
            url: $request->fullUrl(),
            method: $request->method(),
            source: $source,
        );
    }

    /**
     * Generate plugin context
     *
     * @param  string  $pluginSlug  Plugin slug
     * @param  string|null  $userId  User ID
     * @param  string|null  $userType  User type
     */
    public static function forPlugin(string $pluginSlug, ?string $userId = null, ?string $userType = null): self
    {
        return self::fromRequest(null, $userId, $userType, $pluginSlug);
    }

    /**
     * Generate a new DTO with added details
     *
     * @param  array<string,mixed>  $details  Details to add
     */
    public function withDetails(array $details): self
    {
        return new self(
            userId: $this->userId,
            userType: $this->userType,
            ipAddress: $this->ipAddress,
            userAgent: $this->userAgent,
            url: $this->url,
            method: $this->method,
            action: $this->action,
            source: $this->source,
            details: array_merge($this->details, $details),
            meta: $this->meta,
        );
    }

    /**
     * Generate a new DTO with the operation set
     *
     * @param  string  $action  Operation
     */
    public function withAction(string $action): self
    {
        return new self(
            userId: $this->userId,
            userType: $this->userType,
            ipAddress: $this->ipAddress,
            userAgent: $this->userAgent,
            url: $this->url,
            method: $this->method,
            action: $action,
            source: $this->source,
            details: $this->details,
            meta: $this->meta,
        );
    }

    /**
     * Serialize to JSON format
     *
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return array_filter([
            'user_id' => $this->userId,
            'user_type' => $this->userType,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'url' => $this->url,
            'method' => $this->method,
            'action' => $this->action,
            'source' => $this->source,
            'details' => $this->details,
            'meta' => $this->meta,
            'timestamp' => now()->toDateTimeString(),
        ], fn ($v) => $v !== null && $v !== []);
    }

    /**
     * Convert to array format
     *
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return $this->jsonSerialize();
    }

    /**
     * Generate DTO from array
     *
     * @param  array<string,mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            userId: $data['user_id'] ?? null,
            userType: $data['user_type'] ?? null,
            ipAddress: $data['ip_address'] ?? null,
            userAgent: $data['user_agent'] ?? null,
            url: $data['url'] ?? null,
            method: $data['method'] ?? null,
            action: $data['action'] ?? null,
            source: $data['source'] ?? 'core',
            details: $data['details'] ?? [],
            meta: $data['meta'] ?? [],
        );
    }

    /**
     * Filter sensitive information
     *
     * @param  array<string,mixed>  $data  Data to be filtered
     * @return array<string,mixed>
     */
    public static function sanitize(array $data): array
    {
        $sensitiveFields = [
            'password',
            'password_confirmation',
            'current_password',
            'new_password',
            'token',
            'csrf_token',
            '_token',
            'credit_card',
            'card_number',
            'cvv',
            'ssn',
            'secret',
            'api_key',
        ];

        $sanitized = [];
        foreach ($data as $key => $value) {
            if (in_array(strtolower($key), $sensitiveFields)) {
                $sanitized[$key] = '[FILTERED]';
            } elseif (is_array($value)) {
                $sanitized[$key] = self::sanitize($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}
