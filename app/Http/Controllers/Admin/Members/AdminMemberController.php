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
use App\Enums\TwoFaMethod;
use App\Enums\AuthenticationMode;
use App\Enums\AppearanceMode;
use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Services\MailServerValidatorService;
use App\Services\PasswordService;
use App\Services\TwoFa\TwoFaPasskeyService;
use App\Services\TwoFa\TwoFaRecoveryCodeService;
use App\Contracts\Repositories\MemberSettingRepositoryInterface;
use Illuminate\Support\Facades\DB;
use App\Traits\ManagesAccountTrait;
use App\Traits\ManagesTwoFaTrait;

class AdminMemberController extends AdminLoggedInController
{
    use ManagesAccountTrait, ManagesTwoFaTrait;

    protected MemberSettingRepositoryInterface $memberSettingRepository;

    public function __construct(MemberSettingRepositoryInterface $memberSettingRepository)
    {
        parent::__construct();
        $this->memberSettingRepository = $memberSettingRepository;
    }

    /**
     * モデルのルートパラメータ名を取得
     */
    protected function getModelRouteParameterName(): string
    {
        return 'member';
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
        $this->viewParams['loginNotificationUseProfileSettingValue'] = AuthenticationMode::UseProfileSetting->value;
        $this->viewParams['twoFactorUseProfileSettingValue'] = AuthenticationMode::UseProfileSetting->value;
        $this->viewParams['roleValue'] = (int) request()->old('role', MemberRole::ADMIN->value);
        $this->viewParams['appearanceOptions'] = AppearanceMode::translationOptions();
        $this->viewParams['localeOptions'] = \App\Enums\Locale::availableOptions();
        
        // ステータスオプションをコンポーネント用の形式に変換
        $this->viewParams['statusOptions'] = [
            ['value' => '1', 'label' => 'components.status.active', 'icon' => 'fas fa-check-circle', 'color' => 'green'],
            ['value' => '0', 'label' => 'components.status.inactive', 'icon' => 'fas fa-times-circle', 'color' => 'gray'],
        ];

        $statusOld = request()->old('status');
        $statusValue = null;
        if (!is_null($statusOld)) {
            $statusValue = is_numeric($statusOld) ? (int) $statusOld : null;
        } else {
            $statusValue = MemberStatus::Active->value;
        }
        $this->viewParams['statusValue'] = $statusValue;
        $this->viewParams['requirePassword'] = true;
        $this->viewParams['verificationTranslationPrefix'] = 'admin/members/form';

        $this->loadMemberFormParams();

        return view('admin.members.create', $this->viewParams);
    }

