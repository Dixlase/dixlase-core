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

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait RoleCheckTrait
{

    protected string $defaultRole = 'viewer';

    /**
     * 権限チェックメソッド
     *
     * @param string $requiredRole 必要なロール
     * @return void
     */
    public function checkPermission(string $requiredRole): void
    {
        // 管理者ガードからログイン中のユーザーを取得
        $member = Auth::guard('member')->user();
        $memberRole = $member->role ?? null; // ユーザーのロールを取得
        $rolesHierarchy = config('admin.roles_hierarchy'); // 権限階層を取得

        // 権限が不足している場合は403エラーをスロー
        if (!$memberRole || !in_array($memberRole, $rolesHierarchy[$requiredRole])) {
            abort(403, 'この操作を行う権限がありません。');
        }
    }

    // デフォルト権限をチェック
    public function checkDefaultPermission(): void
    {
        $this->checkPermission($this->defaultRole);
    }
}
