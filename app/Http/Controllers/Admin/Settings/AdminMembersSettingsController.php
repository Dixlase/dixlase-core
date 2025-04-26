<?php

/**
 * This file is part of MySoftware.
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
use App\Models\MemberSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Enum;
use App\Enums\TwoFactorMode;
use App\Enums\LoginNotificationMode;
use App\Enums\AppearanceMode;
use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use Illuminate\Support\Facades\Hash;



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

        // 検索条件の取得
        $search = $request->input('search');

        // ユーザーを検索
        $members = Member::query()
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            })
            ->paginate(10); // ページネーション

        $this->viewParams['members'] = $members;
        $this->viewParams['search'] = $search;

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
        $this->viewParams['roleOptions'] = collect(MemberRole::cases())
            ->mapWithKeys(fn($role) => [$role->value => $role->label()])
            ->toArray();

        // 他の初期値も同様にセット可能
        $this->viewParams['roleValue'] = (int) request()->old('role', MemberRole::ADMIN->value);

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

        return view('admin::settings.members.create', $this->viewParams);
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
        return redirect()->route('admin.settings.members.edit', ['member' => $member->id])->with('success', '新しいユーザーが作成されました！');
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Member $member)
    {
        $this->viewParams['member'] = $member;

        // 選択肢用の配列
        $this->viewParams['roleOptions'] = MemberRole::options();
        $this->viewParams['statusOptions'] = MemberStatus::options();

        // 初期値（old() の fallback にも対応）
        $this->viewParams['roleValue'] = (int) request()->old('role', $member->role?->value ?? MemberRole::ADMIN->value);
        $this->viewParams['statusValue'] = (int) request()->old('status', $member->status?->value ?? MemberStatus::Active->value);

        //パスワードの必須を無効に
        $this->viewParams['requirePassword'] = false;

        return view('admin::settings.members.edit', $this->viewParams);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AdminSettingsMemberStoreRequest $request, Member $member)
    {
        // バリデーション済みデータを取得
        $validated = $request->validated();

        // パスワードが送信されている場合のみ更新
        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']); // パスワードが空の場合は更新しない
        }

        $member->update($validated);
        $id = $member->id;

        return redirect()->route('admin.settings.members.edit', ['member' => $id])->with('success', '管理車情報を更新しました！');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Member $member)
    {
        $member->delete();

        return redirect()->route('admin.settings.members.index')->with('success', '管理者アカウントを削除しました！');
    }

    /**
     * Show the form for editing the profile.
     */
    public function profile()
    {
        // プロフィール画面だけアニメーションを有効にする
        $transition = 'transition-colors duration-300';

        // layout クラスに transition を追加
        $appearanceClass = config('admin.appearance_class');

        foreach ($appearanceClass['layout'] as $key => $value) {
            $appearanceClass['layout'][$key] = $value . ' ' . $transition;
        }
        foreach ($appearanceClass['sidebar'] as $key => $value) {
            $appearanceClass['sidebar'][$key] = $value . ' ' . $transition;
        }
        foreach ($appearanceClass['table'] as $key => $value) {
            $appearanceClass['table'][$key] = $value . ' ' . $transition;
        }
        $appearanceClass['link'] .= ' ' . $transition;
        foreach ($appearanceClass['form'] as $key => $value) {
            $appearanceClass['form'][$key] = $value . ' ' . $transition;
        }

        config(['admin.appearance_class' => $appearanceClass]);

        // ✅ 外観モードをもとにクラスを生成
        $appearance = (int) (old('appearance') ?? Auth::guard('member')->user()->appearance?->value ?? 0);

        $htmlClass = '';
        if ($appearance === 2 || ($appearance === 0 && request()->cookie('prefers_dark') === '1')) {
            $htmlClass .= 'dark ';
        }
        $htmlClass .= ''; // 必要があれば他のclassもここで
        // アニメーションを有効にするため disable-transition はつけない
        $this->viewParams['htmlClass'] = trim($htmlClass);

        // ✅ 外観モードのオプションなど他の処理（そのままでOK）
        $this->viewParams['appearanceOptions'] = collect(AppearanceMode::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->label()])
            ->toArray();


        //外観モードの取得
        $appearanceOptions = collect(AppearanceMode::cases())->mapWithKeys(function ($case) {
            return [$case->value => $case->label()];
        })->toArray();

        $this->viewParams['appearanceOptions'] = $appearanceOptions;

        // プロフィール画面だけアニメーションを有効にする
        $this->viewParams['transitionEnabled'] = true;

        // パスワード条件の取得
        $this->viewParams['passwordMinLength'] = (int) MemberSetting::getValue('password_min_length', 8);
        $this->viewParams['passwordRequireUppercase'] = (bool) MemberSetting::getValue('password_require_uppercase', true);
        $this->viewParams['passwordRequireSymbol'] = (bool) MemberSetting::getValue('password_require_symbol', false);

        // ログイン通知設定の追加
        $loginNoticeGlobal = (int) MemberSetting::getValue(
            'login_notification_mode',
            LoginNotificationMode::UseProfileSetting->value
        );
        $loginNotificationMode = Auth::guard('member')->user()->login_notification_mode;

        $loginNotificationOptions = collect(LoginNotificationMode::forProfile())
            ->mapWithKeys(fn($case) => [$case->value => $case->label()])
            ->toArray();

        $this->viewParams['loginNoticeGlobal'] = $loginNoticeGlobal;
        $this->viewParams['loginNotificationMode'] = $loginNotificationMode;
        $this->viewParams['loginNotificationOptions'] = $loginNotificationOptions;


        // 二段階認証設定の追加
        $force2fa = MemberSetting::getValue(
            'force_2fa',
            TwoFactorMode::UseProfileSetting->value
        );
        $twoFactorMode = Auth::guard('member')->user()->two_factor_mode;

        $twoFactorOptions = collect(TwoFactorMode::forProfile())
            ->mapWithKeys(fn($case) => [$case->value => $case->label()])
            ->toArray();

        $this->viewParams['force2fa'] = $force2fa;
        $this->viewParams['twoFactorMode'] = $twoFactorMode;
        $this->viewParams['twoFactorOptions'] = $twoFactorOptions;


        return view('admin.settings.members.profile', $this->viewParams);
    }

    public function updateProfile(Request $request)
    {
        $member = Auth::guard('member')->user();

        // 確認欄を表示するか（プロフィール画面では常に true）
        $showConfirmation = true;


        // 🔽 パスワード条件を全体設定から取得
        $minLength = (int) MemberSetting::getValue('password_min_length', 8);
        $requireUppercase = (bool) MemberSetting::getValue('password_require_uppercase', true);
        $requireSymbol = (bool) MemberSetting::getValue('password_require_symbol', false);


        // 🔽 パスワードのルールを動的に構築
        $passwordRules = ['nullable', "min:$minLength"];

        // パスワードの確認が必要な場合
        if ($showConfirmation) {
            $passwordRules[] = 'confirmed';
        }

        // 常に小文字と数字を必須にする
        $passwordRules[] = 'regex:/[a-z]/'; // 小文字
        $passwordRules[] = 'regex:/[0-9]/'; // 数字

        // 条件に応じて大文字と記号を追加
        if ($requireUppercase) {
            $passwordRules[] = 'regex:/[A-Z]/'; // 英大文字
        }
        if ($requireSymbol) {
            $passwordRules[] = 'regex:/[!@#$%^&*(),.?":{}|<>]/'; // 記号
        }


        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'email' => 'required|email|unique:members,email,' . $member->id,
            'password' => $passwordRules,
            'appearance' => ['nullable', new Enum(AppearanceMode::class)],
            'two_factor_mode' => ['nullable', new Enum(TwoFactorMode::class)],
            'login_notification_mode' => ['nullable', new Enum(LoginNotificationMode::class)],
        ]);

        $member->fill([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? '',
            'email' => $validated['email'],
            'appearance' => (int) $validated['appearance'] ?? null,
        ]);

        if (!empty($validated['password'])) {
            $member->password = Hash::make($validated['password']);
        }

        // login_notification_mode は全体設定が 0 のときだけ上書き
        $globalLogin = (int) MemberSetting::getValue('login_notification_mode', LoginNotificationMode::UseProfileSetting->value);
        if ($globalLogin === LoginNotificationMode::UseProfileSetting->value && array_key_exists('login_notification_mode', $validated)) {
            $member->login_notification_mode = (int) $validated['login_notification_mode'];
        }


        // two_factor_mode は全体設定が 0 のときだけ上書き
        $force2fa = (int) MemberSetting::getValue('force_2fa', TwoFactorMode::UseProfileSetting->value);
        if ($force2fa === TwoFactorMode::UseProfileSetting->value && array_key_exists('two_factor_mode', $validated)) {
            $member->two_factor_mode = (int) $validated['two_factor_mode'];
        }

        $member->save();

        return redirect()->route('admin.settings.members.profile')
            ->with('success', __('admin.settings.members.profile.updated'));
    }


    public function settings()
    {

        // 🔽 パスワード条件の取得
        $passwordMinLength = (int) MemberSetting::getValue('password_min_length', 8);
        $passwordRequireUppercase = (bool) MemberSetting::getValue('password_require_uppercase', true);
        $passwordRequireSymbol = (bool) MemberSetting::getValue('password_require_symbol', false);

        // ログイン通知設定の追加
        $loginNotification = (int) MemberSetting::getValue('login_notification_mode', LoginNotificationMode::UseProfileSetting->value);
        $loginNotificationOptions = collect(config('admin.settings.members.login_notification_mode.options_global'));

        // 二段階認証設定の追加
        $force2fa = (int) MemberSetting::getValue('force_2fa', TwoFactorMode::Disabled->value);
        $twoFactorOptions = collect(TwoFactorMode::cases())->mapWithKeys(function ($case) {
            return [$case->value => $case->label()];
        })->toArray();


        // ビューに渡すデータをセット
        $this->viewParams['passwordMinLength'] = $passwordMinLength;
        $this->viewParams['passwordRequireUppercase'] = $passwordRequireUppercase;
        $this->viewParams['passwordRequireSymbol'] = $passwordRequireSymbol;
        $this->viewParams['loginNotification'] = $loginNotification;
        $this->viewParams['loginNotificationOptions'] = $loginNotificationOptions;
        $this->viewParams['force2fa'] = $force2fa;
        $this->viewParams['twoFactorOptions'] = $twoFactorOptions;

        return view('admin.settings.members.settings', $this->viewParams);
    }

    public function updateSettings(AdminSettingsMemberSettingsRequest $request)
    {
        // validated() を使えば確実にバリデーション済みの値だけ取得できる
        $validated = $request->validated();


        MemberSetting::setValue('login_notification_mode', (int) $validated['login_notification_mode']);
        MemberSetting::setValue('force_2fa', (int) $validated['force_2fa']);
        MemberSetting::setValue('password_min_length', (int) $validated['password_min_length']);
        MemberSetting::setValue('password_require_uppercase', (int) $validated['password_require_uppercase']);
        MemberSetting::setValue('password_require_symbol', (int) $validated['password_require_symbol']);

        return redirect()->route('admin.settings.members.settings')
            ->with('success', __('admin.settings.members.settings.updated'));
    }
}
