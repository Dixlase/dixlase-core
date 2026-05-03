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

namespace App\Contracts\Logging;

use App\DTO\Logging\LogContextDTO;

/**
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
