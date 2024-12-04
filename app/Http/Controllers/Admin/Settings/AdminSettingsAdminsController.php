<?php

/**
 * This file is part of Your Software Name.
 *
 * Copyright (C) 2024 exc-D inc.
 * Website: https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminController;
use App\Models\Admin;
use App\Http\Requests\Admin\Settings\Admin\AdminSettingsAdminStoreRequest;
use Illuminate\Http\Request;

class AdminSettingsAdminsController extends AdminController
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
        //
        $this->viewParams['heading'] = __('admin.features.settings.admins.index.heading');
        $this->viewParams['admins'] = Admin::all();

        // 検索条件の取得
        $search = $request->input('search');

        // ユーザーを検索
        $admins = Admin::query()
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            })
            ->paginate(10); // ページネーション

        $this->viewParams['admins'] = $admins;
        $this->viewParams['search'] = $search;

        // ビューにデータを渡す
        return view('admin::settings.admins.index', $this->viewParams);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->viewParams['heading'] = 'admin.features.settings.admins.create.heading';
        return view('admin::settings.admins.create', $this->viewParams);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AdminSettingsAdminStoreRequest $request)
    {

        // バリデーション済みデータを取得
        $validated = $request->validated();
        $validated['password'] = bcrypt($validated['password']);

        // 新しい管理者を作成
        $admin = Admin::create($validated);

        // リダイレクト
        return redirect()->route('admin.settings.admins.edit', ['admin' => $admin->id])->with('success', '新しいユーザーが作成されました！');



        // 新しいユーザーを作成
        Admin::create($validated);

        // リダイレクト
        return redirect()->route('admin.settings.admins.index')->with('success', '新しい管理者アカウントが作成されました！');
    }

    /**
     * Display the specified resource.
     */
    public function show(Admin $admin)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Admin $admin)
    {
        $this->viewParams['heading'] = 'admin.features.settings.admins.edit.heading';
        $this->viewParams['admin'] = $admin;



        return view('admin::settings.admins.edit', $this->viewParams);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AdminSettingsAdminStoreRequest $request, Admin $admin)
    {
        // バリデーション済みデータを取得
        $validated = $request->validated();

        // パスワードが送信されている場合のみ更新
        if (!empty($validated['password'])) {
            $validated['password'] = bcrypt($validated['password']);
        } else {
            unset($validated['password']); // パスワードが空の場合は更新しない
        }

        $admin->update($validated);
        $id = $admin->id;

        return redirect()->route('admin.settings.admins.edit', ['admin' => $id])->with('success', '管理車情報を更新しました！');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Admin $admin)
    {
        $admin->delete();

        return redirect()->route('admin.settings.admins.index')->with('success', '管理者アカウントを削除しました！');
    }

    /**
     * Show the form for editing the profile.
     */
    public function profile()
    {
        $this->viewParams['heading'] = 'admin.settings.admins.profile.heading';
        return view('admin.settings.admins.profile', $this->viewParams);
    }
}
