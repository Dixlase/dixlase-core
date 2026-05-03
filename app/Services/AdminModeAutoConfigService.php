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

namespace App\Services;

use App\Contracts\Repositories\MediaSettingRepositoryInterface;
use App\Contracts\Repositories\SecuritySettingRepositoryInterface;
use App\Enums\CspBlocklistAction;
use App\Enums\CspMode;
use App\Enums\LogLevel;
use App\Helpers\ConfigHelper;
use App\Helpers\EnvHelper;
use Illuminate\Support\Facades\Log;

/**
 * @internal コア専用。プラグイン/テーマから参照しないこと
 *
 * かんたんモードへの切り替え時に、Hidden/Partial項目の自動設定値を適用するサービス
 *
 * 各メニューの自動設定メソッドは個別に呼び出すことも、
 * applyAll() で一括適用することもできます。
 */
class AdminModeAutoConfigService
{
    public function __construct(
        protected SecuritySettingRepositoryInterface $securitySettingRepository,
        protected MediaSettingRepositoryInterface $mediaSettingRepository
    ) {}

    /**
     * 全てのHidden/Partial項目の自動設定値を一括適用
     *
     * @return array<string, bool> メニューキーごとの適用結果
     */
    public function applyAll(): array
    {
        $results = [];

        $methods = [
            'settings.security.password' => 'applyPasswordDefaults',
            'settings.security.login' => 'applyLoginDefaults',
            'settings.security.two-fa' => 'applyTwoFaDefaults',
            'settings.security.notifications' => 'applyNotificationDefaults',
            'settings.security.session' => 'applySessionDefaults',
            'settings.security.csp' => 'applyCspDefaults',
            'settings.security.environment' => 'applyEnvironmentDefaults',
            'settings.security.extensions' => 'applyExtensionsDefaults',
            'media.settings' => 'applyMediaDefaults',
        ];

        foreach ($methods as $menuKey => $method) {
            try {
                $this->{$method}();
                $results[$menuKey] = true;

                Log::channel('admin_activity')->info("かんたんモード自動設定を適用: {$menuKey}", [
                    'menu_key' => $menuKey,
                ]);
            } catch (\Exception $e) {
                $results[$menuKey] = false;

                Log::channel('admin_activity')->error("かんたんモード自動設定の適用に失敗: {$menuKey}", [
                    'menu_key' => $menuKey,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * パスワード設定の自動設定値を適用
     *
     * 対象: settings.security.password (Hidden)
     * - password_min_length: 8文字（NIST推奨の下限）
     * - password_require_uppercase: 有効（複雑性確保）
     * - password_require_number: 有効（複雑性確保）
     * - password_require_symbol: 有効（複雑性確保）
     * - password_reset_enabled: 有効（利便性確保）
     * - pwned_password_check_enabled: 有効（漏洩パスワード防止）
     */
    public function applyPasswordDefaults(): void
    {
        $this->securitySettingRepository->set('password_min_length', '8');
        $this->securitySettingRepository->set('password_require_uppercase', '1');
        $this->securitySettingRepository->set('password_require_number', '1');
        $this->securitySettingRepository->set('password_require_symbol', '1');
        $this->securitySettingRepository->set('password_reset_enabled', '1');
        $this->securitySettingRepository->set('pwned_password_check_enabled', '1');
    }

    /**
     * ログイン設定の自動設定値を適用（試行制限の詳細パラメータ）
     *
     * 対象: settings.security.login (Partial)
     * ログイン通知のON/OFF/条件はユーザー操作可。以下のパラメータを自動設定:
     * - login_attempt_limit_enabled: 有効（ブルートフォース防御）
     * - login_attempt_max_attempts: 5回
     * - login_attempt_max_attempts_ip: 20回（共有IP環境考慮）
     * - login_attempt_time_window: 15分
     * - login_attempt_lockout_duration: 30分
     * - login_attempt_lockout_notification_enabled: 有効（攻撃検知）
     */
    public function applyLoginDefaults(): void
    {
        $this->securitySettingRepository->set('login_attempt_limit_enabled', '1');
        $this->securitySettingRepository->set('login_attempt_max_attempts', '5');
        $this->securitySettingRepository->set('login_attempt_max_attempts_ip', '20');
        $this->securitySettingRepository->set('login_attempt_time_window', '15');
        $this->securitySettingRepository->set('login_attempt_lockout_duration', '30');
        $this->securitySettingRepository->set('login_attempt_lockout_notification_enabled', '1');
    }

    /**
     * 二段階認証設定の自動設定値を適用（詳細パラメータ）
     *
     * 対象: settings.security.two-fa (Partial)
     * 2FA/パスキーのON/OFF/条件はユーザー操作可。以下のパラメータを自動設定:
     * - two_fa_passkey_max_devices: 5台
     * - two_fa_expire_minutes: 10分
     * - two_fa_resend_interval_seconds: 60秒（スパム防止）
     * - two_fa_max_attempts: 5回
     * - two_fa_attempt_window: 15分
     * - two_fa_lockout_duration: 30分
     * - two_fa_lockout_notification_enabled: 有効（セキュリティ監視）
     * - two_fa_recovery_codes_count: 10個
     * - two_fa_recovery_code_regenerate_interval: 24時間（= 1日、乱用防止）
     */
    public function applyTwoFaDefaults(): void
    {
        $this->securitySettingRepository->set('two_fa_passkey_max_devices', '5');
        $this->securitySettingRepository->set('two_fa_expire_minutes', '10');
        $this->securitySettingRepository->set('two_fa_resend_interval_seconds', '60');
        $this->securitySettingRepository->set('two_fa_max_attempts', '5');
        $this->securitySettingRepository->set('two_fa_attempt_window', '15');
        $this->securitySettingRepository->set('two_fa_lockout_duration', '30');
        $this->securitySettingRepository->set('two_fa_lockout_notification_enabled', '1');
        $this->securitySettingRepository->set('two_fa_recovery_codes_count', '10');
        $this->securitySettingRepository->set('two_fa_recovery_code_regenerate_interval', '1');
    }

    /**
     * エラー通知設定の自動設定値を適用
     *
     * 対象: settings.security.notifications (Hidden)
     * - notification_enabled: 有効（問題の早期検知）
     * - notification_log_levels: Critical以上（Emergency=8, Alert=7, Critical=6）
     */
    public function applyNotificationDefaults(): void
    {
        $this->securitySettingRepository->set('notification_enabled', '1');

        $criticalAndAbove = implode(',', [
            LogLevel::Emergency->value,
            LogLevel::Alert->value,
            LogLevel::Critical->value,
        ]);
        $this->securitySettingRepository->set('notification_log_levels', $criticalAndAbove);
    }

    /**
     * セッション設定の自動設定値を適用
     *
     * 対象: settings.security.session (Hidden)
     * - session_lifetime: 120分（デフォルト値維持）
     * - session_encrypt: false（デフォルト値維持）
     */
    public function applySessionDefaults(): void
    {
        ConfigHelper::setSessionLifetime(120);
        ConfigHelper::setSessionEncrypt(false);
    }

    /**
     * CSP設定の自動設定値を適用
     *
     * 対象: settings.security.csp (Hidden)
     * - csp_enabled: ON（セキュリティ基盤）
     * - csp_mode: 標準モード（開発モード不要）
     * - csp_log_violations: ON（調査に必須）
     * - csp_exclude_dev_tools: ON（ノイズ軽減）
     * - csp_blocklist_check_enabled: ON（セキュリティモニタリング）
     * - csp_blocklist_action: 警告のみ（正規スクリプト遮断リスク回避）
     * - csp_blocklist_enabled_categories: 全カテゴリON
     */
    public function applyCspDefaults(): void
    {
        $this->securitySettingRepository->set('csp_enabled', '1');
        $this->securitySettingRepository->set('csp_mode', (string) CspMode::Standard->value);
        $this->securitySettingRepository->set('csp_log_violations', '1');
        $this->securitySettingRepository->set('csp_exclude_dev_tools', '1');
        $this->securitySettingRepository->set('csp_blocklist_check_enabled', '1');
        $this->securitySettingRepository->set('csp_blocklist_action', (string) CspBlocklistAction::Warn->value);
        $this->securitySettingRepository->set('csp_blocklist_enabled_categories', 'tracking,malware,phishing,cryptominer');
    }

    /**
     * 環境設定の自動設定値を適用
     *
     * 対象: settings.security.environment (Hidden)
     * - APP_ENV: production（本番環境）
     * - APP_DEBUG: false（情報漏洩防止）
     */
    public function applyEnvironmentDefaults(): void
    {
        EnvHelper::update([
            'app_env' => 'production',
            'app_debug' => 'false',
        ]);
    }

    /**
     * メディア設定の自動設定値を適用
     *
     * 対象: media.settings (Partial)
     * ファイルタイプ・サイズ上限はユーザー操作可。以下のセキュリティ項目を自動設定:
     * - mime_validation_enabled: 有効（ファイル偽装防止）
     * - svg_sanitization_enabled: 有効（SVG内スクリプト防止）
     * - zip_security_enabled: 有効（ZIP爆弾・悪意あるファイル検出）
     */
    public function applyMediaDefaults(): void
    {
        $this->mediaSettingRepository->set('mime_validation_enabled', '1');
        $this->mediaSettingRepository->set('svg_sanitization_enabled', '1');
        $this->mediaSettingRepository->set('zip_security_enabled', '1');
    }

    /**
     * 拡張機能セキュリティ設定の自動設定値を適用
     *
     * 対象: settings.security.extensions (Partial)
     * プリセット・各種フラグはユーザー操作可。以下のパラメータを自動設定:
     * - extension_audit_max_age_days: 30 日（監査スキャンの期限を 1 ヶ月に固定）
     */
    public function applyExtensionsDefaults(): void
    {
        $this->securitySettingRepository->set('extension_audit_max_age_days', '30');
    }
}
