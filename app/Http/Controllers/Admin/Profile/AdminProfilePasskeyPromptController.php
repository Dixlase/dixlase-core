<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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

namespace App\Http\Controllers\Admin\Profile;

use App\Http\Controllers\Admin\AdminLoggedInController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * パスキー登録促進モーダルの制御
 */
class AdminProfilePasskeyPromptController extends AdminLoggedInController
{
    /**
     * パスキー登録促進モーダルを非表示にする
     */
    public function dismiss(Request $request)
    {
        $member = Auth::guard('member')->user();
        
        // passkey_prompt_dismissedフラグを設定
        $member->passkey_prompt_dismissed = true;
        $member->save();
        
        return response()->json([
            'success' => true,
            'message' => __('two_fa.passkey_prompt.dismissed')
        ]);
    }
    
    /**
     * パスキー登録促進モーダルを再表示する（設定をリセット）
     */
    public function reset(Request $request)
    {
        $member = Auth::guard('member')->user();
        
        // passkey_prompt_dismissedフラグをリセット
        $member->passkey_prompt_dismissed = false;
        $member->save();
        
        return response()->json([
            'success' => true,
            'message' => __('two_fa.passkey_prompt.reset')
        ]);
    }
}
