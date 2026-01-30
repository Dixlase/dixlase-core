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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laragear\WebAuthn\WebAuthn;

/**
 * パスキーログインコントローラー
 * 
 * WebAuthnを使用したパスキー認証によるログイン処理
 */
class AdminPasskeyLoginController extends AdminController
{
    /**
     * パスキー認証のチャレンジを取得
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getChallenge(Request $request)
    {
        $request->validate([
            'login' => 'required|string|max:255',
        ]);

        $login = $request->input('login');
        
        // メンバー検索（メールアドレスまたはアカウント名）
        $member = Member::where('email', $login)
            ->orWhere('account_name', $login)
            ->first();
        
        if (!$member) {
            return response()->json([
                'error' => __('auth.failed'),
            ], 422);
        }

        // パスキーが登録されているか確認
        if ($member->webauthnCredentials()->count() === 0) {
            return response()->json([
                'error' => __('admin/auth.login.no_passkey_registered'),
            ], 422);
        }

        try {
            // WebAuthnチャレンジを生成
            $publicKey = WebAuthn::generateAssertion($member);
            
            // セッションにメンバーIDを保存（認証後に使用）
            session(['passkey_login_member_id' => $member->id]);
            
            // 監査ログ記録
            AuditLog::logAuth(AuditLog::ACTION_LOGIN_PASSKEY_CHALLENGE, [
                'severity' => AuditLog::SEVERITY_INFO,
                'outcome' => AuditLog::OUTCOME_SUCCESS,
                'actor' => $member,
                'context' => [
                    'login_identifier' => $login,
                ],
            ]);
            
            return response()->json([
                'publicKey' => $publicKey,
            ]);
        } catch (\Exception $e) {
            Log::error('Passkey challenge generation failed', [
                'member_id' => $member->id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'error' => __('common.error_occurred'),
            ], 500);
        }
    }

    /**
     * パスキー認証を検証してログイン
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verify(Request $request)
    {
        $memberId = session('passkey_login_member_id');
        
        if (!$memberId) {
            return response()->json([
                'error' => __('auth.failed'),
            ], 422);
        }

        $member = Member::find($memberId);
        
        if (!$member) {
            session()->forget('passkey_login_member_id');
            return response()->json([
                'error' => __('auth.failed'),
            ], 422);
        }

        try {
            // WebAuthn認証を検証
            $credential = WebAuthn::validateAssertion($member, $request);
            
            if (!$credential) {
                // 認証失敗ログ
                AuditLog::logAuth(AuditLog::ACTION_LOGIN_FAILED, [
                    'severity' => AuditLog::SEVERITY_WARNING,
                    'outcome' => AuditLog::OUTCOME_FAILURE,
                    'actor' => $member,
                    'context' => [
                        'reason' => 'passkey_verification_failed',
                        'authentication_method' => 'passkey',
                    ],
                ]);
                
                return response()->json([
                    'error' => __('auth.failed'),
                ], 422);
            }

            // ログイン成功
            Auth::guard('member')->login($member, true);
            session()->forget('passkey_login_member_id');
            
            // パスキー認証を記録（2FA制限のため）
            session(['login.auth_method' => 'passkey']);
            
            // 認証成功ログ
            AuditLog::logAuth(AuditLog::ACTION_LOGIN_SUCCESS, [
                'severity' => AuditLog::SEVERITY_INFO,
                'outcome' => AuditLog::OUTCOME_SUCCESS,
                'actor' => $member,
                'context' => [
                    'authentication_method' => 'passkey',
                    'credential_id' => $credential->id,
                ],
            ]);
            
            // ファイルログにも記録
            Log::info('Passkey login successful', [
                'member_id' => $member->id,
                'credential_id' => $credential->id,
                'ip' => $request->ip(),
            ]);
            
            // ログイン通知（設定に応じて）
            if ($member->login_notification_mode !== 0) {
                try {
                    $notificationService = app(\App\Services\AdminLoginNotificationService::class);
                    $notificationService->sendLoginNotification($member, $request, 'passkey');
                } catch (\Exception $e) {
                    Log::warning('Login notification failed', [
                        'member_id' => $member->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            
            return response()->json([
                'success' => true,
                'redirect' => route('admin.dashboard'),
            ]);
        } catch (\Exception $e) {
            Log::error('Passkey verification failed', [
                'member_id' => $member->id,
                'error' => $e->getMessage(),
            ]);
            
            // 認証失敗ログ
            AuditLog::logAuth(AuditLog::ACTION_LOGIN_FAILED, [
                'severity' => AuditLog::SEVERITY_WARNING,
                'outcome' => AuditLog::OUTCOME_FAILURE,
                'actor' => $member,
                'context' => [
                    'reason' => 'passkey_verification_error',
                    'error_message' => $e->getMessage(),
                    'authentication_method' => 'passkey',
                ],
            ]);
            
            return response()->json([
                'error' => __('auth.failed'),
            ], 422);
        }
    }
}
