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
use App\Http\Requests\Admin\Settings\Members\AdminSettingsMemberStoreRequest;
use Illuminate\Http\Request;
use App\Models\Member;
use App\Enums\TwoFactorMode;
use App\Enums\TwoFactorMethod;
use App\Enums\LoginNotificationMode;
use App\Enums\AppearanceMode;
use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use Illuminate\Support\Facades\Hash;
use App\Services\MailServerValidatorService;
use App\Contracts\Repositories\MemberSettingRepositoryInterface;
use Illuminate\Support\Facades\DB;

class AdminMemberController extends AdminLoggedInController
{
    protected MemberSettingRepositoryInterface $memberSettingRepository;

    public function __construct(MemberSettingRepositoryInterface $memberSettingRepository)
    {
        parent::__construct();
        $this->memberSettingRepository = $memberSettingRepository;
    }
    /**
     * メンバー一覧
     */
    public function index(Request $request)
    {
        $this->viewParams['members'] = Member::all();

        $search = $request->input('search');
        $roleFilter = $request->input('role', '');
        
        $statusFilter = $request->input('status');
        if ($statusFilter === null && !$request->hasAny(['search', 'role', 'page', 'per_page'])) {
            $statusFilter = '1';
        }
        
        $perPage = $request->input('per_page', 25);
        $allowedPerPage = [10, 25, 50, 100];
        if (!in_array($perPage, $allowedPerPage)) {
            $perPage = 25;
        }

        $members = Member::query()
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('id', 'like', '%' . $search . '%')
                      ->orWhere('name', 'like', '%' . $search . '%')
                      ->orWhere('email', 'like', '%' . $search . '%');
                });
            })
            ->when($roleFilter, function ($query, $roleFilter) {
                $query->where('role', $roleFilter);
            })
            ->when($statusFilter !== null, function ($query) use ($statusFilter) {
                $query->where('status', $statusFilter);
            })
            ->paginate($perPage);

        $members->appends($request->only(['search', 'role', 'status', 'per_page']));
        
        $pagination = [
            'current_page' => $members->currentPage(),
            'last_page' => $members->lastPage(),
            'prev_page' => $members->currentPage() > 1 ? $members->currentPage() - 1 : null,
            'next_page' => $members->hasMorePages() ? $members->currentPage() + 1 : null,
            'total' => $members->total(),
            'per_page' => $members->perPage(),
            'from' => $members->firstItem(),
            'to' => $members->lastItem(),
        ];
        
        $this->viewParams['members'] = $members;
        $this->viewParams['search'] = $search;
        $this->viewParams['roleFilter'] = $roleFilter;
        $this->viewParams['statusFilter'] = $statusFilter;
        $this->viewParams['pagination'] = $pagination;
        $this->viewParams['roles'] = MemberRole::cases();

        return view('admin.members.index', $this->viewParams);
    }

    /**
     * メンバー作成フォーム
     */
    public function create()
    {
        $this->viewParams['member'] = null;
        $this->viewParams['roles'] = MemberRole::cases();
        $this->viewParams['roleOptions'] = MemberRole::translationOptions();
        $this->viewParams['roleAdminValue'] = MemberRole::ADMIN->value;
        $this->viewParams['roleSuperAdminValue'] = MemberRole::SUPER_ADMIN->value;
        $this->viewParams['loginNotificationUseProfileSettingValue'] = LoginNotificationMode::UseProfileSetting->value;
        $this->viewParams['twoFactorUseProfileSettingValue'] = TwoFactorMode::UseProfileSetting->value;
        $this->viewParams['roleValue'] = (int) request()->old('role', MemberRole::ADMIN->value);
        $this->viewParams['appearanceOptions'] = AppearanceMode::translationOptions();
        $this->viewParams['localeOptions'] = \App\Enums\Locale::availableOptions();
        $this->viewParams['statusOptions'] = MemberStatus::options();

        $statusOld = request()->old('status');
        $statusValue = null;
        if (!is_null($statusOld)) {
            $statusValue = is_numeric($statusOld) ? (int) $statusOld : null;
        } else {
            $statusValue = MemberStatus::Active->value;
        }
        $this->viewParams['statusValue'] = $statusValue;
        $this->viewParams['requirePassword'] = true;

        $this->loadMemberFormParams();

        return view('admin.members.create', $this->viewParams);
    }

    /**
     * メンバー保存
     */
    public function store(AdminSettingsMemberStoreRequest $request)
    {
        $validated = $request->validated();
        $validated['password'] = Hash::make($validated['password']);

        $isMailServerTested = MailServerValidatorService::isMailServerTested();
        $emailVerified = (string) $request->input('email_verified', $isMailServerTested ? '0' : '1');
        
        if (!$isMailServerTested) {
            $validated['email_verified_at'] = now();
        } elseif ($emailVerified === '1') {
            $validated['email_verified_at'] = now();
        } else {
            $validated['email_verified_at'] = null;
        }
        
        unset($validated['email_verified']);

        $member = Member::create($validated);

        if ($isMailServerTested && $emailVerified === '0') {
            try {
                $member->sendEmailVerificationNotification('create');
                $message = __('admin.members.messages.created_with_verification_email');
            } catch (\Exception $e) {
                \Log::error('Failed to send verification email', [
                    'member_id' => $member->id,
                    'error' => $e->getMessage()
                ]);
                $message = __('admin.members.messages.created_but_email_failed');
            }
        } else {
            $message = __('admin.members.messages.created');
        }

        return redirect()->route('admin.members.edit', ['member' => $member->id])->with('success', $message);
    }

    /**
     * メンバー編集フォーム
     */
    public function edit(Member $member)
    {
        $this->viewParams['member'] = $member;
        $isInitialAdmin = ($member->id === 1);
        $this->viewParams['isInitialAdmin'] = $isInitialAdmin;

        $this->viewParams['roles'] = MemberRole::cases();
        $this->viewParams['roleOptions'] = MemberRole::translationOptions();
        $this->viewParams['appearanceOptions'] = AppearanceMode::translationOptions();
        $this->viewParams['localeOptions'] = \App\Enums\Locale::availableOptions();
        $this->viewParams['roleAdminValue'] = MemberRole::ADMIN->value;
        $this->viewParams['roleSuperAdminValue'] = MemberRole::SUPER_ADMIN->value;
        $this->viewParams['loginNotificationUseProfileSettingValue'] = LoginNotificationMode::UseProfileSetting->value;
        $this->viewParams['twoFactorUseProfileSettingValue'] = TwoFactorMode::UseProfileSetting->value;
        
        if ($isInitialAdmin) {
            $this->viewParams['statusOptions'] = [
                MemberStatus::Active->value => MemberStatus::Active->label()
            ];
        } else {
            $this->viewParams['statusOptions'] = MemberStatus::options();
        }

        $this->viewParams['roleValue'] = (int) request()->old('role', $member->role?->value ?? MemberRole::ADMIN->value);
        $this->viewParams['statusValue'] = (int) request()->old('status', $member->status?->value ?? MemberStatus::Active->value);
        $this->viewParams['requirePassword'] = false;

        $this->loadMemberFormParams();

        $passkeyEnabled = $this->memberSettingRepository->get('enabled_2fa_passkey', '0') === '1';
        $passkeyService = app(\App\Services\PasskeyAuthenticationService::class);
        $this->viewParams['passkeyDevices'] = $passkeyService->getDevices($member);
        $this->viewParams['passkeyEnabled'] = $passkeyEnabled;

        $recoveryCodeService = app(\App\Services\RecoveryCodeService::class);
        $this->viewParams['recoveryCodesCount'] = $recoveryCodeService->getRemainingCount($member);
        $this->viewParams['hasRecoveryCodes'] = $recoveryCodeService->hasRecoveryCodes($member);

        return view('admin.members.edit', $this->viewParams);
    }

    /**
     * メンバー更新
     */
    public function update(AdminSettingsMemberStoreRequest $request, Member $member)
    {
        $validated = $request->validated();

        if ($member->id === 1) {
            $validated['role'] = MemberRole::SUPER_ADMIN->value;
            $validated['status'] = MemberStatus::Active->value;
        }

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $isMailServerTested = MailServerValidatorService::isMailServerTested();
        $emailVerified = (string) $request->input('email_verified', $isMailServerTested ? null : '1');
        $wasVerified = $member->hasVerifiedEmail();
        $emailChanged = $request->input('email') !== $member->email;
        
        if (!$isMailServerTested) {
            $validated['email_verified_at'] = now();
        } elseif ($emailVerified === '1') {
            $validated['email_verified_at'] = now();
        } elseif ($emailVerified === '0') {
            $validated['email_verified_at'] = null;
        } elseif ($emailChanged && $wasVerified) {
            $validated['email_verified_at'] = null;
        }
        
        unset($validated['email_verified']);

        $member->update($validated);
        $id = $member->id;

        $shouldSendEmail = $isMailServerTested && (
            ($emailVerified === '0' && !$wasVerified) ||
            ($emailVerified === '0' && $wasVerified) ||
            ($emailChanged && $wasVerified)
        );
        
        if ($shouldSendEmail && !$member->hasVerifiedEmail()) {
            try {
                $context = ($emailChanged && $wasVerified) ? 'email_change' : 'create';
                $member->sendEmailVerificationNotification($context);
                $message = __('admin.members.messages.updated_with_verification_email');
            } catch (\Exception $e) {
                \Log::error('Failed to send verification email', [
                    'member_id' => $member->id,
                    'error' => $e->getMessage()
                ]);
                $message = __('admin.members.messages.updated_but_email_failed');
            }
        } else {
            $message = __('admin.members.messages.updated');
        }

        return redirect()->route('admin.members.edit', ['member' => $id])->with('success', $message);
    }

    /**
     * メンバー削除
     */
    public function destroy(Member $member)
    {
        if ($member->id === 1) {
            return redirect()->back()->withErrors(['delete' => __('admin.members.messages.initial_member_cannot_delete')]);
        }

        $member->delete();

        return redirect()->route('admin.members.index')->with('success', __('admin.members.messages.deleted'));
    }

    /**
     * メンバーフォーム共通パラメータ読み込み
     */
    private function loadMemberFormParams(): void
    {
        $this->viewParams['passwordMinLength'] = (int) $this->memberSettingRepository->get('password_min_length', 8);
        $this->viewParams['passwordRequireUppercase'] = (bool) $this->memberSettingRepository->get('password_require_uppercase', true);
        $this->viewParams['passwordRequireNumber'] = (bool) $this->memberSettingRepository->get('password_require_number', true);
        $this->viewParams['passwordRequireSymbol'] = (bool) $this->memberSettingRepository->get('password_require_symbol', true);

        $loginNotificationMode = (int) $this->memberSettingRepository->get('login_notification_mode', LoginNotificationMode::UseProfileSetting->value);
        $this->viewParams['loginNotificationMode'] = $loginNotificationMode;
        $loginNotificationEnum = LoginNotificationMode::tryFrom($loginNotificationMode);
        $this->viewParams['loginNotificationModeLabel'] = $loginNotificationEnum ? $loginNotificationEnum->label() : '';

        $force2fa = (int) $this->memberSettingRepository->get('force_2fa', TwoFactorMode::Disabled->value);
        $this->viewParams['force2fa'] = $force2fa;
        $twoFactorEnum = TwoFactorMode::tryFrom($force2fa);
        $this->viewParams['twoFactorModeLabel'] = $twoFactorEnum ? $twoFactorEnum->label() : '';
        
        $enabledTwoFactorMethodsString = $this->memberSettingRepository->get('enabled_two_factor_methods', (string)TwoFactorMethod::EMAIL->value);
        $enabledTwoFactorMethods = ($enabledTwoFactorMethodsString !== null && $enabledTwoFactorMethodsString !== '') ? explode(',', $enabledTwoFactorMethodsString) : [];
        
        $twoFactorMethodOptions = [];
        foreach ($enabledTwoFactorMethods as $methodValue) {
            if (is_numeric($methodValue)) {
                $method = TwoFactorMethod::tryFrom((int)$methodValue);
                if ($method) {
                    $twoFactorMethodOptions[$methodValue] = $method->translationKey();
                }
            }
        }
        
        $this->viewParams['enabledTwoFactorMethods'] = $twoFactorMethodOptions;
        $this->viewParams['defaultTwoFactorMethod'] = (int) $this->memberSettingRepository->get('default_two_factor_method', TwoFactorMethod::EMAIL->value);
        $this->viewParams['twoFactorMode'] = TwoFactorMode::from($this->viewParams['force2fa']);
        $this->viewParams['twoFactorModeOptions'] = TwoFactorMode::translationOptions();
        $this->viewParams['isMailServerTested'] = $this->isMailServerTested();
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
