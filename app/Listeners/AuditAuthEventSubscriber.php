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

namespace App\Listeners;

use App\Facades\Audit;
use App\Models\AuditLog;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\OtherDeviceLogout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Events\Dispatcher;

/**
 * 認証イベントの監査ログ記録
 */
class AuditAuthEventSubscriber
{
    /**
     * ログイン成功
     */
    public function handleLogin(Login $event): void
    {
        Audit::logAuth(AuditLog::ACTION_LOGIN, [
            'actor' => $event->user,
            'outcome' => AuditLog::OUTCOME_SUCCESS,
            'context' => [
                'message' => 'ログインしました',
                'guard' => $event->guard,
                'remember' => $event->remember,
            ],
        ]);
    }

    /**
     * ログイン失敗
     */
    public function handleFailed(Failed $event): void
    {
        Audit::logAuth(AuditLog::ACTION_LOGIN_FAILED, [
            'actor' => $event->user,
            'outcome' => AuditLog::OUTCOME_FAILURE,
            'severity' => AuditLog::SEVERITY_WARNING,
            'context' => [
                'message' => 'ログインに失敗しました',
                'guard' => $event->guard,
                'credentials' => array_keys($event->credentials),
            ],
        ]);
    }

    /**
     * ログアウト
     */
    public function handleLogout(Logout $event): void
    {
        Audit::logAuth(AuditLog::ACTION_LOGOUT, [
            'actor' => $event->user,
            'outcome' => AuditLog::OUTCOME_SUCCESS,
            'context' => [
                'message' => 'ログアウトしました',
                'guard' => $event->guard,
            ],
        ]);
    }

    /**
     * ロックアウト
     */
    public function handleLockout(Lockout $event): void
    {
        Audit::logSecurity(AuditLog::ACTION_LOCKOUT_TRIGGERED, [
            'outcome' => AuditLog::OUTCOME_DENIED,
            'severity' => AuditLog::SEVERITY_CRITICAL,
            'context' => [
                'message' => 'ログイン試行回数超過によりロックアウトされました',
                'ip' => $event->request->ip(),
            ],
        ]);
    }

    /**
     * パスワードリセット
     */
    public function handlePasswordReset(PasswordReset $event): void
    {
        Audit::logAccount(AuditLog::ACTION_PASSWORD_RESET, [
            'actor' => $event->user,
            'target' => $event->user,
            'outcome' => AuditLog::OUTCOME_SUCCESS,
            'severity' => AuditLog::SEVERITY_NOTICE,
            'context' => [
                'message' => 'パスワードがリセットされました',
            ],
        ]);
    }

    /**
     * ユーザー登録
     */
    public function handleRegistered(Registered $event): void
    {
        Audit::logAccount(AuditLog::ACTION_MEMBER_CREATED, [
            'target' => $event->user,
            'outcome' => AuditLog::OUTCOME_SUCCESS,
            'context' => [
                'message' => '新規ユーザーが登録されました',
            ],
        ]);
    }

    /**
     * 他デバイスからのログアウト
     */
    public function handleOtherDeviceLogout(OtherDeviceLogout $event): void
    {
        Audit::logAuth(AuditLog::ACTION_FORCED_LOGOUT, [
            'actor' => $event->user,
            'outcome' => AuditLog::OUTCOME_SUCCESS,
            'severity' => AuditLog::SEVERITY_NOTICE,
            'context' => [
                'message' => '他のデバイスからログアウトしました',
                'guard' => $event->guard,
            ],
        ]);
    }

    /**
     * メール認証完了
     */
    public function handleVerified(Verified $event): void
    {
        Audit::logAccount('email_verified', [
            'actor' => $event->user,
            'target' => $event->user,
            'outcome' => AuditLog::OUTCOME_SUCCESS,
            'context' => [
                'message' => 'メールアドレスが認証されました',
            ],
        ]);
    }

    /**
     * イベント購読の登録
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class => 'handleLogin',
            Failed::class => 'handleFailed',
            Logout::class => 'handleLogout',
            Lockout::class => 'handleLockout',
            PasswordReset::class => 'handlePasswordReset',
            Registered::class => 'handleRegistered',
            OtherDeviceLogout::class => 'handleOtherDeviceLogout',
            Verified::class => 'handleVerified',
        ];
    }
}
