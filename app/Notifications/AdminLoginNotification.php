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

/**
 * 管理画面ログイン通知
 */
class AdminLoginNotification extends Notification
{
    use Queueable;

    /**
     * The login details.
     *
     * @var array
     */
    public $loginDetails;

    /**
     * Whether this is a system notification.
     *
     * @var bool
     */
    public $isSystemNotification;

    /**
     * Create a new notification instance.
     *
     * @param  array  $loginDetails
     * @param  bool  $isSystemNotification
     * @return void
     */
    public function __construct(array $loginDetails, bool $isSystemNotification = false)
    {
        $this->loginDetails = $loginDetails;
        $this->isSystemNotification = $isSystemNotification;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return $this->buildMailMessage($notifiable);
    }

    /**
     * Get the login notification mail message.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    protected function buildMailMessage($notifiable)
    {
        $message = new MailMessage;

        // Determine display name with fallback priority
        $displayName = $notifiable->display_name
            ?? $notifiable->name
            ?? $notifiable->account_name 
            ?? $notifiable->email;

        $contextKey = $this->getContextKey();

        // Set subject based on notification type
        if ($this->isSystemNotification) {
            $message->subject(__('mail.login_notification.subject_system', ['context' => __($contextKey)]));
            $message->greeting(__('mail.login_notification.system_message'));
        } else {
            $message->subject(__('mail.login_notification.subject_user', [
                'name' => $displayName,
                'context' => __($contextKey)
            ]));
            $message->greeting(__('mail.login_notification.user_message', [
                'name' => $displayName,
                'context' => __($contextKey)
            ]));
        }

        // Add login details
        if ($this->isSystemNotification) {
            $message->line('**' . __('mail.login_notification.details_title') . '**');
            $message->line('**' . __('mail.login_notification.datetime') . '** ' . $this->loginDetails['datetime']);
            $message->line('**' . __('mail.login_notification.ip_address') . '** ' . $this->loginDetails['ip']);
            
            // Add User-Agent for system notifications only
            if (isset($this->loginDetails['user_agent'])) {
                $message->line('**' . __('mail.login_notification.user_agent') . '** ' . $this->loginDetails['user_agent']);
            }
        } else {
            $message->line(__('mail.login_notification.datetime') . ' ' . $this->loginDetails['datetime']);
            $message->line(__('mail.login_notification.ip_address') . ' ' . $this->loginDetails['ip']);
            $message->line(__('mail.login_notification.user_agent') . ' ' . $this->loginDetails['user_agent']);
            $message->line('');
            $message->line(__('mail.login_notification.security_notice'));
        }

        // Add regards
        $message->line('');
        $message->line(__('mail.login_notification.regards'));
        $message->line(config('app.name'));

        return $message;
    }

    /**
     * Get the context translation key.
     *
     * @return string
     */
    protected function getContextKey(): string
    {
        return 'mail.login_notification.context.admin';
    }
}
