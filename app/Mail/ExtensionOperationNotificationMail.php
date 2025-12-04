<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
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

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ExtensionOperationNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $details;
    public string $operation;
    public bool $isUnhealthyWarning;

    /**
     * Create a new message instance.
     *
     * @param array $details 拡張機能操作の詳細情報
     * @param string $operation 操作種別 (installed, uninstalled, enabled, disabled)
     * @param bool $isUnhealthyWarning 健全性警告メールかどうか
     */
    public function __construct(array $details, string $operation, bool $isUnhealthyWarning = false)
    {
        $this->details = $details;
        $this->operation = $operation;
        $this->isUnhealthyWarning = $isUnhealthyWarning;
    }

    public function envelope(): Envelope
    {
        $appName = config('app.name', 'Dixlase');
        $type = $this->details['type'] ?? 'plugin';
        $typeLabel = __('mail.extension_operation.type_' . $type);
        $name = $this->details['name'] ?? 'Unknown';

        if ($this->isUnhealthyWarning) {
            $subject = __('mail.extension_operation.subject_unhealthy_warning', [
                'app_name' => $appName,
                'type' => $typeLabel,
            ]);
        } else {
            $subject = __('mail.extension_operation.subject_' . $this->operation, [
                'app_name' => $appName,
                'type' => $typeLabel,
                'name' => $name,
            ]);
        }

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.extension_operation_notification',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
