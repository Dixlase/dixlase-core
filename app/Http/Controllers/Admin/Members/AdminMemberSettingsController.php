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

namespace App\Http\Controllers\Admin\Members;

use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Models\BaseSetting;
use App\Models\SecuritySetting;
use App\Enums\AuthenticationMode;
use App\Contracts\Repositories\MemberSettingRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminMemberSettingsController extends AdminLoggedInController
{
    protected MemberSettingRepositoryInterface $memberSettingRepository;

    public function __construct(MemberSettingRepositoryInterface $memberSettingRepository)
    {
        parent::__construct();
        $this->memberSettingRepository = $memberSettingRepository;
        
        // index()メソッドが呼ばれる前にloadViewParams()を自動実行
        $this->middleware(function ($request, $next) {
            if ($request->route()->getActionMethod() === 'index') {
                $this->loadViewParams();
            }
            return $next($request);
        });
    }

    /**
     * メンバー設定概要画面
     */
    public function index()
    {
        return view('admin.members.settings.index', $this->viewParams);
    }

    /**
     * 全メンバー強制ログアウト
     */
    public function forceLogoutAll()
    {
        $currentUserId = Auth::guard('member')->id();
        $sessionTable = config('session.table', 'members_sessions');
        
        if ($sessionTable && DB::getSchemaBuilder()->hasTable($sessionTable)) {
            $deletedCount = DB::table($sessionTable)
                ->where('user_id', '!=', $currentUserId)
                ->whereNotNull('user_id')
                ->delete();
            
            return redirect()->route('admin.members.settings')
                ->with('success', __('admin/members/force_logout_all_success', ['count' => $deletedCount]));
        }

        return redirect()->route('admin.members.settings')
            ->with('error', __('admin/members/force_logout_all_error'));
    }

    /**
     * ビューパラメータを読み込む
     */
    protected function loadViewParams(): void
    {
        // パスワード設定はセキュリティ設定に移動
        // ログイン通知設定はセキュリティ設定に移動

        $twoFaForceMode = (int) $this->memberSettingRepository->get('two_fa_mode', AuthenticationMode::Disabled->value);
        
        if (old('two_fa_force_mode') !== null) {
            $twoFaForceMode = (int) old('two_fa_force_mode');
        }
        
        $twoFactorGlobalOptions = collect(config('admin.global_two_factor_mode'))
            ->map(function ($value) {
                $mode = AuthenticationMode::tryFrom($value);
                return [
                    'value' => (string) $value,
                    'label' => $mode ? $mode->twoFactorLabel() : '',
                ];
            })
            ->values()
            ->toArray();
        
        // パスキーモード設定（0=無効, 1=有効, 2=プロフィール設定に従う）
        $twoFaPasskeyMode = (int) $this->memberSettingRepository->get('two_fa_passkey_mode', '2');
        
        if (old('two_fa_passkey_mode') !== null) {
            $twoFaPasskeyMode = (int) old('two_fa_passkey_mode');
        }

        // デフォルトの二段階認証方法（0=メール, 1=パスキー）
        $twoFaDefaultMethod = (int) $this->memberSettingRepository->get('two_fa_default_method', '0');
        
        if (old('two_fa_default_method') !== null) {
            $twoFaDefaultMethod = (int) old('two_fa_default_method');
        }

        // ログイン試行制限設定はセキュリティ設定に移動
        // セッション設定は廃止（セキュリティ設定のセッション設定を使用）

        // 二段階認証の詳細設定はセキュリティ設定に移動

        $isMailServerTested = $this->isMailServerTested();
        $mailConnectionTestDate = BaseSetting::getValue('mail_connection_test_date');

        // パスワード設定はセキュリティ設定に移動
        // ログイン通知設定はセキュリティ設定に移動

        // ログイン試行制限設定はセキュリティ設定に移動

        // 二段階認証設定
        $this->viewParams['twoFaForceMode'] = $twoFaForceMode;
        $this->viewParams['twoFaGlobalOptions'] = $twoFactorGlobalOptions;
        $this->viewParams['twoFaPasskeyMode'] = $twoFaPasskeyMode;
        $this->viewParams['twoFaDefaultMethod'] = $twoFaDefaultMethod;

        // メールサーバー設定状態
        $this->viewParams['isMailServerTested'] = $isMailServerTested;
        $this->viewParams['mailConnectionTestDate'] = $mailConnectionTestDate;

        // CAPTCHA設定
        $captchaEnabled = filter_var(SecuritySetting::get('captcha_enabled', false), FILTER_VALIDATE_BOOLEAN);
        $captchaAuthenticationResult = filter_var(SecuritySetting::get('captcha_authentication_result', false), FILTER_VALIDATE_BOOLEAN);
        $captchaAdminLoginEnabled = (bool) $this->memberSettingRepository->get('captcha_admin_login_enabled', false);
        $captchaPasswordResetEnabled = (bool) $this->memberSettingRepository->get('captcha_password_reset_enabled', false);
        $captchaAvailable = $captchaEnabled && $captchaAuthenticationResult;
        $this->viewParams['captchaEnabled'] = $captchaEnabled;
        $this->viewParams['captchaAuthenticationResult'] = $captchaAuthenticationResult;
        $this->viewParams['captchaAvailable'] = $captchaAvailable;
        $this->viewParams['captchaAdminLoginEnabled'] = $captchaAdminLoginEnabled;
        $this->viewParams['captchaPasswordResetEnabled'] = $captchaPasswordResetEnabled;
    }

    protected function isMailServerTested(): bool
    {
        $connectionTested = (bool) BaseSetting::getValue('mail_connection_tested', false);
        $sendTested = (bool) BaseSetting::getValue('mail_send_tested', false);
        $receiveTested = (bool) BaseSetting::getValue('mail_receive_tested', false);
        
        return $connectionTested && $sendTested && $receiveTested;
    }
}
