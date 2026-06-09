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

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TwoFaDeviceMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $token;

    public $user; // Member or User model

    public string $ipAddress;

    public string $userAgent;

    public string $timestamp;

    public string $approveRoute;

    public string $denyRoute;

    /**
     * Create a new message instance.
     */
    public function __construct(string $token, $user, string $ipAddress, string $userAgent, string $approveRoute = 'admin.device-auth.approve', string $denyRoute = 'admin.device-auth.deny')
    {
        $this->token = $token;
        $this->user = $user;
        $this->ipAddress = $ipAddress;
        $this->userAgent = $userAgent;
        $this->timestamp = now()->format('Y-m-d H:i:s');
        $this->approveRoute = $approveRoute;
        $this->denyRoute = $denyRoute;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.two-fa.device.subject'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.two_fa_device',
            with: [
                'token' => $this->token,
                'user' => $this->user,
                'ipAddress' => $this->ipAddress,
                'userAgent' => $this->userAgent,
                'timestamp' => $this->timestamp,
                'approveUrl' => route($this->approveRoute, ['token' => $this->token]),
                'denyUrl' => route($this->denyRoute, ['token' => $this->token]),
            ],
        );
    }
}
