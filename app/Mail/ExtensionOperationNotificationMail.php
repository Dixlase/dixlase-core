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
 *       (see LICENSE.commercial, or contact info@dixlase.org).
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

use Illuminate\Bus\Queueable;
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
     * @param  array  $details  Detailed information about the extension operation
     * @param  string  $operation  Operation type (installed, uninstalled, enabled, disabled)
     * @param  bool  $isUnhealthyWarning  Whether this is a health warning email
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
        $typeLabel = __('mail.extension.type_'.$type);
        $name = $this->details['name'] ?? 'Unknown';

        if ($this->isUnhealthyWarning) {
            $subject = __('mail.extension.subject_unhealthy_warning', [
                'app_name' => $appName,
                'type' => $typeLabel,
            ]);
        } else {
            $subject = __('mail.extension.subject_'.$this->operation, [
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
