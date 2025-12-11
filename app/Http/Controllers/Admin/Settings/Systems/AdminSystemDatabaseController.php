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
                'name' => __('admin.settings.systems.database.login_attempts.name'),
                'description' => __('admin.settings.systems.database.login_attempts.description'),
                'default_days' => 30,
                'command' => 'dls:admin:cleanup-login-attempts'
            ],
            'password_reset_tokens' => [
                'name' => __('admin.settings.systems.database.password_reset_tokens.name'),
                'description' => __('admin.settings.systems.database.password_reset_tokens.description'),
                'default_days' => 30,
                'command' => 'dls:admin:cleanup-password-reset-tokens'
            ],
            'two_factor_attempts' => [
                'name' => __('admin.settings.systems.database.two_factor_attempts.name'),
                'description' => __('admin.settings.systems.database.two_factor_attempts.description'),
                'default_days' => 30,
                'command' => 'dls:admin:cleanup-two-factor-attempts'
            ],
            'two_factor_tokens' => [
                'name' => __('admin.settings.systems.database.two_factor_tokens.name'),
                'description' => __('admin.settings.systems.database.two_factor_tokens.description'),
                'default_days' => 7,
                'command' => 'dls:admin:cleanup-two-factor-tokens'
            ],
            'recovery_codes' => [
                'name' => __('admin.settings.systems.database.recovery_codes.name'),
                'description' => __('admin.settings.systems.database.recovery_codes.description'),
                'default_days' => 90,
                'command' => 'dls:admin:cleanup-recovery-codes'
            ],
            'passkeys' => [
                'name' => __('admin.settings.systems.database.passkeys.name'),
                'description' => __('admin.settings.systems.database.passkeys.description'),
                'default_days' => 90,
                'command' => 'dls:admin:cleanup-passkeys'
            ],
            'cache_data' => [
                'name' => __('admin.settings.systems.database.cache_data.name'),
                'description' => __('admin.settings.systems.database.cache_data.description'),
                'default_days' => null,
                'command' => 'dls:admin:cleanup-cache'
            ],
            'sessions' => [
                'name' => __('admin.settings.systems.database.sessions.name'),
                'description' => __('admin.settings.systems.database.sessions.description'),
                'default_days' => 7,
                'command' => 'dls:admin:cleanup-sessions'
            ]
        ];

        $this->viewParams['cleanupInfo'] = $cleanupInfo;
        
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
                    $message = __('admin.settings.systems.database.cleanup_success', ['count' => $count]);
                    break;
                case 'password_reset_tokens':
                    $options = $days === 0 ? ['--days' => $days, '--force' => true] : ['--days' => $days];
                    Artisan::call('dls:admin:cleanup-password-reset-tokens', $options);
                    $output = Artisan::output();
                    $count = $this->extractCountFromOutput($output);
                    $message = __('admin.settings.systems.database.cleanup_success', ['count' => $count]);
                    break;
                case 'trusted_devices':
                    $options = $days === 0 ? ['--days' => $days, '--force' => true] : ['--days' => $days];
                    Artisan::call('dls:admin:cleanup-trusted-devices', $options);
                    $output = Artisan::output();
                    $count = $this->extractCountFromOutput($output);
                    $message = __('admin.settings.systems.database.cleanup_success', ['count' => $count]);
                    break;
                case 'two_factor_attempts':
                    $options = $days === 0 ? ['--days' => $days, '--force' => true] : ['--days' => $days];
                    Artisan::call('dls:admin:cleanup-two-factor-attempts', $options);
                    $output = Artisan::output();
                    $count = $this->extractCountFromOutput($output);
                    $message = __('admin.settings.systems.database.cleanup_success', ['count' => $count]);
                    break;
                case 'two_factor_tokens':
                    $options = $days === 0 ? ['--days' => $days, '--force' => true] : ['--days' => $days];
                    Artisan::call('dls:admin:cleanup-two-factor-tokens', $options);
                    $output = Artisan::output();
                    $count = $this->extractCountFromOutput($output);
                    $message = __('admin.settings.systems.database.cleanup_success', ['count' => $count]);
                    break;
                case 'recovery_codes':
                    $options = $days === 0 ? ['--days' => $days, '--force' => true] : ['--days' => $days];
                    Artisan::call('dls:admin:cleanup-recovery-codes', $options);
                    $output = Artisan::output();
                    $count = $this->extractCountFromOutput($output);
                    $message = __('admin.settings.systems.database.cleanup_success', ['count' => $count]);
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
                    
                    $message = __('admin.settings.systems.database.cleanup_success', ['count' => $totalCount]);
                    break;
                default:
                    $success = false;
                    $message = __('admin.settings.systems.database.cleanup_error', ['error' => 'Invalid cleanup type']);
            }
        } catch (\Exception $e) {
            $success = false;
            $message = __('admin.settings.systems.database.cleanup_error', ['error' => $e->getMessage()]);
        }

        if ($success) {
            return redirect()->route('admin.settings.systems.database')->with('success', $message);
        } else {
            return redirect()->route('admin.settings.systems.database')->with('error', $message);
        }
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
