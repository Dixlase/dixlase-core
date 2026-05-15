<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
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

namespace App\Traits;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

/**
 * Trait that provides common logic for email verification
 *
 * This Trait provides only the minimal logic common to
 * member and user email verification notifications
 */
trait EmailVerificationTrait
{
    /**
     * @var string Context ('create', 'email_change', or 'resend')
     */
    protected $context;

    /**
     * Get subject key according to context
     *
     * @param  string  $prefix  Translation key prefix (e.g. 'mail.member_verify_email', 'dixlase-users::mail.verify_email')
     * @return string Translation key for subject
     */
    protected function getSubjectKey(string $prefix): string
    {
        return $this->context === 'email_change'
            ? "{$prefix}.subject"
            : "{$prefix}.subject_account";
    }

    /**
     * Get message key according to context
     *
     * @param  string  $prefix  Translation key prefix
     * @return string Translation key for message
     */
    protected function getMessageKey(string $prefix): string
    {
        return match ($this->context) {
            'email_change' => "{$prefix}.message_email_change",
            'resend' => "{$prefix}.message_resend",
            default => "{$prefix}.message_create",
        };
    }

    /**
     * Get action key according to context
     *
     * @param  string  $prefix  Translation key prefix
     * @return string Translation key for action button
     */
    protected function getActionKey(string $prefix): string
    {
        return $this->context === 'email_change'
            ? "{$prefix}.action_change_email"
            : "{$prefix}.action_verify_account";
    }

    /**
     * Generate signed temporary URL for email verification
     *
     * @param  object  $notifiable  Model to be notified
     * @param  string  $routeName  Route name
     * @param  string  $logContext  Context name for logging (e.g. 'member', 'user')
     * @return string Signed URL
     */
    protected function generateVerificationUrl(object $notifiable, string $routeName, string $logContext = 'entity'): string
    {
        $url = URL::temporarySignedRoute(
            $routeName,
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );

        \Log::info("Email verification URL generated for {$logContext}", [
            "{$logContext}_id" => $notifiable->getKey(),
            'email' => $notifiable->getEmailForVerification(),
            'url' => $url,
            'context' => $this->context,
        ]);

        return $url;
    }

    /**
     * Get expiration time (minutes) for verification email
     *
     * @return int Expiration time (minutes)
     */
    protected function getExpirationMinutes(): int
    {
        return Config::get('auth.verification.expire', 60);
    }
}
