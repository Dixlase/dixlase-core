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

use App\Services\SystemNotificationService;
use Illuminate\Support\Facades\DB;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;

class SystemNotificationLogHandler extends AbstractProcessingHandler
{
    protected SystemNotificationService $notificationService;

    public function __construct()
    {
        parent::__construct();
        $this->notificationService = app(SystemNotificationService::class);
    }

    /**
     * ログレコードを処理
     */
    protected function write(LogRecord $record): void
    {
        try {
            // 通知機能が有効かチェック
            if (! $this->shouldSendNotification($record)) {
                return;
            }

            // 件名を生成
            $subject = $this->generateSubject($record);

            // メッセージを生成
            $message = $this->generateMessage($record);

            // コンテキスト情報を準備
            $context = $this->prepareContext($record);

            // 通知送信
            $this->notificationService->sendErrorNotification($subject, $message, $context);
        } catch (\Exception $e) {
            // 通知送信エラーは別のログに記録（無限ループを避けるため）
            error_log('SystemNotificationLogHandler error: '.$e->getMessage());
        }
    }

    /**
     * 通知を送信すべきかチェック
     */
    private function shouldSendNotification(LogRecord $record): bool
    {
        // 通知機能が有効かチェック
        if (! $this->notificationService->isNotificationEnabled()) {
            return false;
        }

        // 通知対象ログレベルを取得
        $notificationLevels = $this->getNotificationLevels();
        if (empty($notificationLevels)) {
            return false;
        }

        // MonologレベルをDixlaseのLogLevelに変換
        $dixlaseLevel = $this->convertMonologLevelToDixlaseLevel($record->level->value);
        if ($dixlaseLevel === null) {
            return false;
        }

        // 通知対象レベルに含まれているかチェック
        return in_array($dixlaseLevel, $notificationLevels);
    }

    /**
     * 通知対象ログレベルを取得
     */
    private function getNotificationLevels(): array
    {
        try {
            // SecuritySettingモデルの代わりに直接DBクエリを使用
            $levels = DB::table('security_settings')
                ->where('name', 'notification_log_levels')
                ->value('value');

            if (empty($levels)) {
                return [];
            }

            return array_map('intval', explode(',', $levels));
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * MonologレベルをDixlaseのLogLevelに変換
     */
    private function convertMonologLevelToDixlaseLevel(int $monologLevel): ?int
    {
        // Monolog レベル値 → Dixlase LogLevel値のマッピング
        $mapping = [
            600 => 8, // EMERGENCY
            550 => 7, // ALERT
            500 => 6, // CRITICAL
            400 => 5, // ERROR
            300 => 4, // WARNING
            250 => 3, // NOTICE
            200 => 2, // INFO
            100 => 1, // DEBUG
        ];

        return $mapping[$monologLevel] ?? null;
    }

    /**
     * 件名を生成
     */
    private function generateSubject(LogRecord $record): string
    {
        $levelName = $record->level->name;
        $appName = env('APP_NAME', 'Dixlase');

        return "【{$levelName}】システムエラーが発生しました - {$appName}";
    }

    /**
     * メッセージを生成
     */
    private function generateMessage(LogRecord $record): string
    {
        return $record->message;
    }

    /**
     * コンテキスト情報を準備
     */
    private function prepareContext(LogRecord $record): array
    {
        $context = [
            'log_level' => $record->level->name,
            'log_level_value' => $record->level->value,
            'timestamp' => $record->datetime->format('Y-m-d H:i:s'),
            'channel' => $record->channel,
        ];

        // 既存のコンテキスト情報をマージ
        if (! empty($record->context)) {
            $context = array_merge($context, $record->context);
        }

        // 追加情報を抽出
        if (isset($record->context['exception'])) {
            $exception = $record->context['exception'];
            if ($exception instanceof \Exception) {
                $context['file'] = $exception->getFile();
                $context['line'] = $exception->getLine();
                $context['type'] = get_class($exception);
            }
        }

        // リクエスト情報を追加（可能な場合）
        try {
            if (app()->bound('request')) {
                $request = request();
                $context['url'] = $request->fullUrl();
                $context['method'] = $request->method();
                $context['ip'] = $request->ip();
                $context['user_agent'] = $request->userAgent();
            }
        } catch (\Exception $e) {
            // リクエスト情報の取得に失敗した場合は無視
        }

        return $context;
    }
}
