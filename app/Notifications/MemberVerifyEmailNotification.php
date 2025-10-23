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

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class MemberVerifyEmailNotification extends Notification
{
    use Queueable;

    /**
     * @var string コンテキスト（'create', 'email_change', または 'resend'）
     */
    protected $context;

    /**
     * Create a new notification instance.
     *
     * @param string $context 'create', 'email_change', または 'resend'
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
        $verificationUrl = $this->verificationUrl($notifiable);

        // コンテキストに応じてメッセージを切り替え
        $messageKey = match($this->context) {
            'email_change' => 'mail.member_verify_email.message_email_change',
            'resend' => 'mail.member_verify_email.message_resend',
            default => 'mail.member_verify_email.message_create',
        };

        // コンテキストに応じてボタンラベルを切り替え
        $actionKey = $this->context === 'email_change' 
            ? 'mail.member_verify_email.action_change_email'
            : 'mail.member_verify_email.action_verify_account';

        return (new MailMessage)
            ->subject(__('mail.member_verify_email.subject'))
            ->greeting(__('mail.member_verify_email.greeting', ['name' => $notifiable->name]))
            ->line(__($messageKey))
            ->action(__($actionKey), $verificationUrl)
            ->line(__('mail.member_verify_email.manual_verification'))
            ->line($verificationUrl)
            ->line(__('mail.member_verify_email.expiration', ['minutes' => Config::get('auth.verification.expire', 60)]))
            ->salutation(__('mail.member_verify_email.regards'));
    }

    /**
     * Get the verification URL for the given notifiable.
     */
    protected function verificationUrl(object $notifiable): string
    {
        return URL::temporarySignedRoute(
            'admin.verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }
}
