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

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TwoFaCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $code;

    public string $context;

    public string $appName;

    /**
     * Create a new message instance.
     *
     * @param  string  $code  Two-factor authentication code
     * @param  string  $context  Context (admin, user, etc.)
     * @param  string  $appName  Application name
     */
    public function __construct(string $code, string $context = 'admin', ?string $appName = null)
    {
        $this->code = $code;
        $this->context = $context;
        $this->appName = $appName ?? config('app.name', 'Dixlase');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.two-fa.email.subject', ['app_name' => $this->appName]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.two-fa-code',
            with: [
                'code' => $this->code,
                'appName' => $this->appName,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
