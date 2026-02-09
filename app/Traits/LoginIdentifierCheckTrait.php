<?php

namespace App\Traits;

use Illuminate\Http\Request;
use App\Helpers\IdentifierCheckHelper;

/**
 * ログイン識別子確認の共通トレイト
 * 
 * メールアドレスまたはアカウント名の存在確認を行う
 * セキュリティ対策：レート制限、タイミング攻撃対策、監査ログ記録
 */
trait LoginIdentifierCheckTrait
{
    /**
     * CAPTCHAアクション名を取得（継承先で実装）
     * 
     * @return string CAPTCHAアクション名（例: 'admin_login', 'user_login'）
     */
    abstract protected function getCaptchaAction(): string;

    /**
     * 設定モデルクラス名を取得（継承先で実装）
     * 
     * @return string 設定モデルクラス名
     */
    abstract protected function getSettingModelClass(): string;

    /**
     * ユーザーモデルクラス名を取得（継承先で実装）
     * 
     * @return string ユーザーモデルクラス名
     */
    abstract protected function getUserModelClass(): string;

    /**
     * コンテキストを取得（継承先で実装）
     * 
     * @return string コンテキスト（'admin' または 'user'）
     */
    abstract protected function getContext(): string;

    /**
     * 識別子確認処理
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function check(Request $request)
    {
        $request->validate([
            'login' => 'required|string|max:255',
        ]);

        $login = $request->input('login');
        $ipAddress = $request->ip();
        
        // CAPTCHA検証
        $captchaAction = $this->getCaptchaAction();
        $captchaResult = \App\Helpers\CaptchaHelper::verify($request, $captchaAction);
        
        if ($captchaResult && !$captchaResult->isValid()) {
            return response()->json([
                'redirect' => true,
                'message' => $captchaResult->getErrorMessage(),
                'errors' => ['captcha' => [$captchaResult->getErrorMessage()]],
            ], 422);
        }
        
        // CAPTCHA検証済みフラグをセッションに保存（5分間有効）
        session()->put('captcha_verified_' . $login, time());
        
        // ロックアウト設定を取得
        $settingModelClass = $this->getSettingModelClass();
        $settings = IdentifierCheckHelper::getLockoutSettings($settingModelClass);
        
        try {
            // 識別子確認を実行（レート制限付き）
            $userModelClass = $this->getUserModelClass();
            $context = $this->getContext();
            
            $result = IdentifierCheckHelper::checkWithRateLimit(
                $login,
                $ipAddress,
                $userModelClass,
                $settings,
                $context
            );
            
            return response()->json([
                'exists' => $result['exists'],
                'has_passkey' => $result['has_passkey'],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // エラーメッセージを取得
            $errors = $e->errors();
            $errorMessage = $errors['login'][0] ?? $e->getMessage();
            
            // セッションにエラーメッセージを保存してリダイレクト指示を返す
            session()->flash('error', $errorMessage);
            
            return response()->json([
                'redirect' => true,
                'message' => $errorMessage,
            ], 422);
        }
    }
}
