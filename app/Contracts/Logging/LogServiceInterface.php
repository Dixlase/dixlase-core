<?php

namespace App\Contracts\Logging;

use App\DTO\Logging\LogContextDTO;

/**
 * @api プラグイン/テーマから使用可能な安定APIです
 *
 * ログ出力サービスの契約
 *
 * コアおよびプラグインから統一的なログ出力機能を利用するための
 * インターフェースを提供します。
 */
interface LogServiceInterface
{
    /**
     * 情報ログを出力
     *
     * @param  string  $message  メッセージ
     * @param  LogContextDTO|array  $context  コンテキスト
     * @param  string|null  $channel  チャンネル名（nullの場合はデフォルト）
     */
    public function info(string $message, LogContextDTO|array $context = [], ?string $channel = null): void;

    /**
     * 警告ログを出力
     *
     * @param  string  $message  メッセージ
     * @param  LogContextDTO|array  $context  コンテキスト
     * @param  string|null  $channel  チャンネル名
     */
    public function warning(string $message, LogContextDTO|array $context = [], ?string $channel = null): void;

    /**
     * エラーログを出力
     *
     * @param  string  $message  メッセージ
     * @param  LogContextDTO|array  $context  コンテキスト
     * @param  string|null  $channel  チャンネル名
     */
    public function error(string $message, LogContextDTO|array $context = [], ?string $channel = null): void;

    /**
     * デバッグログを出力
     *
     * @param  string  $message  メッセージ
     * @param  LogContextDTO|array  $context  コンテキスト
     * @param  string|null  $channel  チャンネル名
     */
    public function debug(string $message, LogContextDTO|array $context = [], ?string $channel = null): void;

    /**
     * 重大エラーログを出力
     *
     * @param  string  $message  メッセージ
     * @param  LogContextDTO|array  $context  コンテキスト
     * @param  string|null  $channel  チャンネル名
     */
    public function critical(string $message, LogContextDTO|array $context = [], ?string $channel = null): void;

    /**
     * 操作ログを出力（管理画面操作など）
     *
     * @param  string  $action  操作内容
     * @param  LogContextDTO|array  $context  コンテキスト
     */
    public function activity(string $action, LogContextDTO|array $context = []): void;

    /**
     * ログインログを出力
     *
     * @param  string  $action  ログイン/ログアウト
     * @param  LogContextDTO|array  $context  コンテキスト
     */
    public function login(string $action, LogContextDTO|array $context = []): void;

    /**
     * フロント操作ログを出力
     *
     * @param  string  $action  操作内容
     * @param  LogContextDTO|array  $context  コンテキスト
     */
    public function frontActivity(string $action, LogContextDTO|array $context = []): void;

    /**
     * フロントエラーログを出力
     *
     * @param  string  $error  エラー内容
     * @param  LogContextDTO|array  $context  コンテキスト
     */
    public function frontError(string $error, LogContextDTO|array $context = []): void;

    /**
     * カスタムチャンネルにログを出力
     *
     * @param  string  $channel  チャンネル名
     * @param  string  $level  ログレベル
     * @param  string  $message  メッセージ
     * @param  LogContextDTO|array  $context  コンテキスト
     */
    public function log(string $channel, string $level, string $message, LogContextDTO|array $context = []): void;

    /**
     * 利用可能なログチャンネル一覧を取得
     *
     * @return array<string>
     */
    public function getAvailableChannels(): array;
}
