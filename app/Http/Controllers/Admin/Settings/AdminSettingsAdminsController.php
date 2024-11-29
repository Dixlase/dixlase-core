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
        $this->view_params['title'] = __('admin.settings.admins.index');
        $this->view_params['admins'] = Admin::all();

        // 検索条件の取得
        $search = $request->input('search');

        // ユーザーを検索
        $admins = Admin::query()
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            })
            ->paginate(10); // ページネーション

        $this->view_params['admins'] = $admins;
        $this->view_params['search'] = $search;

        // ビューにデータを渡す
        return view('admin.settings.admins.index', $this->view_params);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->view_params['title'] = 'admin.settings.admins.create';
        return view('admin.settings.admins.create', $this->view_params);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AdminSettingsAdminStoreRequest $request)
    {

        // バリデーション済みデータを取得
        $validated = $request->validated();
        $validated['password'] = bcrypt($validated['password']);

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
        $this->view_params['title'] = 'admin.users.create';
        $this->view_params['admin'] = $admin;

        return view('admin.settings.admins.edit', $this->view_params);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AdminSettingsAdminStoreRequest $request, Admin $admin)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $admin->id,
        ]);

        $admin->update($validated);


        return redirect()->route('admin.users.index')->with('success', 'ユーザー情報を更新しました！');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Admin $admin)
    {
        //
    }

    /**
     * Show the form for editing the profile.
     */
    public function profile()
    {
        $this->view_params['title'] = 'admin.settings.admins.profile';
        return view('admin.settings.admins.profile', $this->view_params);
    }
}
