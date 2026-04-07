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

namespace App\DTO\PluginIntegration;

/**
 * ダッシュボードウィジェットDTO
 *
 * プラグインがダッシュボードに表示するウィジェットデータを保持します。
 */
final readonly class DashboardWidgetDTO
{
    /**
     * @param  string  $key  ウィジェット固有キー（例: 'pages_count'）
     * @param  string  $label  表示ラベル（翻訳済み文字列）
     * @param  string|int  $value  メイン表示値（例: '12', 0）
     * @param  string  $icon  Font Awesomeアイコンクラス（例: 'fas fa-file-alt'）
     * @param  string|null  $url  詳細ページへのリンク（null可）
     * @param  string|null  $description  補足説明（null可）
     * @param  string  $color  カードカラー（'blue', 'green', 'purple', 'orange' 等）
     */
    public function __construct(
        public string $key,
        public string $label,
        public string|int $value,
        public string $icon,
        public ?string $url = null,
        public ?string $description = null,
        public string $color = 'blue',
    ) {}
}
