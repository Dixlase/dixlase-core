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

namespace App\Services;

use App\Contracts\Logging\LogServiceInterface;
use App\DTO\Logging\LogContextDTO;
use Illuminate\Support\Facades\Log;

/**
 * @internal コア専用。プラグイン/テーマから参照しないこと
 *
 * ログ出力サービス
 *
 * Contract対応の統一的なログ出力機能を提供します。
 */
class LogService implements LogServiceInterface
{
    /**
     * デフォルトチャンネル
     */
    protected string $defaultChannel = 'dixlase';

    /**
     * 管理画面操作ログチャンネル
     */
    protected string $activityChannel = 'admin_activity';

    /**
     * ログインログチャンネル
     */
    protected string $loginChannel = 'admin_login';

    /**
     * フロント操作ログチャンネル
     */
    protected string $frontActivityChannel = 'front_activity';

    /**
     * フロントエラーログチャンネル
     */
    protected string $frontErrorChannel = 'front_error';

    /**
     * 情報ログを出力
     */
    public function info(string $message, LogContextDTO|array $context = [], ?string $channel = null): void
    {
        $this->log($channel ?? $this->defaultChannel, 'info', $message, $context);
    }

    /**
     * 警告ログを出力
     */
    public function warning(string $message, LogContextDTO|array $context = [], ?string $channel = null): void
    {
        $this->log($channel ?? $this->defaultChannel, 'warning', $message, $context);
    }

    /**
     * エラーログを出力
     */
    public function error(string $message, LogContextDTO|array $context = [], ?string $channel = null): void
    {
        $this->log($channel ?? 'admin_error', 'error', $message, $context);
    }

    /**
     * デバッグログを出力
     */
    public function debug(string $message, LogContextDTO|array $context = [], ?string $channel = null): void
    {
        $this->log($channel ?? $this->defaultChannel, 'debug', $message, $context);
    }

    /**
     * 重大エラーログを出力
     */
    public function critical(string $message, LogContextDTO|array $context = [], ?string $channel = null): void
    {
        $this->log($channel ?? 'admin_error', 'critical', $message, $context);
    }

    /**
     * 操作ログを出力（管理画面操作など）
     */
    public function activity(string $action, LogContextDTO|array $context = []): void
    {
        $contextArray = $this->normalizeContext($context);
        $contextArray['action'] = $action;

        Log::channel($this->activityChannel)->info('管理画面操作', $contextArray);
    }

    /**
     * ログインログを出力
     */
    public function login(string $action, LogContextDTO|array $context = []): void
    {
        $contextArray = $this->normalizeContext($context);
        $contextArray['action'] = $action;

        Log::channel($this->loginChannel)->info($action, $contextArray);
    }

    /**
     * フロント操作ログを出力
     */
    public function frontActivity(string $action, LogContextDTO|array $context = []): void
    {
        $contextArray = $this->normalizeContext($context);
        $contextArray['action'] = $action;

        Log::channel($this->frontActivityChannel)->info('フロント操作', $contextArray);
    }

    /**
     * フロントエラーログを出力
     */
    public function frontError(string $error, LogContextDTO|array $context = []): void
    {
        $contextArray = $this->normalizeContext($context);
        $contextArray['error'] = $error;

        Log::channel($this->frontErrorChannel)->error('フロントエラー', $contextArray);
    }

    /**
     * カスタムチャンネルにログを出力
     */
    public function log(string $channel, string $level, string $message, LogContextDTO|array $context = []): void
    {
        $contextArray = $this->normalizeContext($context);

        try {
            Log::channel($channel)->log($level, $message, $contextArray);
        } catch (\Exception $e) {
            // チャンネルが存在しない場合はデフォルトチャンネルにフォールバック
            Log::channel($this->defaultChannel)->log($level, $message, $contextArray);
        }
    }

    /**
     * 利用可能なログチャンネル一覧を取得
     */
    public function getAvailableChannels(): array
    {
        return array_keys(config('logging.channels', []));
    }

    /**
     * コンテキストを配列に正規化
     *
     * @return array<string,mixed>
     */
    protected function normalizeContext(LogContextDTO|array $context): array
    {
        if ($context instanceof LogContextDTO) {
            return $context->toArray();
        }

        return $context;
    }

    /**
     * プラグイン用のログを出力
     *
     * @param  string  $pluginSlug  プラグインスラッグ
     * @param  string  $level  ログレベル
     * @param  string  $message  メッセージ
     * @param  array<string,mixed>  $context  コンテキスト
     */
    public function pluginLog(string $pluginSlug, string $level, string $message, array $context = []): void
    {
        $context['source'] = $pluginSlug;
        $context['plugin'] = $pluginSlug;

        $this->log($this->defaultChannel, $level, "[{$pluginSlug}] {$message}", $context);
    }

    /**
     * プラグイン用の情報ログを出力
     *
     * @param  string  $pluginSlug  プラグインスラッグ
     * @param  string  $message  メッセージ
     * @param  array<string,mixed>  $context  コンテキスト
     */
    public function pluginInfo(string $pluginSlug, string $message, array $context = []): void
    {
        $this->pluginLog($pluginSlug, 'info', $message, $context);
    }

    /**
     * プラグイン用のエラーログを出力
     *
     * @param  string  $pluginSlug  プラグインスラッグ
     * @param  string  $message  メッセージ
     * @param  array<string,mixed>  $context  コンテキスト
     */
    public function pluginError(string $pluginSlug, string $message, array $context = []): void
    {
        $context['source'] = $pluginSlug;
        $context['plugin'] = $pluginSlug;

        $this->log('admin_error', 'error', "[{$pluginSlug}] {$message}", $context);
    }

    /**
     * 例外をログに記録
     *
     * @param  \Throwable  $exception  例外
     * @param  LogContextDTO|array  $context  追加コンテキスト
     * @param  string|null  $channel  チャンネル名
     */
    public function exception(\Throwable $exception, LogContextDTO|array $context = [], ?string $channel = null): void
    {
        $contextArray = $this->normalizeContext($context);
        $contextArray['exception'] = [
            'class' => get_class($exception),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ];

        $this->error($exception->getMessage(), $contextArray, $channel);
    }
}
