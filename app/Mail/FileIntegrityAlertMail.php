<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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

namespace App\Mail;

use App\Models\FileIntegrityAudit;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FileIntegrityAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public FileIntegrityAudit $audit;

    public array $summary;

    public string $siteName;

    public string $siteUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(FileIntegrityAudit $audit)
    {
        $this->audit = $audit;
        $this->summary = $audit->summary ?? [];
        $this->siteName = config('app.name', 'Dixlase');
        $this->siteUrl = config('app.url', '');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $statusLabel = match ($this->audit->status) {
            FileIntegrityAudit::STATUS_CRITICAL => __('mail.file-integrity.status_critical'),
            FileIntegrityAudit::STATUS_WARNING => __('mail.file-integrity.status_warning'),
            default => __('mail.file-integrity.status_unknown'),
        };

        return new Envelope(
            subject: __('mail.file-integrity.subject', [
                'site_name' => $this->siteName,
                'status' => $statusLabel,
            ]),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.file-integrity-alert',
            with: [
                'audit' => $this->audit,
                'summary' => $this->summary,
                'siteName' => $this->siteName,
                'siteUrl' => $this->siteUrl,
                'statusLabel' => match ($this->audit->status) {
                    FileIntegrityAudit::STATUS_CRITICAL => __('mail.file-integrity.status_critical'),
                    FileIntegrityAudit::STATUS_WARNING => __('mail.file-integrity.status_warning'),
                    default => __('mail.file-integrity.status_unknown'),
                },
                'statusColor' => match ($this->audit->status) {
                    FileIntegrityAudit::STATUS_CRITICAL => 'red',
                    FileIntegrityAudit::STATUS_WARNING => 'yellow',
                    default => 'gray',
                },
            ],
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
