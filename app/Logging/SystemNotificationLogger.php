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

namespace App\Logging;

use Monolog\Logger;

class SystemNotificationLogger
{
    /**
     * カスタムログチャンネルを作成
     */
    public function __invoke(array $config)
    {
        $logger = new Logger('system-notification');

        // 既存のハンドラーを追加
        $logger->pushHandler(new \Monolog\Handler\StreamHandler(
            storage_path('logs/laravel.log'),
            Logger::DEBUG
        ));

        // システム通知ハンドラーを追加
        $logger->pushHandler(new SystemNotificationLogHandler());

        return $logger;
    }
}
