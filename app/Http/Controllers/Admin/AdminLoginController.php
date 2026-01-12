<?php

namespace App\Http\Controllers\Admin;

use App\Models\Member;
use App\Models\MemberSetting;
use App\Repositories\BaseSettingRepository;

/**
 * 管理画面のログインコントローラー
 * 
 * ログイン処理と認証関連の設定を提供します。
 */
class AdminLoginController extends AdminController
{
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
     * メール認証設定を取得
     */
    protected function getEmailVerificationConfig(): array
    {
        return [
            'verification_completed_notification' => \App\Notifications\MemberVerificationCompletedNotification::class,
            'admin_verified_notification' => \App\Notifications\AdminMemberVerifiedNotification::class,
            'admin_email_setting_key' => 'system_admin_email',
            'notification_email_setting_key' => 'notification_email',
            'success_message_key' => 'admin/profile.account_verification_success',
            'email_change_success_key' => 'admin/profile.email_verification_success',
            'setting_model_class' => \App\Models\BaseSetting::class,
        ];
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
        return \App\Models\MemberSetting::class;
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
        return (bool) \App\Models\MemberSetting::getValue('password_reset_enabled', true);
    }

    /**
     * アカウント名でのログインをサポートするかどうか
     */
    protected function supportsAccountNameLogin(): bool
    {
        return true;
    }

    /**
     * pending_emailでのログインをサポートするかどうか
     */
    protected function supportsPendingEmailLogin(): bool
    {
        return true;
    }
}
