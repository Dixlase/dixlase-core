<?php

/**
 * This file is part of MySoftware.
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

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Exception;

abstract class PluginMigrationCommand extends Command
{
    /**
     * 現在の環境が本番環境かどうかを判定
     *
     * @return bool
     */
    protected function isProduction(): bool
    {
        return app()->environment('production');
    }

    /**
     * 本番環境での実行を確認する
     *
     * @param string $action 説明文（例: "migrate"）
     * @return bool
     */
    protected function confirmProduction(string $action): bool
    {
        if ($this->isProduction()) {
            return $this->confirm("You are running this command in production. Do you wish to continue with {$action}?");
        }

        return true;
    }

    /**
     * プラグインディレクトリの存在確認
     *
     * @param string $plugin プラグイン名
     * @return bool
     */
    protected function pluginExists(string $plugin): bool
    {
        return is_dir(base_path("plugins/{$plugin}"));
    }

    /**
     * マイグレーションディレクトリの存在確認
     *
     * @param string $plugin プラグイン名
     * @return bool
     */
    protected function migrationPathExists(string $plugin): bool
    {
        return is_dir(base_path("plugins/{$plugin}/database/migrations"));
    }

    /**
     * オプションのバリデーションや処理を行う
     *
     * @param array $options 処理するオプション
     * @return array 処理済みのオプション
     */
    protected function processOptions(array $options): array
    {
        // 例: 'step' オプションのデフォルト値設定
        $options['step'] = $options['step'] ?? null;

        // 必要に応じて他のオプションも処理
        // 例: 'pretend' オプションを boolean に変換
        $options['pretend'] = filter_var($options['pretend'] ?? false, FILTER_VALIDATE_BOOLEAN);

        return $options;
    }


    /**
     * マイグレーション操作を安全に実行するラッパーメソッド
     *
     * @param callable $operation 実行するマイグレーション操作
     * @return bool
     */
    protected function executeOperation(callable $operation): bool
    {
        try {
            $operation();
            return true;
        } catch (Exception $e) {
            $this->error($e->getMessage());
            return false;
        }
    }
}
