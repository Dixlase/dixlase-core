<?php

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
