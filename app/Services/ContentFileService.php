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

use App\Traits\ManagesContentFiles;

/**
 * @api プラグイン/テーマから直接DIで使用可能な安定APIです
 *
 * コンテンツファイル管理サービス
 * ページ、ブログ記事などのファイルベースのコンテンツ保存を管理
 * ManagesContentFilesトレイトを使用して共通機能を提供
 */
class ContentFileService
{
    use ManagesContentFiles;

    /**
     * コンストラクタ
     *
     * @param  string  $basePath  ベースパス（例: 'pages', 'posts'）
     * @param  string  $disk  ディスク名
     * @param  string  $defaultLocale  デフォルト言語
     */
    public function __construct(string $basePath = 'content', string $disk = 'local', string $defaultLocale = 'en')
    {
        $this->basePath = $basePath;
        $this->disk = $disk;
        $this->defaultLocale = $defaultLocale;
    }
}
