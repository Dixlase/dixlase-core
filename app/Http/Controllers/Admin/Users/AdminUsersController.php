<?php

/**
 * This file is part of MySoftware.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
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


namespace App\Http\Controllers\Admin\Users;

use App\Http\Controllers\Admin\AdminController;
use App\Models\User;
use App\Http\Requests\Admin\Users\AdminUserStoreRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;


class AdminUsersController extends AdminController
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

        $this->viewParams['heading'] = 'admin.features.users.index.heading';

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
        $this->viewParams['heading'] = 'admin.features.users.create.heading';

        return view('admin.users.create', $this->viewParams);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AdminUserStoreRequest $request)
    {
        // バリデーション済みデータを取得
        $validated = $request->validated();

        // パスワードをハッシュ化
        $validated['password'] = bcrypt($validated['password']);

        $validated['name'] = $validated['last_name'] . ' ' . $validated['first_name'];

        // 新しいユーザーを作成
        User::create($validated);

        // 作成したユーザーのIDを取得
        $id = User::latest()->first()->id;

        // リダイレクト
        return redirect()->route('admin.users.edit', ['user' => $id])->with('success', '新しいユーザーが作成されました！');
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
        $this->viewParams['heading'] = 'admin.features.users.edit.heading';
        $this->viewParams['user'] = $user;

        return view('admin.users.edit', $this->viewParams);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AdminUserStoreRequest $request, User $user)
    {
        // バリデーション済みデータを取得
        $validated = $request->validated();

        // パスワードが送信されている場合のみ更新
        if (!empty($validated['password'])) {
            $validated['password'] = bcrypt($validated['password']);
        } else {
            unset($validated['password']); // パスワードが空の場合は更新しない
        }

        // ユーザー情報を更新
        $user->update($validated);

        // 更新したユーザーのIDを取得
        $id = $user->id;

        // リダイレクト
        return redirect()->route('admin.users.edit', ['user' => $id])->with('success', 'ユーザー情報を更新しました！');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        // ユーザーを削除
        $user->delete();

        // リダイレクト
        return redirect()->route('admin.users.index')->with('success', 'ユーザーを削除しました！');
    }
}
