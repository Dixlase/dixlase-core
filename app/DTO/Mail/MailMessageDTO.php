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

namespace App\DTO\Mail;

use JsonSerializable;

/**
 * メールメッセージDTO
 *
 * メール送信に必要な情報を保持する不変データオブジェクトです。
 */
final readonly class MailMessageDTO implements JsonSerializable
{
    /**
     * @param  string|array<string>  $to  宛先メールアドレス
     * @param  string  $subject  件名
     * @param  string  $body  本文（HTMLまたはテキスト）
     * @param  bool  $isHtml  HTML形式かどうか
     * @param  string|null  $from  送信元メールアドレス
     * @param  string|null  $fromName  送信元名
     * @param  string|null  $replyTo  返信先メールアドレス
     * @param  array<string>  $cc  CCメールアドレス
     * @param  array<string>  $bcc  BCCメールアドレス
     * @param  array<MailAttachmentDTO>  $attachments  添付ファイル
     * @param  array<string,mixed>  $headers  カスタムヘッダー
     * @param  array<string,mixed>  $meta  メタデータ（ログ用など）
     */
    public function __construct(
        public string|array $to,
        public string $subject,
        public string $body,
        public bool $isHtml = true,
        public ?string $from = null,
        public ?string $fromName = null,
        public ?string $replyTo = null,
        public array $cc = [],
        public array $bcc = [],
        public array $attachments = [],
        public array $headers = [],
        public array $meta = [],
    ) {}

    /**
     * 宛先を配列で取得
     *
     * @return array<string>
     */
    public function getRecipients(): array
    {
        return is_array($this->to) ? $this->to : [$this->to];
    }

    /**
     * 添付ファイルがあるか
     */
    public function hasAttachments(): bool
    {
        return ! empty($this->attachments);
    }

    /**
     * CCがあるか
     */
    public function hasCc(): bool
    {
        return ! empty($this->cc);
    }

    /**
     * BCCがあるか
     */
    public function hasBcc(): bool
    {
        return ! empty($this->bcc);
    }

    /**
     * JSON形式にシリアライズ
     *
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'to' => $this->to,
            'subject' => $this->subject,
            'body' => $this->body,
            'is_html' => $this->isHtml,
            'from' => $this->from,
            'from_name' => $this->fromName,
            'reply_to' => $this->replyTo,
            'cc' => $this->cc,
            'bcc' => $this->bcc,
            'attachments_count' => count($this->attachments),
            'headers' => $this->headers,
            'meta' => $this->meta,
        ];
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
            to: $data['to'],
            subject: $data['subject'],
            body: $data['body'],
            isHtml: $data['is_html'] ?? true,
            from: $data['from'] ?? null,
            fromName: $data['from_name'] ?? null,
            replyTo: $data['reply_to'] ?? null,
            cc: $data['cc'] ?? [],
            bcc: $data['bcc'] ?? [],
            attachments: $data['attachments'] ?? [],
            headers: $data['headers'] ?? [],
            meta: $data['meta'] ?? [],
        );
    }

    /**
     * 件名を変更した新しいDTOを生成
     *
     * @param  string  $subject  新しい件名
     */
    public function withSubject(string $subject): self
    {
        return new self(
            to: $this->to,
            subject: $subject,
            body: $this->body,
            isHtml: $this->isHtml,
            from: $this->from,
            fromName: $this->fromName,
            replyTo: $this->replyTo,
            cc: $this->cc,
            bcc: $this->bcc,
            attachments: $this->attachments,
            headers: $this->headers,
            meta: $this->meta,
        );
    }

    /**
     * 宛先を変更した新しいDTOを生成
     *
     * @param  string|array<string>  $to  新しい宛先
     */
    public function withTo(string|array $to): self
    {
        return new self(
            to: $to,
            subject: $this->subject,
            body: $this->body,
            isHtml: $this->isHtml,
            from: $this->from,
            fromName: $this->fromName,
            replyTo: $this->replyTo,
            cc: $this->cc,
            bcc: $this->bcc,
            attachments: $this->attachments,
            headers: $this->headers,
            meta: $this->meta,
        );
    }

    /**
     * メタデータを追加した新しいDTOを生成
     *
     * @param  array<string,mixed>  $meta  追加するメタデータ
     */
    public function withMeta(array $meta): self
    {
        return new self(
            to: $this->to,
            subject: $this->subject,
            body: $this->body,
            isHtml: $this->isHtml,
            from: $this->from,
            fromName: $this->fromName,
            replyTo: $this->replyTo,
            cc: $this->cc,
            bcc: $this->bcc,
            attachments: $this->attachments,
            headers: $this->headers,
            meta: array_merge($this->meta, $meta),
        );
    }
}
