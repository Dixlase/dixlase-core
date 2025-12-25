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

namespace App\Http\Controllers\Admin\Settings\Systems;

use App\Http\Controllers\Admin\AdminLoggedInController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class AdminSystemDatabaseController extends AdminLoggedInController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * データベース管理画面
     */
    public function index()
    {
        $cleanupInfo = [
            'login_attempts' => [
                'name' => __('admin/settings/systems/database.login_attempts.name'),
                'description' => __('admin/settings/systems/database.login_attempts.description'),
                'default_days' => 30,
                'command' => 'dls:admin:cleanup-login-attempts'
            ],
            'password_reset_tokens' => [
                'name' => __('admin/settings/systems/database.password_reset_tokens.name'),
                'description' => __('admin/settings/systems/database.password_reset_tokens.description'),
                'default_days' => 30,
                'command' => 'dls:admin:cleanup-password-reset-tokens'
            ],
            'two_factor_attempts' => [
                'name' => __('admin/settings/systems/database.two_factor_attempts.name'),
                'description' => __('admin/settings/systems/database.two_factor_attempts.description'),
                'default_days' => 30,
                'command' => 'dls:admin:cleanup-two-factor-attempts'
            ],
            'two_factor_tokens' => [
                'name' => __('admin/settings/systems/database.two_factor_tokens.name'),
                'description' => __('admin/settings/systems/database.two_factor_tokens.description'),
                'default_days' => 7,
                'command' => 'dls:admin:cleanup-two-factor-tokens'
            ],
            'recovery_codes' => [
                'name' => __('admin/settings/systems/database.recovery_codes.name'),
                'description' => __('admin/settings/systems/database.recovery_codes.description'),
                'default_days' => 90,
                'command' => 'dls:admin:cleanup-recovery-codes'
            ],
            'passkeys' => [
                'name' => __('admin/settings/systems/database.passkeys.name'),
                'description' => __('admin/settings/systems/database.passkeys.description'),
                'default_days' => 90,
                'command' => 'dls:admin:cleanup-passkeys'
            ],
            'cache_data' => [
                'name' => __('admin/settings/systems/database.cache_data.name'),
                'description' => __('admin/settings/systems/database.cache_data.description'),
                'default_days' => null,
                'command' => 'dls:admin:cleanup-cache'
            ],
            'sessions' => [
                'name' => __('admin/settings/systems/database.sessions.name'),
                'description' => __('admin/settings/systems/database.sessions.description'),
                'default_days' => 7,
                'command' => 'dls:admin:cleanup-sessions'
            ]
        ];

        $this->viewParams['cleanupInfo'] = $cleanupInfo;
        
        // プラグインのクリーンアップ設定を取得
        $pluginCleanupInfo = $this->getPluginCleanupInfo();
        $this->viewParams['pluginCleanupInfo'] = $pluginCleanupInfo;
        
        return view('admin::settings.systems.database', $this->viewParams);
    }

    /**
     * 個別データベースクリーンアップ
     */
    public function cleanup(Request $request)
    {
        $type = $request->input('type');
        $days = (int) $request->input('days');
        $message = '';
        $success = true;
        $count = 0;

        try {
            switch ($type) {
                case 'login_attempts':
                    $options = ['--days' => $days];
                    if ($days === 0) {
                        $options['--force'] = true;
                    }
                    Artisan::call('dls:admin:cleanup-login-attempts', $options);
                    $output = Artisan::output();
                    $count = $this->extractCountFromOutput($output);
                    $message = __('admin/settings/systems/database.cleanup_success', ['count' => $count]);
                    break;
                case 'password_reset_tokens':
                    $options = $days === 0 ? ['--days' => $days, '--force' => true] : ['--days' => $days];
                    Artisan::call('dls:admin:cleanup-password-reset-tokens', $options);
                    $output = Artisan::output();
                    $count = $this->extractCountFromOutput($output);
                    $message = __('admin/settings/systems/database.cleanup_success', ['count' => $count]);
                    break;
                case 'trusted_devices':
                    $options = $days === 0 ? ['--days' => $days, '--force' => true] : ['--days' => $days];
                    Artisan::call('dls:admin:cleanup-trusted-devices', $options);
                    $output = Artisan::output();
                    $count = $this->extractCountFromOutput($output);
                    $message = __('admin/settings/systems/database.cleanup_success', ['count' => $count]);
                    break;
                case 'two_factor_attempts':
                    $options = $days === 0 ? ['--days' => $days, '--force' => true] : ['--days' => $days];
                    Artisan::call('dls:admin:cleanup-two-factor-attempts', $options);
                    $output = Artisan::output();
                    $count = $this->extractCountFromOutput($output);
                    $message = __('admin/settings/systems/database.cleanup_success', ['count' => $count]);
                    break;
                case 'two_factor_tokens':
                    $options = $days === 0 ? ['--days' => $days, '--force' => true] : ['--days' => $days];
                    Artisan::call('dls:admin:cleanup-two-factor-tokens', $options);
                    $output = Artisan::output();
                    $count = $this->extractCountFromOutput($output);
                    $message = __('admin/settings/systems/database.cleanup_success', ['count' => $count]);
                    break;
                case 'recovery_codes':
                    $options = $days === 0 ? ['--days' => $days, '--force' => true] : ['--days' => $days];
                    Artisan::call('dls:admin:cleanup-recovery-codes', $options);
                    $output = Artisan::output();
                    $count = $this->extractCountFromOutput($output);
                    $message = __('admin/settings/systems/database.cleanup_success', ['count' => $count]);
                    break;
                case 'all':
                    $totalCount = 0;
                    $allDays = (int) $request->input('all_days', 30);
                    
                    $cleanupTypes = [
                        'login_attempts',
                        'password_reset_tokens',
                        'two_factor_attempts',
                        'two_factor_tokens',
                        'recovery_codes',
                        'passkeys',
                        'sessions'
                    ];
                    
                    foreach ($cleanupTypes as $cleanupType) {
                        $commandName = 'dls:admin:cleanup-' . str_replace('_', '-', $cleanupType);
                        $options = ['--days' => $allDays, '--force' => true];
                        
                        Artisan::call($commandName, $options);
                        $output = Artisan::output();
                        $totalCount += $this->extractCountFromOutput($output);
                    }
                    
                    Artisan::call('dls:admin:cleanup-cache', ['--expired-only' => true, '--force' => true]);
                    $output = Artisan::output();
                    $totalCount += $this->extractCountFromOutput($output);
                    
                    $message = __('admin/settings/systems/database.cleanup_success', ['count' => $totalCount]);
                    break;
                default:
                    // プラグインテーブルのクリーンアップを試行
                    if (str_starts_with($type, 'plugin:')) {
                        $result = $this->handlePluginTableCleanup($type, $days);
                        $success = $result['success'];
                        $message = $result['message'];
                        $count = $result['count'] ?? 0;
                    } else {
                        $success = false;
                        $message = __('admin/settings/systems/database.cleanup_error', ['error' => 'Invalid cleanup type']);
                    }
            }
        } catch (\Exception $e) {
            $success = false;
            $message = __('admin/settings/systems/database.cleanup_error', ['error' => $e->getMessage()]);
        }

        if ($success) {
            return redirect()->route('admin.settings.systems.database')->with('success', $message);
        } else {
            return redirect()->route('admin.settings.systems.database')->with('error', $message);
        }
    }

    /**
     * プラグインのクリーンアップ設定を取得
     */
    private function getPluginCleanupInfo(): array
    {
        $pluginCleanupInfo = [];
        
        // 有効なプラグインを取得
        $plugins = DB::table('plugins')
            ->whereNotNull('enabled_at')
            ->get();
        
        foreach ($plugins as $plugin) {
            $pluginJsonPath = base_path("plugins/{$plugin->directory}/plugin.json");
            
            if (!File::exists($pluginJsonPath)) {
                continue;
            }
            
            try {
                $pluginData = json_decode(File::get($pluginJsonPath), true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    continue;
                }
                
                $cleanupTables = $pluginData['cleanup']['tables'] ?? [];
                
                if (empty($cleanupTables)) {
                    continue;
                }
                
                $locale = app()->getLocale();
                
                foreach ($cleanupTables as $tableConfig) {
                    $tableName = $tableConfig['name'] ?? '';
                    if (empty($tableName)) {
                        continue;
                    }
                    
                    // 説明を取得（多言語対応）
                    $description = $tableConfig['description'] ?? '';
                    if (is_array($description)) {
                        $description = $description[$locale] ?? $description['en'] ?? $description['ja'] ?? '';
                    }
                    
                    $pluginCleanupInfo["plugin:{$plugin->slug}:{$tableName}"] = [
                        'plugin_name' => $plugin->name,
                        'plugin_slug' => $plugin->slug,
                        'table' => $tableName,
                        'name' => $description ?: $tableName,
                        'description' => $description,
                        'date_column' => $tableConfig['date_column'] ?? 'created_at',
                        'default_days' => $tableConfig['default_days'] ?? 30,
                    ];
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        
        return $pluginCleanupInfo;
    }

    /**
     * プラグインテーブルのクリーンアップを処理
     */
    private function handlePluginTableCleanup(string $type, int $days): array
    {
        // type形式: plugin:slug:table_name
        $parts = explode(':', $type, 3);
        
        if (count($parts) !== 3) {
            return [
                'success' => false,
                'message' => __('admin/settings/systems/database.cleanup_error', ['error' => 'Invalid plugin cleanup type']),
                'count' => 0,
            ];
        }
        
        $pluginSlug = $parts[1];
        $tableName = $parts[2];
        
        // プラグインのクリーンアップ設定を取得して日付カラムを確認
        $pluginCleanupInfo = $this->getPluginCleanupInfo();
        $key = "plugin:{$pluginSlug}:{$tableName}";
        
        if (!isset($pluginCleanupInfo[$key])) {
            return [
                'success' => false,
                'message' => __('admin/settings/systems/database.cleanup_error', ['error' => 'Table not allowed for cleanup']),
                'count' => 0,
            ];
        }
        
        $dateColumn = $pluginCleanupInfo[$key]['date_column'];
        
        $options = [
            '--plugin' => $pluginSlug,
            '--table' => $tableName,
            '--days' => $days,
            '--date-column' => $dateColumn,
            '--force' => true,
        ];
        
        Artisan::call('dls:admin:cleanup-plugin-table', $options);
        $output = Artisan::output();
        $count = $this->extractCountFromOutput($output);
        
        return [
            'success' => true,
            'message' => __('admin/settings/systems/database.cleanup_success', ['count' => $count]),
            'count' => $count,
        ];
    }

    /**
     * コマンド出力から削除件数を抽出
     */
    private function extractCountFromOutput($output)
    {
        if (preg_match('/DELETED_COUNT: (\d+)/', $output, $matches)) {
            return (int) $matches[1];
        }
        
        if (preg_match('/Successfully deleted (\d+)/', $output, $matches)) {
            return (int) $matches[1];
        }
        
        if (preg_match('/Successfully deleted all (\d+)/', $output, $matches)) {
            return (int) $matches[1];
        }
        
        if (preg_match('/(\d+) 件の.*を正常に削除しました/', $output, $matches)) {
            return (int) $matches[1];
        }
        
        if (preg_match('/(\d+) 件の.*記録を正常に削除しました/', $output, $matches)) {
            return (int) $matches[1];
        }
        
        return 0;
    }
}
