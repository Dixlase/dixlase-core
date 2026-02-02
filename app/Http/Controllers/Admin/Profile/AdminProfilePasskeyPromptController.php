<?php

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
