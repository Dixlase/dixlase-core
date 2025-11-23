<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
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

use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Support\Facades\Log;

class ThemeMigrationRepository extends DatabaseMigrationRepository
{
    protected $theme;

    public function __construct(ConnectionResolverInterface $resolver, $table, $theme = null)
    {
        parent::__construct($resolver, $table);
        $this->theme = $theme;
    }

    /**
     * テーマ名に基づいて実行済みマイグレーションを取得
     */
    public function getRan($theme = null)
    {
        $theme = $theme ?? $this->theme; // null の場合はインスタンス変数を使用

        return $this->table()
            ->where('theme', $theme)
            ->pluck('migration')
            ->all();
    }

    /**
     * マイグレーションを記録
     */
    public function log($file, $batch, $theme = null)
    {

        $theme = $theme ?? $this->theme; // テーマが明示的に渡されなかった場合、インスタンス変数を使用

        $this->table()->insert([
            'migration' => $file,
            'theme' => $theme, // テーマ識別子を追加
            'batch' => $batch,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
