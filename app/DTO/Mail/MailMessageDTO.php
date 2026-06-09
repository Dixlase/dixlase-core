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
 * Mail Message DTO
 *
 * Immutable data object that holds information required for sending email
 */
final readonly class MailMessageDTO implements JsonSerializable
{
    /**
     * @param  string|array<string>  $to  Recipient email address
     * @param  string  $subject  Subject
     * @param  string  $body  Body (HTML or text)
     * @param  bool  $isHtml  Whether it is HTML format
     * @param  string|null  $from  Sender email address
     * @param  string|null  $fromName  Sender name
     * @param  string|null  $replyTo  Reply-to email address
     * @param  array<string>  $cc  CC email address
     * @param  array<string>  $bcc  BCC email address
     * @param  array<MailAttachmentDTO>  $attachments  Attachments
     * @param  array<string,mixed>  $headers  Custom headers
     * @param  array<string,mixed>  $meta  Metadata (for logging, etc.)
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
     * Get recipients as array
     *
     * @return array<string>
     */
    public function getRecipients(): array
    {
        return is_array($this->to) ? $this->to : [$this->to];
    }

    /**
     * Check if there are attachments
     */
    public function hasAttachments(): bool
    {
        return ! empty($this->attachments);
    }

    /**
     * Check if there are CC recipients
     */
    public function hasCc(): bool
    {
        return ! empty($this->cc);
    }

    /**
     * Check if there are BCC recipients
     */
    public function hasBcc(): bool
    {
        return ! empty($this->bcc);
    }

    /**
     * Serialize to JSON format
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
     * Convert to array format
     *
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return $this->jsonSerialize();
    }

    /**
     * Create DTO from array
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
     * Create a new DTO with modified subject
     *
     * @param  string  $subject  New subject
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
     * Create a new DTO with modified recipient
     *
     * @param  string|array<string>  $to  New recipient
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
     * Create a new DTO with added metadata
     *
     * @param  array<string,mixed>  $meta  Metadata to add
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
