<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
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

namespace App\Contracts\Mail;

use App\DTO\Mail\MailConfigDTO;
use App\DTO\Mail\MailMessageDTO;
use App\DTO\Mail\MailResultDTO;

/**
 * メール送信サービスの契約
 *
 * コアおよびプラグインからメール送信機能を利用するための
 * 統一インターフェースを提供します。
 */
interface MailServiceInterface
{
    /**
     * メールを送信
     *
     * @param  MailMessageDTO  $message  メールメッセージ
     * @param  MailConfigDTO|null  $config  カスタム設定（nullの場合はシステム設定を使用）
     */
    public function send(MailMessageDTO $message, ?MailConfigDTO $config = null): MailResultDTO;

    /**
     * 複数のメールを一括送信
     *
     * @param  array<MailMessageDTO>  $messages  メールメッセージの配列
     * @param  MailConfigDTO|null  $config  カスタム設定
     * @return array<MailResultDTO>
     */
    public function sendMany(array $messages, ?MailConfigDTO $config = null): array;

    /**
     * キューにメールを追加（非同期送信）
     *
     * @param  MailMessageDTO  $message  メールメッセージ
     * @param  MailConfigDTO|null  $config  カスタム設定
     * @param  string|null  $queue  キュー名
     */
    public function queue(MailMessageDTO $message, ?MailConfigDTO $config = null, ?string $queue = null): MailResultDTO;

    /**
     * SMTP接続テスト
     *
     * @param  MailConfigDTO  $config  メール設定
     */
    public function testConnection(MailConfigDTO $config): MailResultDTO;

    /**
     * 現在のメール設定を取得
     */
    public function getConfig(): MailConfigDTO;

    /**
     * メール設定が有効かどうか
     */
    public function isConfigured(): bool;
}
