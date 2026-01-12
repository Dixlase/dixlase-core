<?php

namespace App\Traits;

use Illuminate\Http\Request;

/**
 * ログイン・認証処理の共通トレイト
 * 
 * ログイン処理と二段階認証処理で共通して使用される設定メソッドと機能を提供します。
 * このトレイトを使用するコントローラーは、以下の抽象メソッドを実装する必要があります。
 */
trait LoginTrait
{
    /**
     * ログイン画面のルート名を取得（継承先で実装）
     * 
     * @return string ルート名（例: 'admin.login', 'users-plugin::mypage.login'）
     */
    abstract protected function getLoginRoute(): string;

    /**
     * ダッシュボードのルート名を取得（継承先で実装）
     * 
     * @return string ルート名（例: 'admin.dashboard', 'users-plugin::mypage.dashboard'）
     */
    abstract protected function getDashboardRoute(): string;

    /**
     * セッションキーのプレフィックスを取得（継承先で実装）
     * 
     * @return string プレフィックス（例: 'login', 'two_fa'）
     */
    abstract protected function getSessionPrefix(): string;

    /**
     * ユーザーモデルクラス名を取得（継承先で実装）
     * 
     * @return string モデルクラス名（例: 'App\Models\Member', 'Plugins\DixlaseUsers\App\Models\DixlaseUsersUser'）
     */
    abstract protected function getUserModelClass(): string;

    /**
     * 認証ガード名を取得（継承先で実装）
     * 
     * @return string ガード名（例: 'member', 'user'）
     */
    abstract protected function getGuardName(): string;

    /**
     * コンテキストを取得（継承先で実装）
     * 
     * @return string コンテキスト（'admin' または 'user'）
     */
    abstract protected function getContext(): string;

    /**
     * 二段階認証ルートのプレフィックスを取得（継承先で実装）
     * 
     * @return string ルートプレフィックス（例: 'admin', 'users-plugin::mypage'）
     */
    abstract protected function getTwoFaRoutePrefix(): string;

    /**
     * メール認証設定を取得（継承先で実装）
     * 
     * @return array メール認証設定の配列
     * 
     * 設定項目:
     * - verification_completed_notification: ユーザーへの認証完了通知クラス（オプション）
     * - admin_verified_notification: 管理者への認証完了通知クラス（オプション）
     * - admin_email_setting_key: 管理者メールアドレスの設定キー（オプション）
     * - notification_email_setting_key: 通知メールアドレスの設定キー（オプション）
     * - success_message_key: 認証成功メッセージの翻訳キー（必須）
     * - email_change_success_key: メールアドレス変更成功メッセージの翻訳キー（必須）
     * - setting_model_class: 設定モデルクラス（必須）
     */
    abstract protected function getEmailVerificationConfig(): array;

    /**
     * ログイン後にメール認証が待機中の場合、認証処理を実行
     * 
     * @param mixed $user ユーザーモデル（Member または DixlaseUsersUser）
     * @param Request $request リクエストオブジェクト
     * @return void
     */
    protected function processEmailVerificationIfPending($user, Request $request): void
    {
        $verificationService = app(\App\Services\AccountVerificationService::class);
        $verificationService->processIfPending($user, $this->getEmailVerificationConfig());
    }
}
