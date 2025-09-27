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

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminLoggedInController;
use Illuminate\Http\Request;
use App\Http\Requests\Admin\Settings\Members\AdminSettingsMemberStoreRequest;
use App\Http\Requests\Admin\Settings\Members\AdminSettingsMemberSettingsRequest;
use App\Models\Member;
use App\Models\MemberRolePermission;
use App\Models\MemberSetting;
use App\Models\BaseSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Enum;
use App\Enums\TwoFactorMode;
use App\Enums\TwoFactorMethod;
use App\Enums\LoginNotificationMode;
use App\Enums\AppearanceMode;
use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use Illuminate\Support\Facades\Hash;
use App\Helpers\AdminHelper;




class AdminMembersSettingsController extends AdminLoggedInController
{

    //初期設定を行う
    public function __construct()
    {
        // 親クラスのコンストラクタを呼び出す
        parent::__construct();
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        $this->viewParams['members'] = Member::all();

        // 検索条件の取得（デフォルト値設定）
        $search = $request->input('search');
        $roleFilter = $request->input('role', ''); // 権限：デフォルトは全て（空文字）
        
        // ステータスフィルター：初回アクセス時のみデフォルトで有効（1）を設定
        $statusFilter = $request->input('status');
        if ($statusFilter === null && !$request->hasAny(['search', 'role', 'page', 'per_page'])) {
            $statusFilter = '1'; // 初回アクセス時は有効のみ表示
        }
        
        // 表示件数の取得（デフォルト25件）
        $perPage = $request->input('per_page', 25);
        
        // 有効な表示件数かチェック
        $allowedPerPage = [10, 25, 50, 100];
        if (!in_array($perPage, $allowedPerPage)) {
            $perPage = 25;
        }

        // ユーザーを検索
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
            ->paginate($perPage); // 動的ページネーション

        // ページネーションリンクにパラメータを追加
        $members->appends($request->only(['search', 'role', 'status', 'per_page']));
        
        // カスタムページネーション情報を準備
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

        // ビューにデータを渡す
        return view('admin::settings.members.index', $this->viewParams);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // フォームの初期値をセット
        $this->viewParams['member'] = null;

        // 権限の選択肢をセット
        $this->viewParams['roles'] = MemberRole::cases();
        $this->viewParams['roleOptions'] = MemberRole::translationOptions();

        // 他の初期値も同様にセット可能
        $this->viewParams['roleValue'] = (int) request()->old('role', MemberRole::ADMIN->value);

        // 外観の選択肢をセット
        $this->viewParams['appearanceOptions'] = AppearanceMode::translationOptions();

        $this->viewParams['statusOptions'] = MemberStatus::options();

        // old() は request ヘルパで取得可能
        $statusOld = request()->old('status');
        $statusValue = null;

        if (!is_null($statusOld)) {
            $statusValue = is_numeric($statusOld) ? (int) $statusOld : null;
        } else {
            $statusValue = MemberStatus::Active->value; // デフォルト: 有効
        }

        $this->viewParams['statusValue'] = $statusValue;

        //パスワードの必須を有効に
        $this->viewParams['requirePassword'] = true;

        // パスワード設定を取得
        $this->viewParams['passwordMinLength'] = (int) MemberSetting::getValue('password_min_length', 8);
        $this->viewParams['passwordRequireUppercase'] = (bool) MemberSetting::getValue('password_require_uppercase', true);
        $this->viewParams['passwordRequireSymbol'] = (bool) MemberSetting::getValue('password_require_symbol', false);

        // 二段階認証設定
        $this->viewParams['force2fa'] = (int) MemberSetting::getValue('force_2fa', TwoFactorMode::Disabled->value);
        
        // 有効な二段階認証方法を取得
        $enabledTwoFactorMethodsString = MemberSetting::getValue('enabled_two_factor_methods', (string)TwoFactorMethod::EMAIL->value);
        $enabledTwoFactorMethods = ($enabledTwoFactorMethodsString !== null && $enabledTwoFactorMethodsString !== '') ? explode(',', $enabledTwoFactorMethodsString) : [];
        
        // 二段階認証方法の選択肢を作成（翻訳キー版）
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
        $this->viewParams['defaultTwoFactorMethod'] = (int) MemberSetting::getValue('default_two_factor_method', TwoFactorMethod::EMAIL->value);

        // 二段階認証モード設定
        $this->viewParams['twoFactorMode'] = TwoFactorMode::from($this->viewParams['force2fa']);
        $this->viewParams['twoFactorModeOptions'] = TwoFactorMode::translationOptions();

        // メール設定テスト状況を取得
        $this->viewParams['isMailServerTested'] = $this->isMailServerTested();

        return view('admin.settings.members.create', $this->viewParams);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AdminSettingsMemberStoreRequest $request)
    {

        // バリデーション済みデータを取得
        $validated = $request->validated();
        $validated['password'] = Hash::make($validated['password']);

        // 新しい管理者を作成
        $member = Member::create($validated);

        // リダイレクト
        return redirect()->route('admin.settings.members.edit', ['member' => $member->id])->with('success', __('admin.settings.members.messages.created'));
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Member $member)
    {
        $this->viewParams['member'] = $member;

        // 初期管理者アカウント（ID=1）かどうかを判定
        $isInitialAdmin = ($member->id === 1);
        $this->viewParams['isInitialAdmin'] = $isInitialAdmin;

        // 選択肢用の配列
        $this->viewParams['roles'] = MemberRole::cases();
        $this->viewParams['roleOptions'] = MemberRole::translationOptions();
        $this->viewParams['appearanceOptions'] = AppearanceMode::translationOptions();
        
        // 初期管理者の場合はステータス選択肢を制限
        if ($isInitialAdmin) {
            $this->viewParams['statusOptions'] = [
                MemberStatus::Active->value => MemberStatus::Active->label()
            ];
        } else {
            $this->viewParams['statusOptions'] = MemberStatus::options();
        }

        // 初期値（old() の fallback にも対応）
        $this->viewParams['roleValue'] = (int) request()->old('role', $member->role?->value ?? MemberRole::ADMIN->value);
        $this->viewParams['statusValue'] = (int) request()->old('status', $member->status?->value ?? MemberStatus::Active->value);

        //パスワードの必須を無効に
        $this->viewParams['requirePassword'] = false;

        // パスワード設定を取得
        $this->viewParams['passwordMinLength'] = (int) MemberSetting::getValue('password_min_length', 8);
        $this->viewParams['passwordRequireUppercase'] = (bool) MemberSetting::getValue('password_require_uppercase', true);
        $this->viewParams['passwordRequireSymbol'] = (bool) MemberSetting::getValue('password_require_symbol', false);

        // 二段階認証設定
        $this->viewParams['force2fa'] = (int) MemberSetting::getValue('force_2fa', TwoFactorMode::Disabled->value);
        
        // 有効な二段階認証方法を取得
        $enabledTwoFactorMethodsString = MemberSetting::getValue('enabled_two_factor_methods', (string)TwoFactorMethod::EMAIL->value);
        $enabledTwoFactorMethods = ($enabledTwoFactorMethodsString !== null && $enabledTwoFactorMethodsString !== '') ? explode(',', $enabledTwoFactorMethodsString) : [];
        
        // 二段階認証方法の選択肢を作成（翻訳キー版）
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
        $this->viewParams['defaultTwoFactorMethod'] = (int) MemberSetting::getValue('default_two_factor_method', TwoFactorMethod::EMAIL->value);

        // 二段階認証モード設定
        $this->viewParams['twoFactorMode'] = TwoFactorMode::from($this->viewParams['force2fa']);
        $this->viewParams['twoFactorModeOptions'] = TwoFactorMode::translationOptions();

        // メール設定テスト状況を取得
        $this->viewParams['isMailServerTested'] = $this->isMailServerTested();

        return view('admin.settings.members.edit', $this->viewParams);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AdminSettingsMemberStoreRequest $request, Member $member)
    {
        // バリデーション済みデータを取得
        $validated = $request->validated();

        // 初期管理者アカウント（ID=1）の保護
        if ($member->id === 1) {
            // 権限変更を防ぐ
            if (isset($validated['role']) && $validated['role'] !== MemberRole::SUPER_ADMIN->value) {
                return redirect()->back()->withErrors(['role' => __('admin.settings.members.messages.initial_member_role_protected')]);
            }
            
            // ステータス無効化を防ぐ
            if (isset($validated['status']) && $validated['status'] !== MemberStatus::Active->value) {
                return redirect()->back()->withErrors(['status' => __('admin.settings.members.messages.initial_member_status_protected')]);
            }
        }

        // 初期管理者の場合は権限とステータスを強制的に固定
        if ($member->id === 1) {
            $validated['role'] = MemberRole::SUPER_ADMIN->value;
            $validated['status'] = MemberStatus::Active->value;
        }

        // パスワードが送信されている場合のみ更新
        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']); // パスワードが空の場合は更新しない
        }

