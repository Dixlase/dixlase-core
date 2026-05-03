<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
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

namespace App\Console\Traits;

use Exception;
use Illuminate\Support\Facades\DB;

/**
 * カスタムバリデーター（または独自バリデーションルール）を作るための追加ロジック。
 * -> MakeFileTrait を use してファイル生成を共通化。
 */
trait PluginManagementTrait
{
    /**
     * 現在の環境が本番環境かどうかを判定
     */
    protected function isProduction(): bool
    {
        return app()->environment('production');
    }

    /**
     * 本番環境での実行を確認する
     *
     * @param  string  $action  説明文（例: "migrate"）
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
     * @param  string  $plugin  プラグイン名
     */
    protected function pluginExists(string $plugin): bool
    {
        return is_dir(base_path("plugins/{$plugin}"));
    }

    /**
     * プラグインデータを取得
     *
     * @return object|null
     */
    protected function getPluginData(string $pluginName)
    {
        return DB::table('plugins')->where('name', $pluginName)->first();
    }

    /**
     * プラグインを有効化
     */
    protected function enablePlugin(string $pluginName): void
    {
        DB::table('plugins')->where('name', $pluginName)->update(['status' => 1]);
        $this->info("プラグイン '{$pluginName}' を有効化しました。");
    }

    /**
     * プラグインを無効化
     */
    protected function disablePlugin(string $pluginName): void
    {
        DB::table('plugins')->where('name', $pluginName)->update(['status' => 0]);
        $this->info("プラグイン '{$pluginName}' を無効化しました。");
    }

    /**
     * マイグレーションディレクトリの存在確認
     *
     * @param  string  $plugin  プラグイン名
     */
    protected function migrationPathExists(string $plugin): bool
    {
        return is_dir(base_path("plugins/{$plugin}/database/migrations"));
    }

    /**
     * オプションのバリデーションや処理を行う
     *
     * @param  array  $options  処理するオプション
     * @return array 処理済みのオプション
     */
    protected function processOptions(array $options): array
    {
        // 'step' オプションのデフォルト値設定
        $options['step'] = $options['step'] ?? null;

        // 必要に応じて他のオプションも処理
        $options['pretend'] = filter_var($options['pretend'] ?? false, FILTER_VALIDATE_BOOLEAN);

        return $options;
    }

    /**
     * マイグレーション操作を安全に実行するラッパーメソッド
     *
     * @param  callable  $operation  実行するマイグレーション操作
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

    /**
     * オートロードを更新
     *
     * 注意: このメソッドは非推奨です。
     * composer.local.jsonの更新にはComposerLocalHelper::syncAutoload()を使用してください。
     *
     * @deprecated
     */
    protected function updateAutoload(): void
    {
        $this->warn('updateAutoload()は非推奨です。ComposerLocalHelper::syncAutoload()を使用してください。');
    }
}
