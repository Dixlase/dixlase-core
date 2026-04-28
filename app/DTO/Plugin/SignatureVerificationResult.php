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
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact office@exc-d.com).
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

namespace App\DTO\Plugin;

use JsonSerializable;

/**
 * 署名検証結果DTO
 *
 * コア側の署名検証結果を表現します。
 * DixlaseDevKit プラグインの SignatureResult から変換、
 * またはスタブ実装から直接生成されます。
 */
final readonly class SignatureVerificationResult implements JsonSerializable
{
    /**
     * ステータス定数
     */
    public const STATUS_VALID = 'valid';

    public const STATUS_INVALID = 'invalid';

    public const STATUS_UNSIGNED = 'unsigned';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_UNKNOWN_KEY = 'unknown_key';

    public const STATUS_ERROR = 'error';

    public const STATUS_PENDING = 'pending_verification';

    /**
     * @param  string  $status  検証ステータス
     * @param  string|null  $type  署名タイプ（official, verified, partner）
     * @param  string|null  $signedBy  署名者
     * @param  string|null  $signedAt  署名日時
     * @param  string|null  $keyId  鍵ID
     * @param  string|null  $keyLabel  鍵ラベル
     * @param  string|null  $message  メッセージ
     * @param  array  $errors  エラー一覧
     */
    public function __construct(
        public string $status,
        public ?string $type = null,
        public ?string $signedBy = null,
        public ?string $signedAt = null,
        public ?string $keyId = null,
        public ?string $keyLabel = null,
        public ?string $message = null,
        public array $errors = [],
    ) {}

    /**
     * 署名が有効かどうか
     */
    public function isValid(): bool
    {
        return $this->status === self::STATUS_VALID;
    }

    /**
     * 未署名かどうか
     */
    public function isUnsigned(): bool
    {
        return $this->status === self::STATUS_UNSIGNED;
    }

    /**
     * 署名が無効かどうか（改ざんの可能性）
     */
    public function isInvalid(): bool
    {
        return $this->status === self::STATUS_INVALID;
    }

    /**
     * 未署名の結果を生成
     */
    public static function unsigned(?string $message = null): self
    {
        return new self(
            status: self::STATUS_UNSIGNED,
            message: $message ?? '署名がありません。',
        );
    }

    /**
     * 検証保留の結果を生成
     */
    public static function pending(?string $message = null): self
    {
        return new self(
            status: self::STATUS_PENDING,
            message: $message ?? '署名検証モジュールが利用できません。',
        );
    }

    /**
     * 有効な署名結果を生成
     */
    public static function valid(string $keyId, ?string $keyLabel = null, ?string $signedBy = null, ?string $signedAt = null, ?string $type = null): self
    {
        return new self(
            status: self::STATUS_VALID,
            type: $type,
            signedBy: $signedBy,
            signedAt: $signedAt,
            keyId: $keyId,
            keyLabel: $keyLabel,
            message: '署名は有効です。',
        );
    }

    /**
     * 無効な署名結果を生成
     */
    public static function invalid(?string $message = null, array $errors = []): self
    {
        return new self(
            status: self::STATUS_INVALID,
            message: $message ?? '署名が無効です。',
            errors: $errors,
        );
    }

    /**
     * エラー結果を生成
     */
    public static function error(string $message, array $errors = []): self
    {
        return new self(
            status: self::STATUS_ERROR,
            message: $message,
            errors: $errors,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'type' => $this->type,
            'signed_by' => $this->signedBy,
            'signed_at' => $this->signedAt,
            'key_id' => $this->keyId,
            'key_label' => $this->keyLabel,
            'message' => $this->message,
            'errors' => $this->errors,
        ];
    }
}
