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
use App\Http\Requests\Admin\Settings\Member\AdminSettingsMemberStoreRequest;
use App\Models\Member;
use App\Models\MemberSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Enum;
use App\Enums\TwoFactorModeMember;
use App\Enums\TwoFactorModeGlobal;
use App\Enums\LoginNotificationModeMember;
use App\Enums\LoginNotificationModeGlobal;


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
        return view('admin::settings.members.create', $this->viewParams);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AdminSettingsMemberStoreRequest $request)
    {

        // バリデーション済みデータを取得
        $validated = $request->validated();
        $validated['password'] = bcrypt($validated['password']);

        // 新しい管理者を作成
        $member = Member::create($validated);

        // リダイレクト
        return redirect()->route('admin.settings.members.edit', ['member' => $member->id])->with('success', '新しいユーザーが作成されました！');



        // 新しいユーザーを作成
        Member::create($validated);

        // リダイレクト
        return redirect()->route('admin.settings.members.index')->with('success', '新しい管理者アカウントが作成されました！');
    }

    /**
     * Display the specified resource.
     */
    public function show(Member $admin)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Member $member)
    {
        $this->viewParams['member'] = $member;
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
            $validated['password'] = bcrypt($validated['password']);
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

        $loginNoticeGlobal = (int) MemberSetting::getValue(
            'login_notification_mode',
            LoginNotificationModeGlobal::UseProfileSetting->value
        );
        $loginNotificationMode = Auth::guard('member')->user()->login_notification_mode;

        $loginNotificationOptions = collect(LoginNotificationModeMember::cases())->mapWithKeys(function ($case) {
            return [$case->value => $case->label()];
        })->toArray();

        $this->viewParams['loginNoticeGlobal'] = $loginNoticeGlobal;
        $this->viewParams['loginNotificationMode'] = $loginNotificationMode;
        $this->viewParams['loginNotificationOptions'] = $loginNotificationOptions;


        $force2fa = MemberSetting::getValue(
            'force_2fa',
            TwoFactorModeGlobal::UseProfileSetting->value
        );
        $twoFactorMode = Auth::guard('member')->user()->two_factor_mode;

        $twoFactorOptions = collect(TwoFactorModeMember::cases())->mapWithKeys(function ($case) {
            return [$case->value => $case->label()];
        })->toArray();

        $this->viewParams['force2fa'] = $force2fa;
        $this->viewParams['twoFactorMode'] = $twoFactorMode;
        $this->viewParams['twoFactorOptions'] = $twoFactorOptions;


        return view('admin.settings.members.profile', $this->viewParams);
    }

    public function updateProfile(Request $request)
    {
        $member = Auth::guard('member')->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'email' => 'required|email|unique:members,email,' . $member->id,
            'password' => 'nullable|min:8|confirmed',
            'two_factor_mode' => ['nullable', new Enum(TwoFactorModeMember::class)],
            'login_notification_mode' => ['nullable', new Enum(LoginNotificationModeMember::class)],
        ]);

        $member->fill([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? '',
            'email' => $validated['email'],
        ]);

        if (!empty($validated['password'])) {
            $member->password = bcrypt($validated['password']);
        }

        // login_notification_mode は全体設定が 0 のときだけ上書き
        $globalLogin = (int) MemberSetting::getValue('login_notification_mode', LoginNotificationModeGlobal::UseProfileSetting->value);
        if ($globalLogin === LoginNotificationModeGlobal::UseProfileSetting->value && array_key_exists('login_notification_mode', $validated)) {
            $member->login_notification_mode = (int) $validated['login_notification_mode'];
        }


        // two_factor_mode は全体設定が 0 のときだけ上書き
        $force2fa = (int) MemberSetting::getValue('force_2fa', TwoFactorModeGlobal::UseProfileSetting->value);
        if ($force2fa === TwoFactorModeGlobal::UseProfileSetting->value && array_key_exists('two_factor_mode', $validated)) {
            $member->two_factor_mode = (int) $validated['two_factor_mode'];
        }

        $member->save();

        return redirect()->route('admin.settings.members.profile')
            ->with('success', __('admin.settings.members.profile.updated'));
    }


    public function settings()
    {

        // ログイン通知設定の追加
        $loginNotification = (int) MemberSetting::getValue('login_notification_mode', \App\Enums\LoginNotificationModeGlobal::UseProfileSetting->value);
        $loginNotificationOptions = collect(config('admin.settings.members.login_notification_mode.options_global'));

        // 二段階認証設定の追加
        $force2fa = (int) MemberSetting::getValue('force_2fa', TwoFactorModeMember::Disabled->value);
        $twoFactorOptions = collect(TwoFactorModeMember::cases())->mapWithKeys(function ($case) {
            return [$case->value => $case->label()];
        })->toArray();


        //
        $this->viewParams['loginNotification'] = $loginNotification;
        $this->viewParams['loginNotificationOptions'] = $loginNotificationOptions;
        $this->viewParams['force2fa'] = $force2fa;
        $this->viewParams['twoFactorOptions'] = $twoFactorOptions;

        return view('admin.settings.members.settings', $this->viewParams);
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'force_2fa' => ['required', new Enum(TwoFactorModeGlobal::class)],
        ]);

        MemberSetting::setValue('login_notification_mode', (int) $request->input('login_notification_mode'));
        MemberSetting::setValue('force_2fa', (int) $request->input('force_2fa'));

        return redirect()->route('admin.settings.members.settings')
            ->with('success', __('admin.settings.members.settings.updated'));
    }
}
