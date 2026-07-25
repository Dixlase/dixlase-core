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

namespace App\DTO\Mail;

use JsonSerializable;

/**
 * Mail sending result DTO
 *
 * Immutable data object that holds the result of mail sending
 */
final readonly class MailResultDTO implements JsonSerializable
{
    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    public const STATUS_QUEUED = 'queued';

    /**
     * @param  bool  $success  Whether it succeeded
     * @param  string  $status  Status (success, failed, queued)
     * @param  string|null  $message  Message
     * @param  string|null  $messageId  Message ID (on successful sending)
     * @param  string|null  $error  Error message (on failure)
     * @param  string|null  $errorCode  Error code (on failure)
     * @param  array<string>  $recipients  Recipient
     * @param  string  $sentAt  Sent at
     * @param  array<string,mixed>  $meta  Metadata
     */
    public function __construct(
        public bool $success,
        public string $status,
        public ?string $message = null,
        public ?string $messageId = null,
        public ?string $error = null,
        public ?string $errorCode = null,
        public array $recipients = [],
        public string $sentAt = '',
        public array $meta = [],
    ) {}

    /**
     * Generate success result
     *
     * @param  array<string>  $recipients  Recipient
     * @param  string|null  $messageId  Message ID
     * @param  string|null  $message  Message
     */
    public static function success(array $recipients, ?string $messageId = null, ?string $message = null): self
    {
        return new self(
            success: true,
            status: self::STATUS_SUCCESS,
            message: $message ?? __('mail.send_success'),
            messageId: $messageId,
            recipients: $recipients,
            sentAt: now()->toIso8601String(),
        );
    }

    /**
     * Generate failure result
     *
     * @param  string  $error  Error message
     * @param  string|null  $errorCode  Error code
     * @param  array<string>  $recipients  Recipient
     */
    public static function failed(string $error, ?string $errorCode = null, array $recipients = []): self
    {
        return new self(
            success: false,
            status: self::STATUS_FAILED,
            error: $error,
            errorCode: $errorCode,
            recipients: $recipients,
            sentAt: now()->toIso8601String(),
        );
    }

    /**
     * Generate queue result
     *
     * @param  array<string>  $recipients  Recipient
     * @param  string|null  $message  Message
     */
    public static function queued(array $recipients, ?string $message = null): self
    {
        return new self(
            success: true,
            status: self::STATUS_QUEUED,
            message: $message ?? __('mail.queued_success'),
            recipients: $recipients,
            sentAt: now()->toIso8601String(),
        );
    }

    /**
     * Whether it succeeded
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Whether it failed
     */
    public function isFailed(): bool
    {
        return ! $this->success;
    }

    /**
     * Whether it was queued
     */
    public function isQueued(): bool
    {
        return $this->status === self::STATUS_QUEUED;
    }

    /**
     * Serialize to JSON format
     *
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return array_filter([
            'success' => $this->success,
            'status' => $this->status,
            'message' => $this->message,
            'message_id' => $this->messageId,
            'error' => $this->error,
            'error_code' => $this->errorCode,
            'recipients' => $this->recipients,
            'sent_at' => $this->sentAt,
            'meta' => $this->meta,
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
}
