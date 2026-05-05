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

namespace App\Notifications;

use App\Traits\EmailVerificationTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MemberVerifyEmailNotification extends Notification
{
    use EmailVerificationTrait, Queueable;

    /**
     * Create a new notification instance.
     *
     * @param  string  $context  'create', 'email_change', or 'resend'
     */
    public function __construct(string $context = 'create')
    {
        $this->context = $context;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $prefix = 'mail.verify-email.member';
        $verificationUrl = $this->generateVerificationUrl($notifiable, 'admin.verification.verify', 'member');

        $subjectKey = $this->getSubjectKey($prefix);
        $messageKey = $this->getMessageKey($prefix);
        $actionKey = $this->getActionKey($prefix);

        return (new MailMessage())
            ->subject(__($subjectKey, ['type' => __('common.account_types.member')]))
            ->greeting(__('mail.verify-email.member.greeting', ['name' => $notifiable->name]))
            ->line(__($messageKey))
            ->action(__($actionKey), $verificationUrl)
            ->line(__('mail.verify-email.member.manual_verification'))
            ->line($verificationUrl)
            ->line(__('mail.verify-email.member.expiration', ['minutes' => $this->getExpirationMinutes()]))
            ->line('') // blank line
            ->line(__('mail.verify-email.member.security_notice'))
            ->salutation(__('mail.verify-email.member.regards'));
    }
}
