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
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
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

use App\Models\Member;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminMemberVerifiedNotification extends Notification
{
    use Queueable;

    protected $member;

    protected $verifiedAt;

    /**
     * Create a new notification instance.
     */
    public function __construct(Member $member, string $verifiedAt)
    {
        $this->member = $member;
        $this->verifiedAt = $verifiedAt;
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
        $appName = env('APP_NAME', 'Dixlase');

        return (new MailMessage())
            ->subject("[{$appName}] ".__('mail.member-notification.admin_notification.member_verified.subject'))
            ->greeting(__('mail.member-notification.admin_notification.member_verified.greeting'))
            ->line(__('mail.member-notification.admin_notification.member_verified.title'))
            ->line('') // 空白行
            ->line(__('mail.member-notification.admin_notification.member_verified.message'))
            ->line('') // 空白行
            ->line(__('mail.member-notification.admin_notification.member_verified.member_info'))
            ->line(__('mail.member-notification.admin_notification.member_verified.name').': '.$this->member->name)
            ->line(__('mail.member-notification.admin_notification.member_verified.email').': '.$this->member->email)
            ->line(__('mail.member-notification.admin_notification.member_verified.verified_at').': '.$this->verifiedAt)
            ->line('') // 空白行
            ->line(__('mail.member-notification.admin_notification.member_verified.login_available'))
            ->line('') // 空白行
            ->line(__('mail.member-notification.admin_notification.member_verified.notification_time').': '.now()->format('Y-m-d H:i:s'))
            ->salutation(__('mail.member-notification.admin_notification.member_verified.regards')."\n\n{$appName}");
    }
}
