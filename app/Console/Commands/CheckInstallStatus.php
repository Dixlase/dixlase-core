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

/**
 * This file is part of Your Software Name.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckInstallStatus extends Command
{
    protected $signature = 'dls:install:check';
    protected $description = 'インストール状態とマイグレーション状況をチェック';

    public function handle()
    {
        $this->info('========================================');
        $this->info('  インストール状態チェック');
        $this->info('========================================');
        $this->newLine();
        
        // 1. INSTALLED状態
        $installed = env('INSTALLED');
        $this->info("1. INSTALLED: " . var_export($installed, true));
        $this->line("   型: " . gettype($installed));
        $this->line("   判定: " . (($installed === 'true' || $installed === true) ? '✅ インストール済み' : '❌ 未インストール'));
        $this->newLine();
        
        // 2. データベース接続
        try {
            $dbName = DB::connection()->getDatabaseName();
            $this->info("2. データベース接続: ✅ OK");
            $this->line("   データベース名: {$dbName}");
        } catch (\Exception $e) {
            $this->error("2. データベース接続: ❌ NG");
            $this->line("   エラー: " . $e->getMessage());
            return 1;
        }
        $this->newLine();
        
        // 3. migrationsテーブル
        try {
            $hasMigrationsTable = DB::getSchemaBuilder()->hasTable('migrations');
            $this->info("3. migrationsテーブル: " . ($hasMigrationsTable ? '✅ 存在' : '❌ 不在'));
            
            if ($hasMigrationsTable) {
                $migrationCount = DB::table('migrations')->count();
                $this->line("   実行済みマイグレーション: {$migrationCount}件");
                
                // 最近のマイグレーション5件を表示
                $recentMigrations = DB::table('migrations')
                    ->orderBy('id', 'desc')
                    ->limit(5)
                    ->pluck('migration');
                
                $this->line("   最近のマイグレーション:");
                foreach ($recentMigrations as $migration) {
                    $this->line("   - {$migration}");
                }
            }
        } catch (\Exception $e) {
            $this->error("3. migrationsテーブルチェックエラー: " . $e->getMessage());
        }
        $this->newLine();
        
        // 4. 主要テーブル
        $this->info("4. 主要テーブル:");
        $requiredTables = ['members', 'base_settings', 'themes', 'theme_settings', 'members_roles'];
        
        foreach ($requiredTables as $table) {
            try {
                $exists = DB::getSchemaBuilder()->hasTable($table);
                if ($exists) {
                    $count = DB::table($table)->count();
                    $this->line("   {$table}: ✅ 存在 ({$count}レコード)");
                } else {
                    $this->line("   {$table}: ❌ 不在");
                }
            } catch (\Exception $e) {
                $this->error("   {$table}: エラー - " . $e->getMessage());
            }
        }
        $this->newLine();
        
        // 5. 管理者ユーザー
        try {
            if (DB::getSchemaBuilder()->hasTable('members')) {
                // role=10: SUPER_ADMIN, role=9: ADMIN, role=1: GUEST
                $adminCount = DB::table('members')->whereIn('role', [9, 10])->count();
                $this->info("5. 管理者ユーザー (role 9,10): {$adminCount}人");
                
                if ($adminCount > 0) {
                    $admins = DB::table('members')
                        ->whereIn('role', [9, 10])
                        ->select('id', 'name', 'email', 'role')
                        ->get();
                    
                    foreach ($admins as $admin) {
                        $roleLabel = $admin->role == 10 ? 'SUPER_ADMIN' : 'ADMIN';
                        $this->line("   - [{$admin->id}] {$admin->name} ({$admin->email}) [role={$admin->role}:{$roleLabel}]");
                    }
                } else {
                    $this->warn("   ⚠️ 管理者ユーザーが存在しません");
                }
            }
        } catch (\Exception $e) {
            $this->error("5. 管理者ユーザーチェックエラー: " . $e->getMessage());
        }
        $this->newLine();
        
        // 6. base_settings
        try {
            if (DB::getSchemaBuilder()->hasTable('base_settings')) {
                $hasSiteName = DB::table('base_settings')
                    ->where('name', 'site_name')
                    ->exists();
                
                $this->info("6. base_settings (site_name): " . ($hasSiteName ? '✅ 存在' : '❌ 不在'));
                
                if ($hasSiteName) {
                    $siteName = DB::table('base_settings')
                        ->where('name', 'site_name')
                        ->value('value');
                    $this->line("   サイト名: {$siteName}");
                }
            }
        } catch (\Exception $e) {
            $this->error("6. base_settingsチェックエラー: " . $e->getMessage());
        }
        $this->newLine();
        
        // 7. 総合判定
        $this->info('========================================');
        $this->info('  総合判定');
        $this->info('========================================');
        
        try {
            $isMigrationComplete = $this->checkMigrationCompleted();
            
            if ($isMigrationComplete) {
                $this->info('✅ マイグレーション: 完了');
                
                if ($installed === 'true' || $installed === true) {
                    $this->info('✅ インストール: 完了');
                    $this->line('');
                    $this->info('🎉 システムは正常にインストールされています！');
                } else {
                    $this->warn('⚠️ INSTALLED=false になっています');
                    $this->line('');
                    $this->info('💡 フロントページにアクセスすると完了画面が表示されます。');
                    $this->info('   ボタンを押すことでINSTALLED=trueになります。');
                }
            } else {
                $this->error('❌ マイグレーション: 未完了');
                $this->line('');
                $this->warn('インストールプロセスを実行してください。');
            }
        } catch (\Exception $e) {
            $this->error('判定エラー: ' . $e->getMessage());
        }
        
        return 0;
    }
    
    private function checkMigrationCompleted(): bool
    {
        try {
            // 基本チェック
            if (!DB::connection()->getDatabaseName()) return false;
            
            // migrationsテーブルが存在しない場合はスキップ（直接SQL実行の場合）
            if (DB::getSchemaBuilder()->hasTable('migrations')) {
                $migrationCount = DB::table('migrations')->count();
                if ($migrationCount < 15) return false;
            }
            
            if (!DB::getSchemaBuilder()->hasTable('members')) return false;
            if (!DB::getSchemaBuilder()->hasTable('base_settings')) return false;
            
            // role=10: SUPER_ADMIN, role=9: ADMIN
            $adminCount = DB::table('members')->whereIn('role', [9, 10])->count();
            if ($adminCount === 0) return false;
            
            $hasSiteName = DB::table('base_settings')->where('name', 'site_name')->exists();
            
            return $hasSiteName;
        } catch (\Exception $e) {
            return false;
        }
    }
}
