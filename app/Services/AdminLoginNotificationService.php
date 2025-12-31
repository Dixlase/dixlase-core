<?php

namespace App\Services;

use App\Models\Member;
use App\Models\MemberSetting;
use App\Notifications\AdminLoginNotification;
use Illuminate\Http\Request;

/**
 * 管理画面ログイン通知サービス
 * 
 * LoginNotificationServiceを使用してメンバーのログイン通知を処理
 */
class AdminLoginNotificationService
{
    protected LoginNotificationService $loginNotificationService;

    public function __construct()
    {
        $this->loginNotificationService = new LoginNotificationService();
    }

    /**
     * メンバーのログイン通知を処理
     * 
     * @param Member $member メンバーモデル
     * @param Request $request リクエスト
     * @return void
     */
    public function handle(Member $member, Request $request): void
    {
        $this->loginNotificationService->handle(
            $member,
            $request,
            fn() => MemberSetting::getValue('login_notification_mode', '0'),
            AdminLoginNotification::class,
            'Admin login notification'
        );
    }
}
