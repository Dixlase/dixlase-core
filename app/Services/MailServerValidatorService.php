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

namespace App\Services;

use App\Models\BaseSetting;
use Illuminate\Support\Facades\Log;

/**
 * @api プラグイン/テーマから使用可能な安定APIです
 */
class MailServerValidatorService
{
    /**
     * メールサーバーの設定が完了しているかチェック
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
     * メールサーバーのテストが全て完了しているかチェック
     */
    public static function isMailServerTested(): bool
    {
        $connectionTested = (bool) BaseSetting::getValue('mail_connection_tested', false);
        $sendTested = (bool) BaseSetting::getValue('mail_send_tested', false);
        $receiveTested = (bool) BaseSetting::getValue('mail_receive_tested', false);

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
     * メール送信が可能かどうかの総合チェック
     */
    public static function canSendMail(): bool
    {
        return self::isMailServerConfigured() && self::isMailServerTested();
    }

    /**
     * メール送信不可の理由を取得
     */
    public static function getMailDisabledReason(): string
    {
        if (! self::isMailServerConfigured()) {
            return 'メールサーバーの設定が未完了です';
        }

        if (! self::isMailServerTested()) {
            return 'メールサーバーのテストが未完了です';
        }

        return '';
    }
}
