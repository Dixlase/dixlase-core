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
     * @param  string  $code  二段階認証コード
     * @param  string  $context  コンテキスト（admin, user等）
     * @param  string  $appName  アプリケーション名
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
