<?php

namespace App\Http\Controllers\Admin;

use App\Models\Member;
use App\Models\MemberSetting;
use App\Repositories\BaseSettingRepository;

/**
 * 管理画面の認証関連コントローラーの基底クラス
 * 
 * ログインコントローラーと二段階認証コントローラーで共通する設定メソッドを提供します。
 */
abstract class AdminAuthController extends AdminController
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
     * ログイン後にメール認証が待機中の場合、認証処理を実行
     * 
     * @param mixed $member メンバーモデル
     * @param \Illuminate\Http\Request $request リクエストオブジェクト
     * @return void
     */
    protected function processEmailVerificationIfPending($member, $request): void
    {
        $verificationService = app(\App\Services\AccountVerificationService::class);
        
        $verificationService->processIfPending($member, [
            'verification_completed_notification' => \App\Notifications\MemberVerificationCompletedNotification::class,
            'admin_verified_notification' => \App\Notifications\AdminMemberVerifiedNotification::class,
            'admin_email_setting_key' => 'system_admin_email',
            'notification_email_setting_key' => 'notification_email',
            'success_message_key' => 'admin/profile.account_verification_success',
            'email_change_success_key' => 'admin/profile.email_verification_success',
            'setting_model_class' => \App\Models\BaseSetting::class,
        ]);
    }
}
