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

namespace App\Repositories;

use App\Contracts\Repositories\MemberSettingRepositoryInterface;
use App\Models\MemberSetting;

/**
 * メンバー設定リポジトリ実装
 */
class MemberSettingRepository extends AbstractSettingRepository implements MemberSettingRepositoryInterface
{
    /**
     * SecuritySettingに移動済みの設定キー
     */
    protected static array $movedToSecuritySettings = [
        'password_min_length', 'password_min_length_default',
        'password_require_uppercase', 'password_require_lowercase',
        'password_require_number', 'password_require_symbol',
        'login_notification_mode', 'login_notification_send_to_system', 'login_notification_system_email',
        'login_attempt_limit_enabled', 'login_attempt_max_attempts', 'login_attempt_max_attempts_ip',
        'login_attempt_time_window', 'login_attempt_lockout_duration',
        'login_attempt_lockout_notification_enabled', 'lockout_notification_enabled',
        'session_driver', 'session_encrypt', 'session_lifetime', 'session_member_lifetime',
        'two_fa_expire_minutes', 'two_fa_resend_interval_seconds', 'two_fa_max_attempts',
        'two_fa_attempt_window', 'two_fa_lockout_duration', 'two_fa_lockout_notification_enabled',
        'two_fa_recovery_codes_count', 'two_fa_recovery_code_regenerate_interval',
    ];

    /**
     * コンストラクタ
     */
    public function __construct()
    {
        $this->cachePrefix = 'member_setting:';
        $this->cacheAllKey = 'members_settings_all';
        $this->cacheTtl = 10;
        $this->keyColumn = 'key'; // MemberSettingは'key'カラムを使用
    }

    /**
     * {@inheritDoc}
     */
    protected function getModelClass(): string
    {
        return MemberSetting::class;
    }

    /**
     * 設定値を取得（移動済み設定のチェック付き）
     * 
     * @param string $key 設定キー
     * @param mixed $default デフォルト値
     * @return mixed
     * @throws \RuntimeException SecuritySettingに移動済みの設定にアクセスした場合
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (in_array($key, static::$movedToSecuritySettings)) {
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
            throw new \RuntimeException(
                "設定キー '{$key}' は MemberSettingRepository から SecuritySettingRepository に移動されました。\n" .
                "SecuritySettingRepository::get('{$key}') または SecuritySetting::getValue('{$key}') を使用してください。\n" .
                "ファイル: " . ($backtrace[1]['file'] ?? 'unknown') . "\n" .
                "行: " . ($backtrace[1]['line'] ?? 'unknown')
            );
        }
        
        return parent::get($key, $default);
    }
}
