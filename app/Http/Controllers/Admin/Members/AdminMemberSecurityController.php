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
use Illuminate\Http\Request;
use App\Models\Member;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminMemberSecurityController extends AdminLoggedInController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * メンバー強制ログアウト
     */
    public function forceLogout(Member $member)
    {
        $sessionTable = config('session.table', 'sessions');
        
        if ($sessionTable && DB::getSchemaBuilder()->hasTable($sessionTable)) {
            DB::table($sessionTable)
                ->where('user_id', $member->id)
                ->delete();
        }

        return redirect()->route('admin.members.edit', ['member' => $member->id])
            ->with('success', __('admin.members.messages.force_logout_success'));
    }

    /**
     * 2FAロックアウト解除
     */
    public function unlock2fa(Member $member)
    {
        \App\Models\Member2faAttempt::where('member_id', $member->id)->delete();
        \App\Models\MemberLoginAttempt::where('identifier', $member->email)->delete();

        return redirect()->route('admin.members.edit', ['member' => $member->id])
            ->with('success', __('admin.members.messages.unlock_lockout_success'));
    }

    /**
     * 全メンバー強制ログアウト
     */
    public function forceLogoutAll()
    {
        $currentUserId = Auth::guard('member')->id();
        $sessionTable = config('session.table', 'sessions');
        
        if ($sessionTable && DB::getSchemaBuilder()->hasTable($sessionTable)) {
            $deletedCount = DB::table($sessionTable)
                ->where('user_id', '!=', $currentUserId)
                ->whereNotNull('user_id')
                ->delete();
            
            return redirect()->route('admin.members.settings')
                ->with('success', __('admin.members.force_logout_all_success', ['count' => $deletedCount]));
        }

        return redirect()->route('admin.members.settings')
            ->with('error', __('admin.members.force_logout_all_error'));
    }

    /**
     * 認証メール送信
     */
    public function sendVerificationEmail(Member $member)
    {
        try {
            if (!$this->isMailServerTested()) {
                return response()->json([
                    'success' => false,
                    'message' => __('admin.members.form.mail_server_not_tested')
                ], 400);
            }

            $member->email_verified_at = null;
            $member->save();

            $sessionTable = config('session.table', 'sessions');
            if ($sessionTable && DB::getSchemaBuilder()->hasTable($sessionTable)) {
                DB::table($sessionTable)
                    ->where('user_id', $member->id)
                    ->delete();
            }

            $member->sendEmailVerificationNotification('resend');

            session()->flash('success', __('admin.members.messages.verification_email_sent'));

            return response()->json([
                'success' => true,
                'redirect' => route('admin.members.edit', ['member' => $member->id])
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to send verification email: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => __('admin.members.messages.verification_email_failed')
            ], 500);
        }
    }

    /**
     * Passkey削除
     */
    public function revokePasskey(Request $request, Member $member, string $credentialId)
    {
        $passkeyService = app(\App\Services\PasskeyAuthenticationService::class);
        
        try {
            if ($credentialId === 'all') {
                $deletedCount = $passkeyService->revokeAllCredentials($member);
                
                return response()->json([
                    'success' => true,
                    'message' => __('admin.profile.passkey_deleted_all', ['count' => $deletedCount])
                ]);
            }
            
            $deleted = $passkeyService->revokeCredential($member, $credentialId);
            
            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'message' => __('admin.profile.passkey_not_found')
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => __('admin.profile.passkey_deleted')
            ]);
        } catch (\Exception $e) {
            \Log::error('[Member Passkey Delete] Exception caught', [
                'member_id' => $member->id,
                'credential_id' => $credentialId,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => __('admin.profile.passkey_delete_error')
            ], 500);
        }
    }

    /**
     * 回復コード削除
     */
    public function revokeRecoveryCodes(Request $request, Member $member)
    {
        $recoveryCodeService = app(\App\Services\RecoveryCodeService::class);
        
        try {
            $deletedCount = $recoveryCodeService->revokeAll($member);
            
            return response()->json([
                'success' => true,
                'message' => __('admin.members.form.recovery_codes_deleted', ['count' => $deletedCount])
            ]);
        } catch (\Exception $e) {
            \Log::error('[Member Recovery Code Delete] Exception caught', [
                'member_id' => $member->id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => __('admin.members.form.recovery_codes_delete_error')
            ], 500);
        }
    }

    private function isMailServerTested(): bool
    {
        $connectionTested = (bool) \App\Models\BaseSetting::getValue('mail_connection_tested', false);
        $sendTested = (bool) \App\Models\BaseSetting::getValue('mail_send_tested', false);
        $receiveTested = (bool) \App\Models\BaseSetting::getValue('mail_receive_tested', false);
        
        return $connectionTested && $sendTested && $receiveTested;
    }
}
