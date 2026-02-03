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

class MemberVerifiedNotification extends Notification
{
    use Queueable;

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
        $appName = env('APP_NAME', 'Dixlase');
        $frontUrl = url('/');
        $adminUrl = route('admin.login');

        return (new MailMessage)
            ->subject(__('mail.verify-email.member_verification_completed.subject'))
            ->greeting(__('mail.verify-email.member_verification_completed.greeting', ['name' => $notifiable->name]))
            ->line(__('mail.verify-email.member_verification_completed.message'))
            ->line('') // 空白行
            ->line(__('mail.verify-email.member_verification_completed.member_info'))
            ->line(__('mail.verify-email.member_verification_completed.name') . ': ' . $notifiable->name)
            ->line(__('mail.verify-email.member_verification_completed.email') . ': ' . $notifiable->email)
            ->line('') // 空白行
            ->line(__('mail.verify-email.member_verification_completed.login_info'))
            ->line('') // 空白行
            ->line(__('mail.verify-email.member_verification_completed.url_info'))
            ->line(__('mail.verify-email.member_verification_completed.front_url') . ': ' . $frontUrl)
            ->line(__('mail.verify-email.member_verification_completed.admin_url') . ': ' . $adminUrl)
            ->action(__('common.login'), $adminUrl)
            ->line('') // 空白行
            ->line(__('mail.verify-email.member_verification_completed.thanks'))
            ->salutation(__('mail.verify-email.member_verification_completed.regards') . "\n\n{$appName}");
    }
}
