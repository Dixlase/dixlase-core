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

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Theme;
use Illuminate\Support\Facades\File;

class ThemeDelete extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dls:theme:delete {themeDirectory : ' . 'command.theme_delete.theme_directory_prompt' . '}
                            {--force : ' . 'command.theme_delete.force_option' . '}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'command.theme_delete.description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $themeDirectory = $this->argument('themeDirectory');
        $themePath = base_path('themes/' . $themeDirectory);

        // テーマディレクトリの存在チェック
        if (!File::exists($themePath)) {
            $this->error(__('command.theme_delete.not_found', ['directory' => $themeDirectory]));
            return Command::FAILURE;
        }

        // データベースに登録されているかチェック（アンインストール済みかどうか）
        $theme = Theme::where('directory', $themeDirectory)->first();
        
        if ($theme) {
            // テーマがまだインストールされている場合
            if ($theme->isInstalled()) {
                $this->error(__('command.theme_delete.still_installed', ['themeName' => $theme->name]));
                $this->warn(__('command.theme_delete.uninstall_first'));
                return Command::FAILURE;
            }
            
            // テーマが有効な場合（念のため）
            if ($theme->isEnabled()) {
                $this->error(__('command.theme_delete.still_enabled', ['themeName' => $theme->name]));
                $this->warn(__('command.theme_delete.disable_first'));
                return Command::FAILURE;
            }
        }

        // 確認プロンプト
        if (!$this->option('force')) {
            if (!$this->confirm(__('command.theme_delete.confirm', ['directory' => $themeDirectory]), false)) {
                $this->info(__('command.theme_delete.cancelled'));
                return Command::SUCCESS;
            }
        }

        // ディレクトリを削除
        try {
            File::deleteDirectory($themePath);
            $this->info(__('command.theme_delete.deleted', ['path' => $themePath]));
        } catch (\Exception $e) {
            $this->error(__('command.theme_delete.failed', ['error' => $e->getMessage()]));
            return Command::FAILURE;
        }

        // データベースからもテーマレコードを削除（存在する場合）
        if ($theme) {
            $theme->delete();
            $this->info(__('command.theme_delete.database_removed', ['themeName' => $theme->name]));
        }

        $this->info(__('command.theme_delete.completed', ['directory' => $themeDirectory]));
        
        return Command::SUCCESS;
    }
}
