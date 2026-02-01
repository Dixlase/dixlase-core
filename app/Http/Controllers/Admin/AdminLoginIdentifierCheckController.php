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

namespace App\Http\Controllers\Admin;

use App\Models\Member;
use App\Helpers\IdentifierCheckHelper;
use App\Http\Requests\Admin\AdminLoginIdentifierCheckRequest;

/**
 * ログイン識別子確認コントローラー
 * 
 * メールアドレスまたはアカウント名の存在確認を行う
 * セキュリティ対策：レート制限、タイミング攻撃対策、監査ログ記録
 */
class AdminLoginIdentifierCheckController extends AdminController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * メンバー存在確認
     * 
     * @param AdminLoginIdentifierCheckRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function check(AdminLoginIdentifierCheckRequest $request)
    {
        $login = $request->input('login');
        $ipAddress = $request->ip();
        
        // ロックアウト設定を取得
        $settings = IdentifierCheckHelper::getLockoutSettings(\App\Models\SecuritySetting::class);
        
        try {
            // 識別子確認を実行（レート制限付き）
            $result = IdentifierCheckHelper::checkWithRateLimit(
                $login,
                $ipAddress,
                Member::class,
                $settings,
                'admin'
            );
            
            return response()->json([
                'exists' => $result['exists'],
                'has_passkey' => $result['has_passkey'],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // バリデーションエラーの場合、JSONエラーレスポンスを返す
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        }
    }
}