        $member->update($validated);
        $id = $member->id;

        return redirect()->route('admin.settings.members.edit', ['member' => $id])->with('success', __('admin.settings.members.messages.updated'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Member $member)
    {
        // 初期管理者アカウント（ID=1）の削除を防ぐ
        if ($member->id === 1) {
            return redirect()->back()->withErrors(['delete' => __('admin.settings.members.messages.initial_member_cannot_delete')]);
        }

        $member->delete();

        return redirect()->route('admin.settings.members.index')->with('success', __('admin.settings.members.messages.deleted'));
    }

    /**
     * Force logout the specified member.
     */
    public function forceLogout(Member $member)
    {
        // セッションストアからこのメンバーのセッションを削除
        $sessionStore = app('session.store');
        $sessionTable = config('session.table', 'sessions');
        
        if ($sessionTable && \DB::getSchemaBuilder()->hasTable($sessionTable)) {
            // データベースセッションの場合
            \DB::table($sessionTable)
                ->where('user_id', $member->id)
                ->delete();
        }

        return redirect()->route('admin.settings.members.edit', ['member' => $member->id])
            ->with('success', __('admin.settings.members.messages.force_logout_success'));
    }

    /**
     * Force logout all members except current user.
     */
    public function forceLogoutAll()
    {
        $currentUserId = Auth::guard('member')->id();
        $sessionTable = config('session.table', 'sessions');
        
        if ($sessionTable && \DB::getSchemaBuilder()->hasTable($sessionTable)) {
            // 現在のユーザー以外の全てのセッションを削除
            $deletedCount = \DB::table($sessionTable)
                ->where('user_id', '!=', $currentUserId)
                ->whereNotNull('user_id')
                ->delete();
            
            return redirect()->route('admin.settings.members.settings')
                ->with('success', __('admin.settings.members.force_logout_all_success', ['count' => $deletedCount]));
        }

        return redirect()->route('admin.settings.members.settings')
            ->with('error', __('admin.settings.members.force_logout_all_error'));
    }


    public function roles()
    {
        $permissions = MemberRolePermission::all()->keyBy('menu_key');
        $roles = MemberRole::cases(); // Enumの一覧を取得
        $menuList = config('admin.nav'); // メニューリスト

        // 権限アイテムを収集
        $permissionItems = $this->collectMenuPermissions($menuList);

        $this->viewParams['permissions'] = $permissions;
        $this->viewParams['roles'] = $roles;
        $this->viewParams['menuList'] = $menuList;
        $this->viewParams['permissionItems'] = $permissionItems;

        return view('admin.settings.members.roles', $this->viewParams);
    }

    /**
     * メニューから権限設定用のアイテムを収集
     */
    private function collectMenuPermissions($menuList, $parentKey = '', &$currentSection = '')
    {
        $items = [];
        
        foreach ($menuList as $key => $item) {
            $menuKey = $parentKey ? $parentKey . '.' . $key : $key;

            // 権限設定対象かどうか判定
            $isTarget = isset($item['route']) && !in_array($menuKey, ['dashboard', 'front', 'media', 'settings']);

            // 見出しだけ表示すべき親メニューかどうか
            $isHeadingOnly = !$isTarget && isset($item['children']) && !in_array($menuKey, ['dashboard']);

            if ($isHeadingOnly) {
                $sectionTitle = __($item['text']);
                if ($currentSection !== $sectionTitle) {
                    $currentSection = $sectionTitle;
                    $items[] = [
                        'type' => 'heading',
                        'title' => $sectionTitle
                    ];
                }
            }

            if ($isTarget) {
                $items[] = [
                    'type' => 'permission',
                    'title' => __($item['text']),
                    'menuKey' => $menuKey
                ];
            }

            // 子メニューがあれば再帰処理
            if (isset($item['children'])) {
                $childItems = $this->collectMenuPermissions($item['children'], $menuKey, $currentSection);
                $items = array_merge($items, $childItems);
            }
        }
        
        return $items;
    }

    public function updateRoles(Request $request)
    {

        $this->authorizeEdit('settings.members.roles');

        $data = $request->input('permissions', []);

        foreach ($data as $menuKey => $values) {
            MemberRolePermission::updateOrCreate(
                ['menu_key' => $menuKey],
                [
                    'access_roles' => isset($values['access_roles']) ? implode(',', $values['access_roles']) : '',
                    'view_roles' => isset($values['view_roles']) ? implode(',', $values['view_roles']) : '',
                ]
            );
        }

        return redirect()->back()->with('success', __('admin.settings.members.messages.permissions_saved'));
    }

    protected function authorizeEdit(string $menuKey)
    {
        if (!\App\Helpers\AdminHelper::canEditMenu($menuKey)) {
            abort(403, __('admin.settings.members.messages.insufficient_permissions'));
        }
    }


    public function settings()
    {

        // 既存の設定を取得
        $passwordMinLength = (int) MemberSetting::getValue('password_min_length', 8);
        $passwordRequireUppercase = (bool) MemberSetting::getValue('password_require_uppercase', true);
        $passwordRequireSymbol = (bool) MemberSetting::getValue('password_require_symbol', false);
        
        // パスワード条件設定の選択肢を準備
        $minLengthOptions = collect(__('admin.settings.members.settings.password_min_length_options'))
            ->mapWithKeys(fn($label, $key) => [$key => $label])
            ->toArray();
            
        $uppercaseOptions = collect(__('admin.settings.members.settings.password_require_uppercase_options'))
            ->mapWithKeys(fn($label, $key) => [$key => $label])
            ->toArray();
            
        $symbolOptions = collect(__('admin.settings.members.settings.password_require_symbol_options'))
            ->mapWithKeys(fn($label, $key) => [$key => $label])
            ->toArray();

        // その他の選択肢を準備
        $loginAttemptLimitOptions = [
            '1' => __('common.enabled'),
            '0' => __('common.disabled'),
        ];
        
        $lockoutNotificationOptions = [
            '1' => __('common.enabled'),
            '0' => __('common.disabled'),
        ];
        
        $passwordResetOptions = [
            '1' => __('common.enabled'),
            '0' => __('common.disabled'),
        ];
        
        $pwnedPasswordOptions = [
            '1' => __('common.enabled'),
            '0' => __('common.disabled'),
        ];
        
        $adminSessionLifetimeOptions = [
            '1' => __('admin.settings.members.settings.admin_session_lifetime_enabled_options.enabled'),
            '0' => __('admin.settings.members.settings.admin_session_lifetime_enabled_options.disabled'),
        ];

        // ログイン通知設定
        $loginNotification = (int) MemberSetting::getValue('login_notification_mode', LoginNotificationMode::UseProfileSetting->value);
        $loginNotificationGlobalOptions = collect(config('admin.global_login_notification_mail_mode'))
            ->mapWithKeys(fn ($value) => [$value => __('common.login_notification_mode.options.' . $value)])
            ->toArray();

        // 二段階認証設定
        $force2fa = (int) MemberSetting::getValue('force_2fa', TwoFactorMode::Disabled->value);
        
        // old() の値がある場合はそれを優先（バリデーションエラー後の再表示時）
        if (old('force_2fa') !== null) {
            $force2fa = (int) old('force_2fa');
        }
        
        $twoFactorGlobalOptions = collect(config('admin.global_two_factor_mode'))
            ->mapWithKeys(fn ($value) => [$value => str_replace(':account_type', __('common.account_types.member'), __('common.two_factor_mode.options.' . $value))])
            ->toArray();
        
        // 有効な二段階認証方法を取得（複数選択可能）
        // バリデーションエラー時は old() の値を優先して使用
        $enabledTwoFactorMethodsString = MemberSetting::getValue('enabled_two_factor_methods', (string)TwoFactorMethod::EMAIL->value);
        $enabledTwoFactorMethods = ($enabledTwoFactorMethodsString !== null && $enabledTwoFactorMethodsString !== '') ? explode(',', $enabledTwoFactorMethodsString) : [];
        
        // old() の値がある場合はそれを優先（バリデーションエラー後の再表示時）
        if (old('enabled_two_factor_methods')) {
            $enabledTwoFactorMethods = old('enabled_two_factor_methods');
        }
        
        $defaultTwoFactorMethod = (int) MemberSetting::getValue('default_two_factor_method', TwoFactorMethod::EMAIL->value);
        
        // old() の値がある場合はそれを優先（バリデーションエラー後の再表示時）
        if (old('default_two_factor_method')) {
            $defaultTwoFactorMethod = (int) old('default_two_factor_method');
        }
        $twoFactorMethodOptions = TwoFactorMethod::forGlobalSettings();
        
        // コンポーネント用の配列を準備
        $twoFactorMethodCheckboxOptions = collect($twoFactorMethodOptions)
            ->mapWithKeys(fn($method) => [$method->value => $method->label()])
            ->toArray();
            
        $twoFactorMethodRadioOptions = collect($twoFactorMethodOptions)
            ->mapWithKeys(fn($method) => [$method->value => $method->label()])
            ->toArray();
            
        $selectedDefaultMethod = $defaultTwoFactorMethod ?? TwoFactorMethod::EMAIL->value;

        // パスワードリセット機能設定
        $passwordResetEnabled = (bool) MemberSetting::getValue('password_reset_enabled', true);

        // パスワード辞書攻撃対策設定
        $pwnedPasswordCheckEnabled = (bool) MemberSetting::getValue('pwned_password_check_enabled', false);

        // ログイン試行制限設定
        $loginAttemptLimitEnabled = (bool) MemberSetting::getValue('login_attempt_limit_enabled', false);
        $loginAttemptMaxAttempts = (int) MemberSetting::getValue('login_attempt_max_attempts', 5);
        $loginAttemptTimeWindow = (int) MemberSetting::getValue('login_attempt_time_window', 15);
        $loginAttemptLockoutDuration = (int) MemberSetting::getValue('login_attempt_lockout_duration', 30);
        $lockoutNotificationEnabled = (bool) MemberSetting::getValue('lockout_notification_enabled', true);

        // 管理メンバー用セッション設定
        $membersSessionLifetimeEnabled = (bool) MemberSetting::getValue('members_session_lifetime_enabled', false);
        $membersSessionLifetime = (int) MemberSetting::getValue('members_session_lifetime', 120);

        // メールサーバー接続テスト状況
        $isMailServerTested = $this->isMailServerTested();
        $mailConnectionTestDate = BaseSetting::getValue('mail_connection_test_date');

        // ビューに渡すデータをセット
        $this->viewParams['passwordMinLength'] = $passwordMinLength;
        $this->viewParams['passwordRequireUppercase'] = $passwordRequireUppercase;
        $this->viewParams['passwordRequireSymbol'] = $passwordRequireSymbol;
        $this->viewParams['minLengthOptions'] = $minLengthOptions;
        $this->viewParams['uppercaseOptions'] = $uppercaseOptions;
        $this->viewParams['symbolOptions'] = $symbolOptions;
        $this->viewParams['loginAttemptLimitOptions'] = $loginAttemptLimitOptions;
        $this->viewParams['lockoutNotificationOptions'] = $lockoutNotificationOptions;
        $this->viewParams['passwordResetOptions'] = $passwordResetOptions;
        $this->viewParams['pwnedPasswordOptions'] = $pwnedPasswordOptions;
        $this->viewParams['adminSessionLifetimeOptions'] = $adminSessionLifetimeOptions;
        $this->viewParams['loginNotification'] = $loginNotification;
        $this->viewParams['loginNotificationGlobalOptions'] = $loginNotificationGlobalOptions;
        $this->viewParams['force2fa'] = $force2fa;
        $this->viewParams['twoFactorGlobalOptions'] = $twoFactorGlobalOptions;
        $this->viewParams['enabledTwoFactorMethods'] = $enabledTwoFactorMethods;
        $this->viewParams['defaultTwoFactorMethod'] = $defaultTwoFactorMethod;
        $this->viewParams['twoFactorMethodOptions'] = $twoFactorMethodOptions;
        $this->viewParams['twoFactorMethodCheckboxOptions'] = $twoFactorMethodCheckboxOptions;
        $this->viewParams['twoFactorMethodRadioOptions'] = $twoFactorMethodRadioOptions;
        $this->viewParams['selectedDefaultMethod'] = $selectedDefaultMethod;
        $this->viewParams['passwordResetEnabled'] = $passwordResetEnabled;
        $this->viewParams['pwnedPasswordCheckEnabled'] = $pwnedPasswordCheckEnabled;
        $this->viewParams['loginAttemptLimitEnabled'] = $loginAttemptLimitEnabled;
        $this->viewParams['loginAttemptMaxAttempts'] = $loginAttemptMaxAttempts;
        $this->viewParams['loginAttemptTimeWindow'] = $loginAttemptTimeWindow;
        $this->viewParams['loginAttemptLockoutDuration'] = $loginAttemptLockoutDuration;
        $this->viewParams['lockoutNotificationEnabled'] = $lockoutNotificationEnabled;
        $this->viewParams['membersSessionLifetimeEnabled'] = $membersSessionLifetimeEnabled;
        $this->viewParams['membersSessionLifetime'] = $membersSessionLifetime;
        $this->viewParams['isMailServerTested'] = $isMailServerTested;
        $this->viewParams['mailConnectionTestDate'] = $mailConnectionTestDate;

        return view('admin.settings.members.settings', $this->viewParams);
    }

    public function updateSettings(AdminSettingsMemberSettingsRequest $request)
    {
        // validated() を使えば確実にバリデーション済みの値だけ取得できる
        $validated = $request->validated();


        MemberSetting::setValue('password_min_length', (int) $validated['password_min_length']);
        MemberSetting::setValue('password_require_uppercase', (int) $validated['password_require_uppercase']);
        MemberSetting::setValue('password_require_symbol', (int) $validated['password_require_symbol']);
        MemberSetting::setValue('login_notification_mode', (int) $validated['login_notification_mode']);
        MemberSetting::setValue('force_2fa', (int) $validated['force_2fa']);
        MemberSetting::setValue('password_reset_enabled', (bool) $validated['password_reset_enabled']);
        MemberSetting::setValue('pwned_password_check_enabled', (bool) $validated['pwned_password_check_enabled']);
        
        // ログイン試行制限設定
        MemberSetting::setValue('login_attempt_limit_enabled', $validated['login_attempt_limit_enabled'] ? '1' : '0');
        MemberSetting::setValue('login_attempt_max_attempts', (string) $validated['login_attempt_max_attempts']);
        MemberSetting::setValue('login_attempt_time_window', (string) $validated['login_attempt_time_window']);
        MemberSetting::setValue('login_attempt_lockout_duration', (string) $validated['login_attempt_lockout_duration']);
        MemberSetting::setValue('lockout_notification_enabled', $validated['lockout_notification_enabled'] ? '1' : '0');

        // 管理メンバー用セッション設定
        MemberSetting::setValue('members_session_lifetime_enabled', $validated['members_session_lifetime_enabled'] ? '1' : '0');
        MemberSetting::setValue('members_session_lifetime', (string) $validated['members_session_lifetime']);

        // 二段階認証方法設定の保存
        $autoSelectedDefaultMethod = null;
        if (array_key_exists('enabled_two_factor_methods', $validated)) {
            $enabledMethods = $validated['enabled_two_factor_methods'] ?? [];
            $enabledMethodsString = !empty($enabledMethods) ? implode(',', $enabledMethods) : '';
            MemberSetting::setValue('enabled_two_factor_methods', $enabledMethodsString);

            // デフォルト認証方法の自動選択ロジック
            $defaultMethod = $validated['default_two_factor_method'] ?? null;
            
            if (!empty($enabledMethods)) {
                // 有効な方法が1つだけの場合、自動的にデフォルトに設定
                if (count($enabledMethods) === 1) {
                    $autoSelectedDefaultMethod = $enabledMethods[0];
                    MemberSetting::setValue('default_two_factor_method', $autoSelectedDefaultMethod);
                }
                // 有効な方法が複数ある場合
                else {
                    // デフォルトが指定されていない、または指定されたデフォルトが有効な方法に含まれていない場合
                    if (!$defaultMethod || !in_array($defaultMethod, $enabledMethods)) {
                        $autoSelectedDefaultMethod = $enabledMethods[0]; // 最初の有効な方法を選択
                        MemberSetting::setValue('default_two_factor_method', $autoSelectedDefaultMethod);
                    } else {
                        MemberSetting::setValue('default_two_factor_method', $defaultMethod);
                    }
                }
            }
        } elseif (array_key_exists('default_two_factor_method', $validated)) {
            MemberSetting::setValue('default_two_factor_method', $validated['default_two_factor_method']);
        }

        // 成功メッセージの準備
        $successMessage = __('admin.settings.members.settings.updated');
        if ($autoSelectedDefaultMethod) {
            $methodLabel = \App\Enums\TwoFactorMethod::from((int)$autoSelectedDefaultMethod)->label();
            $successMessage .= ' ' . __('admin.settings.members.settings.auto_selected_default_method', ['method' => $methodLabel]);
        }

        return redirect()->route('admin.settings.members.settings')
            ->with('success', $successMessage);
    }

    private function isMailServerTested(): bool
    {
        $connectionTested = (bool) BaseSetting::getValue('mail_connection_tested', false);
        $sendTested = (bool) BaseSetting::getValue('mail_send_tested', false);
        $receiveTested = (bool) BaseSetting::getValue('mail_receive_tested', false);
        
        return $connectionTested && $sendTested && $receiveTested;
    }
}
