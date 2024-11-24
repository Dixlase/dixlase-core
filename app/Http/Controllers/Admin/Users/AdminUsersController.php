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

namespace App\Http\Controllers\Admin\Users;

use App\Http\Controllers\Admin\AdminController;
use App\Models\Admin;
use App\Models\User;
use App\Http\Requests\Admin\Users\AdminUserStoreRequest;
use Illuminate\Http\Request;


class AdminUsersController extends AdminController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        $this->viewParams['title'] = 'admin.users.index';

        // 検索条件の取得
        $search = $request->input('search');

        // ユーザーを検索
        $users = User::query()
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            })
            ->paginate(10); // ページネーション

        $this->viewParams['users'] = $users;
        $this->viewParams['search'] = $search;

        // ビューにデータを渡す
        return view('admin.users.index', $this->viewParams);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->viewParams['title'] = 'admin.users.create';

        return view('admin.users.create', $this->viewParams);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AdminUserStoreRequest $request)
    {
        // バリデーション済みデータを取得
        $validated = $request->validated();

        // 新しいユーザーを作成
        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']), // パスワードをハッシュ化
        ]);

        // リダイレクト
        return redirect()->route('admin.users.index')->with('success', '新しいユーザーが作成されました！');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        $this->viewParams['title'] = 'admin.users.create';
        $this->viewParams['user'] = $user;

        return view('admin.users.edit', $this->viewParams);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
        ]);

        $user->update($validated);

        return redirect()->route('admin.users.index')->with('success', 'ユーザー情報を更新しました！');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        //
    }
}
