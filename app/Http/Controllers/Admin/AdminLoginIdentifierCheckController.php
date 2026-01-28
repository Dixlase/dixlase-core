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

namespace App\Http\Controllers\Admin;

use App\Models\Member;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * ログイン識別子確認コントローラー
 * 
 * メールアドレスまたはアカウント名の存在確認を行う
 * セキュリティ対策：レート制限、タイミング攻撃対策、監査ログ記録
 */
class AdminLoginIdentifierCheckController extends AdminController
{
    /**
     * メンバー存在確認
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function check(Request $request)
    {
        $request->validate([
            'login' => 'required|string|max:255',
        ]);

        $login = $request->input('login');
        $ipAddress = $request->ip();
        
        // レート制限キー（IP + identifier）
        $rateLimitKey = 'login-identifier-check:' . $ipAddress . ':' . md5($login);
        
        // レート制限チェック（5回/5分）
        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            
            // ロックアウトログ記録
            AuditLog::logSecurity(AuditLog::ACTION_LOCKOUT_TRIGGERED, [
                'severity' => AuditLog::SEVERITY_CRITICAL,
                'outcome' => AuditLog::OUTCOME_DENIED,
                'context' => [
                    'reason' => 'login_identifier_check_rate_limit',
                    'login_identifier' => $login,
                    'available_in_seconds' => $seconds,
                ],
            ]);
            
            // ファイルログにも記録
            Log::warning('Login identifier check rate limit exceeded', [
                'ip' => $ipAddress,
                'login' => $login,
                'available_in' => $seconds,
            ]);
            
            throw ValidationException::withMessages([
                'login' => __('auth.throttle', ['seconds' => $seconds]),
            ]);
        }

        // タイミング攻撃対策：常に一定時間待機（100-300ms）
        $delayMs = random_int(100, 300);
        usleep($delayMs * 1000);
        
        // メンバー検索（メールアドレスまたはアカウント名）
        $member = Member::where('email', $login)
            ->orWhere('account_name', $login)
            ->first();
        
        // レート制限カウンターを増やす
        RateLimiter::hit($rateLimitKey, 300); // 5分間保持
        
        if ($member) {
            // メンバー存在確認成功
            AuditLog::logAuth(AuditLog::ACTION_LOGIN_IDENTIFIER_CHECK, [
                'severity' => AuditLog::SEVERITY_INFO,
                'outcome' => AuditLog::OUTCOME_SUCCESS,
                'actor' => $member,
                'context' => [
                    'login_identifier' => $login,
                    'identifier_type' => filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'account_name',
                ],
            ]);
            
            // ファイルログにも記録
            Log::info('Login identifier check successful', [
                'member_id' => $member->id,
                'login' => $login,
                'ip' => $ipAddress,
            ]);
            
            return response()->json([
                'exists' => true,
                'has_passkey' => $member->passkeys()->count() > 0,
            ]);
        } else {
            // メンバー存在確認失敗（セキュリティログ）
            AuditLog::logSecurity(AuditLog::ACTION_LOGIN_IDENTIFIER_NOT_FOUND, [
                'severity' => AuditLog::SEVERITY_WARNING,
                'outcome' => AuditLog::OUTCOME_FAILURE,
                'context' => [
                    'login_identifier' => $login,
                    'identifier_type' => filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'account_name',
                ],
            ]);
            
            // ファイルログにも記録
            Log::warning('Login identifier not found', [
                'login' => $login,
                'ip' => $ipAddress,
            ]);
            
            // エラーメッセージは統一（ユーザー列挙攻撃対策）
            throw ValidationException::withMessages([
                'login' => __('auth.failed'),
            ]);
        }
    }
}
