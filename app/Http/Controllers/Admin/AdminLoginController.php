<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LoginIdentifierMode;
use App\Models\Member;
use App\Repositories\BaseSettingRepository;

/**
 * 管理画面のログインコントローラー
 *
 * ログイン処理と認証関連の設定を提供します。
 */
class AdminLoginController extends AdminController
{
    use \App\Traits\AccountVerificationTrait;
    use \App\Traits\LoginTrait;

    protected BaseSettingRepository $baseSettingRepository;

    public function __construct(BaseSettingRepository $baseSettingRepository)
    {
        parent::__construct();
        $this->baseSettingRepository = $baseSettingRepository;
    }

    /**
     * ログインルート名を取得
     */
    protected function getLoginRoute(): string
    {
        return 'admin.login';
    }

    /**
     * ダッシュボードのルート名を取得
     */
    protected function getDashboardRoute(): string
    {
        return 'admin.dashboard';
    }

    /**
     * セッションキーのプレフィックスを取得
     */
    protected function getSessionPrefix(): string
    {
        return 'login';
    }

    /**
     * ユーザーモデルクラス名を取得
     */
    protected function getUserModelClass(): string
    {
        return Member::class;
    }

    /**
     * 認証ガード名を取得
     */
    protected function getGuardName(): string
    {
        return config('auth.defaults.guard', 'member');
    }

    /**
     * コンテキストを取得
     */
    protected function getContext(): string
    {
        return 'admin';
    }

    /**
     * 二段階認証ルートのプレフィックスを取得
     */
    protected function getTwoFaRoutePrefix(): string
    {
        return $this->baseSettingRepository->get('admin_url', 'admin');
    }

    /**
     * 管理者メールアドレス設定キーを取得
     */
    protected function getAdminEmailSettingKey(): string
    {
        return 'system_admin_email';
    }

    /**
     * 通知メールアドレス設定キーを取得
     */
    protected function getNotificationEmailSettingKey(): string
    {
        return 'notification_email';
    }

    /**
     * ログアウト後のリダイレクト先を取得
     */
    protected function getLogoutRedirectRoute(): string
    {
        return 'admin.login';
    }

    /**
     * 設定モデルクラス名を取得
     */
    protected function getSettingModelClass(): string
    {
        return \App\Models\SecuritySetting::class;
    }

    /**
     * ログイン試行モデルクラス名を取得
     */
    protected function getLoginAttemptModelClass(): string
    {
        return \App\Models\MemberLoginAttempt::class;
    }

    /**
     * ロックアウトサービスクラス名を取得
     */
    protected function getLockoutServiceClass(): string
    {
        return \App\Services\AdminLoginLockoutService::class;
    }

    /**
     * ログイン通知サービスクラス名を取得
     */
    protected function getLoginNotificationServiceClass(): string
    {
        return \App\Services\AdminLoginNotificationService::class;
    }

    /**
     * ログインビュー名を取得
     */
    protected function getLoginViewName(): string
    {
        return 'admin::login';
    }

    /**
     * CAPTCHAアクション名を取得
     */
    protected function getCaptchaAction(): string
    {
        return 'admin_login';
    }

    /**
     * パスワードリセット機能が有効かどうかを取得
     */
    protected function isPasswordResetEnabled(): bool
    {
        return (bool) \App\Models\SecuritySetting::getValue('password_reset_enabled', false);
    }

    /**
     * ログイン識別子モードを取得
     */
    protected function getLoginIdentifierMode(): LoginIdentifierMode
    {
        $value = (int) \App\Models\SecuritySetting::getValue('login_identifier_mode', LoginIdentifierMode::EmailOrAccountName->value);

        return LoginIdentifierMode::tryFrom($value) ?? LoginIdentifierMode::EmailOrAccountName;
    }

    /**
     * アカウント名でのログインをサポートするかどうか
     */
    protected function supportsAccountNameLogin(): bool
    {
        return $this->getLoginIdentifierMode()->supportsAccountName();
    }

    /**
     * メールアドレスでのログインをサポートするかどうか
     */
    protected function supportsEmailLogin(): bool
    {
        return $this->getLoginIdentifierMode()->supportsEmail();
    }

    /**
     * pending_emailでのログインをサポートするかどうか
     */
    protected function supportsPendingEmailLogin(): bool
    {
        return false;
    }

    /**
     * リカバリーコード画面のルート名を取得
     */
    protected function getRecoveryCodeRoute(): string
    {
        return 'admin.two-fa.recovery-code.show';
    }
}
