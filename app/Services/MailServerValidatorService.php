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

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Log;

class MailServerValidatorService
{
    /**
     * Check if mail server settings are complete
     */
    public static function isMailServerConfigured(): bool
    {
        $requiredSettings = [
            'MAIL_MAILER',
            'MAIL_HOST',
            'MAIL_PORT',
            'MAIL_FROM_ADDRESS',
        ];

        foreach ($requiredSettings as $setting) {
            $value = env($setting);
            if (empty($value)) {
                Log::info("Mail server not configured: {$setting} is empty");

                return false;
            }
        }

        return true;
    }

    /**
     * Check if all mail server tests are complete
     */
    public static function isMailServerTested(): bool
    {
        $connectionTested = (bool) SiteSetting::getValue('mail_connection_tested', false);
        $sendTested = (bool) SiteSetting::getValue('mail_send_tested', false);
        $receiveTested = (bool) SiteSetting::getValue('mail_receive_tested', false);

        $allTested = $connectionTested && $sendTested && $receiveTested;

        if (! $allTested) {
            Log::info('Mail server tests not completed', [
                'connection_tested' => $connectionTested,
                'send_tested' => $sendTested,
                'receive_tested' => $receiveTested,
            ]);
        }

        return $allTested;
    }

    /**
     * Comprehensive check whether mail sending is possible
     */
    public static function canSendMail(): bool
    {
        return self::isMailServerConfigured() && self::isMailServerTested();
    }

    /**
     * Get reason why mail sending is not possible
     */
    public static function getMailDisabledReason(): string
    {
        if (! self::isMailServerConfigured()) {
            return __('services/mail_server_validator.mail_server_not_configured');
        }

        if (! self::isMailServerTested()) {
            return __('services/mail_server_validator.mail_server_not_tested');
        }

        return '';
    }
}
