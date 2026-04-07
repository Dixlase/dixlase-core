<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
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

namespace App\Traits;

use App\Helpers\IdentifierCheckHelper;
use Illuminate\Http\Request;

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

        if ($captchaResult && ! $captchaResult->isValid()) {
            return response()->json([
                'redirect' => true,
                'message' => $captchaResult->getErrorMessage(),
                'errors' => ['captcha' => [$captchaResult->getErrorMessage()]],
            ], 422);
        }

        // CAPTCHA検証済みフラグをセッションに保存（5分間有効）
        session()->put('captcha_verified_'.$login, time());

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
