<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
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
 * @api プラグイン/テーマから使用可能な安定APIです
 *
 * ログコンテキストDTO
 *
 * ログ出力時のコンテキスト情報を保持する不変データオブジェクトです。
 */
final readonly class LogContextDTO implements JsonSerializable
{
    /**
     * @param  string|null  $userId  ユーザーID
     * @param  string|null  $userType  ユーザータイプ（member, user, guest）
     * @param  string|null  $ipAddress  IPアドレス
     * @param  string|null  $userAgent  ユーザーエージェント
     * @param  string|null  $url  リクエストURL
     * @param  string|null  $method  HTTPメソッド
     * @param  string|null  $action  操作内容
     * @param  string|null  $source  ソース（core, プラグインスラッグ）
     * @param  array<string,mixed>  $details  詳細情報
     * @param  array<string,mixed>  $meta  メタデータ
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
     * リクエストからコンテキストを生成
     *
     * @param  Request|null  $request  リクエスト
     * @param  string|null  $userId  ユーザーID
     * @param  string|null  $userType  ユーザータイプ
     * @param  string  $source  ソース
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
     * 管理者コンテキストを生成
     *
     * @param  int|string|null  $memberId  メンバーID
     * @param  string  $source  ソース
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
     * プラグインコンテキストを生成
     *
     * @param  string  $pluginSlug  プラグインスラッグ
     * @param  string|null  $userId  ユーザーID
     * @param  string|null  $userType  ユーザータイプ
     */
    public static function forPlugin(string $pluginSlug, ?string $userId = null, ?string $userType = null): self
    {
        return self::fromRequest(null, $userId, $userType, $pluginSlug);
    }

    /**
     * 詳細情報を追加した新しいDTOを生成
     *
     * @param  array<string,mixed>  $details  追加する詳細情報
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
     * 操作を設定した新しいDTOを生成
     *
     * @param  string  $action  操作内容
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
     * JSON形式にシリアライズ
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
     * 配列形式に変換
     *
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return $this->jsonSerialize();
    }

    /**
     * 配列からDTOを生成
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
     * 機密情報をフィルタリング
     *
     * @param  array<string,mixed>  $data  フィルタ対象データ
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
