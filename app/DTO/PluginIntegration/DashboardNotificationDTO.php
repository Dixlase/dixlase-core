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

namespace App\DTO\PluginIntegration;

/**
 * ダッシュボード通知DTO
 *
 * プラグイン・リモートサーバー・システムからダッシュボードに表示する
 * 警告・推奨・情報通知を保持します。
 */
final readonly class DashboardNotificationDTO
{
    /** @var string プラグインからの通知 */
    public const SOURCE_PLUGIN = 'plugin';

    /** @var string リモートサーバーからの通知 */
    public const SOURCE_REMOTE = 'remote';

    /** @var string システム内部からの通知 */
    public const SOURCE_SYSTEM = 'system';

    /**
     * @param  string  $key  通知固有キー（例: 'inquiry_captcha_off'）
     * @param  string  $level  通知レベル（'warning', 'recommendation', 'info'）
     * @param  string  $message  通知メッセージ（翻訳済み文字列）
     * @param  string  $icon  Font Awesomeアイコンクラス（例: 'fas fa-exclamation-triangle'）
     * @param  string  $pluginName  プラグイン表示名（翻訳済み文字列）
     * @param  string|null  $url  対応ページへのリンク（null可）
     * @param  string|null  $actionLabel  アクションリンクのラベル（null可）
     * @param  string  $source  通知ソース（'plugin', 'remote', 'system'）
     */
    public function __construct(
        public string $key,
        public string $level,
        public string $message,
        public string $icon,
        public string $pluginName,
        public ?string $url = null,
        public ?string $actionLabel = null,
        public string $source = self::SOURCE_PLUGIN,
    ) {}
}