    /**
     * メンバー保存
     */
    public function store(AdminSettingsMemberStoreRequest $request)
    {
        $validated = $request->validated();
        $validated['password'] = PasswordService::hash($validated['password']);

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
                $message = __('admin/members/create.messages.created_with_verification_email');
            } catch (\Exception $e) {
                \Log::error('Failed to send verification email', [
                    'member_id' => $member->id,
                    'error' => $e->getMessage()
                ]);
                $message = __('admin/members/create.messages.created_but_email_failed');
            }
        } else {
            $message = __('admin/members/create.messages.created');
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
        $this->viewParams['loginNotificationUseProfileSettingValue'] = AuthenticationMode::UseProfileSetting->value;
        $this->viewParams['twoFactorUseProfileSettingValue'] = AuthenticationMode::UseProfileSetting->value;
        
        // ステータスオプションをコンポーネント用の形式に変換
        $this->viewParams['statusOptions'] = [
            ['value' => '1', 'label' => 'components.status.active', 'icon' => 'fas fa-check-circle', 'color' => 'green'],
            ['value' => '0', 'label' => 'components.status.inactive', 'icon' => 'fas fa-times-circle', 'color' => 'gray'],
        ];

        $this->viewParams['roleValue'] = (int) request()->old('role', $member->role?->value ?? MemberRole::ADMIN->value);
        $this->viewParams['statusValue'] = (int) request()->old('status', $member->status?->value ?? MemberStatus::Active->value);
        $this->viewParams['requirePassword'] = false;
        $this->viewParams['verificationTranslationPrefix'] = 'admin/members/form';

        $this->loadMemberFormParams();

        // グローバル設定で有効な二段階認証方法を取得
        $twoFaPasskeyMode = (int) $this->memberSettingRepository->get('two_fa_passkey_mode', '2');
        // two_fa_passkey_modeが0（無効）以外ならPasskeyは有効とみなす
        $twoFaPasskeyEnabled = $twoFaPasskeyMode > 0;
        
        $twoFaPasskeyService = new TwoFaPasskeyService();
        $this->viewParams['twoFaPasskeyDevices'] = $twoFaPasskeyService->getDevices($member);
        $this->viewParams['twoFaPasskeyEnabled'] = $twoFaPasskeyEnabled;

        $twoFaRecoveryCodeService = new TwoFaRecoveryCodeService();
        $this->viewParams['twoFaRecoveryCodesCount'] = $twoFaRecoveryCodeService->getRemainingCount($member);
        $this->viewParams['twoFaHasRecoveryCodes'] = $twoFaRecoveryCodeService->hasRecoveryCodes($member);

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

        PasswordService::hashPasswordIfPresent($validated);

        $wasVerified = $member->hasVerifiedEmail();
        $this->processEmailVerificationStatus($validated, $request, $member);

        $member->update($validated);
        $id = $member->id;

        $messageKey = $this->sendEmailVerificationIfNeeded(
            $member,
            $request,
            $wasVerified,
            'admin/members/edit.messages.updated_with_verification_email',
            'admin/members/edit.messages.updated_but_email_failed',
            'admin/members/edit.messages.updated'
        );

        $message = __($messageKey);

        return redirect()->route('admin.members.edit', ['member' => $id])->with('success', $message);
    }

    /**
     * メンバー削除
     */
    public function destroy(Member $member)
    {
        if ($member->id === 1) {
            return redirect()->back()->withErrors(['delete' => __('admin/members/index.messages.initial_member_cannot_delete')]);
        }

        $member->delete();

        return redirect()->route('admin.members.index')->with('success', __('admin/members/edit.messages.deleted'));
    }

    /**
     * メンバーフォーム共通パラメータ読み込み
     */
    private function loadMemberFormParams(): void
    {
        $this->viewParams['passwordMinLength'] = (int) $this->memberSettingRepository->get('password_min_length', 8);
        $this->viewParams['passwordRequireUppercase'] = (bool) $this->memberSettingRepository->get('password_require_uppercase', true);
        $this->viewParams['passwordRequireLowercase'] = (bool) $this->memberSettingRepository->get('password_require_lowercase', true);
        $this->viewParams['passwordRequireNumber'] = (bool) $this->memberSettingRepository->get('password_require_number', true);
        $this->viewParams['passwordRequireSymbol'] = (bool) $this->memberSettingRepository->get('password_require_symbol', true);

        $loginNotificationMode = (int) $this->memberSettingRepository->get('login_notification_mode', AuthenticationMode::UseProfileSetting->value);
        $this->viewParams['loginNotificationMode'] = $loginNotificationMode;
        $loginNotificationEnum = AuthenticationMode::tryFrom($loginNotificationMode);
        $this->viewParams['loginNotificationModeLabel'] = $loginNotificationEnum ? $loginNotificationEnum->notificationLabel() : '';
        
        // radio-card-group用の通知設定オプション配列を生成
        $loginNotificationOptions = [];
        foreach (AuthenticationMode::forProfile() as $case) {
            $loginNotificationOptions[] = [
                'value' => (string)$case->value,
                'label' => $case->notificationLabel(),
            ];
        }
        $this->viewParams['loginNotificationModeOptions'] = $loginNotificationOptions;

        $twoFaForceMode = (int) $this->memberSettingRepository->get('two_fa_mode', AuthenticationMode::Disabled->value);
        // Enumオブジェクトの場合は整数値に変換
        if ($twoFaForceMode instanceof AuthenticationMode) {
            $twoFaForceMode = $twoFaForceMode->value;
        }
        $this->viewParams['forceTwoFa'] = $twoFaForceMode;
        
        // パスキーモード設定を追加
        $twoFaPasskeyMode = (int) $this->memberSettingRepository->get('two_fa_passkey_mode', '2');
        $this->viewParams['twoFaPasskeyMode'] = $twoFaPasskeyMode;
        $twoFactorEnum = AuthenticationMode::tryFrom($twoFaForceMode);
        $this->viewParams['twoFactorModeLabel'] = $twoFactorEnum ? $twoFactorEnum->twoFactorLabel() : '';
        
        // radio-card-group用の二段階認証オプション配列を生成
        $twoFaModeOptions = [];
        foreach (AuthenticationMode::forProfile() as $case) {
            $twoFaModeOptions[] = [
                'value' => (string)$case->value,
                'label' => $case->twoFactorLabel(),
            ];
        }
        $this->viewParams['twoFactorModeOptions'] = $twoFaModeOptions;
        
        // メール認証は常に有効
        $twoFaMethodOptions = [
            TwoFaMethod::EMAIL->value => TwoFaMethod::EMAIL->translationKey(),
        ];
        
        // 全体設定でパスキー認証が有効な場合は追加
        $twoFaPasskeyEnabled = $this->memberSettingRepository->get('two_fa_passkey_enabled', '0') === '1';
        if ($twoFaPasskeyEnabled) {
            $twoFaMethodOptions[TwoFaMethod::PASSKEY->value] = TwoFaMethod::PASSKEY->translationKey();
        }
        
        // デフォルトの認証方法を取得
        $twoFaDefaultMethod = (int) $this->memberSettingRepository->get('default_two_fa_method', TwoFaMethod::EMAIL->value);
        
        $this->viewParams['twoFaEnabledMethods'] = $twoFaMethodOptions;
        $this->viewParams['twoFaDefaultMethod'] = $twoFaDefaultMethod;
        $this->viewParams['twoFaPasskeyEnabled'] = $twoFaPasskeyEnabled;
        $this->viewParams['twoFaMode'] = AuthenticationMode::from($this->viewParams['forceTwoFa']);
        $this->viewParams['twoFaUseProfileSettingValue'] = AuthenticationMode::UseProfileSetting->value;
        $this->viewParams['loginNotificationUseProfileSettingValue'] = AuthenticationMode::UseProfileSetting->value;
        $this->viewParams['isMailServerTested'] = MailServerValidatorService::isMailServerTested();
    }

    public function forceLogout(Member $member)
    {
        return $this->forceLogoutModel(
            $member,
            'admin/members/edit.messages.force_logout_success',
            'admin.members.edit'
        );
    }

    /**
     * Two-FAロックアウト解除
     */
    public function unlockTwoFa(Member $member)
    {
        return $this->unlockTwoFaForModel(
            $member,
            \App\Models\MemberTwoFaAttempt::class,
            \App\Models\MemberLoginAttempt::class,
            'member_id',
            'admin/members/edit.messages.unlock_lockout_success',
            'admin.members.edit'
        );
    }

    /**
     * 認証メール送信
     */
    public function sendVerificationEmail(Member $member)
    {
        return $this->sendVerificationEmailToModel(
            $member,
            'admin/members/edit.messages.verification_email_sent',
            'admin/members/edit.messages.verification_email_failed',
            'admin.members.edit'
        );
    }

    /**
     * Passkey削除
     */
    public function revokePasskey(Request $request, Member $member, string $credentialId)
    {
        return $this->revokePasskeyForModel(
            $request,
            $member,
            $credentialId,
            'admin/members/form.passkey_all_deleted',
            'admin/profile.passkey_not_found',
            'admin/profile.passkey_deleted',
            'admin/profile.passkey_delete_error'
        );
    }

    /**
     * 回復コード削除
     */
    public function revokeRecoveryCodes(Request $request, Member $member)
    {
        return $this->revokeRecoveryCodesForModel(
            $request,
            $member,
            'admin/members/form.recovery_codes_deleted',
            'admin/members/form.recovery_codes_delete_error'
        );
    }
}
