<?php

namespace App\Services;

use App\Models\Member;
use App\Models\MemberSetting;
use App\Notifications\AdminLoginNotification;
use App\Traits\LoginNotificationsTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminLoginNotificationService
{
    use LoginNotificationsTrait;

    public function handle(Member $member, Request $request): void
    {
        // ログイン情報を記録
        $this->recordLoginInfo($member, $request);

        // ログイン詳細データを準備
        $loginDetails = $this->prepareLoginDetails($request);

        // メール送信可能性をチェック
        if (!$this->canSendNotification($member, 'Admin login notification')) {
            return;
        }

        // ユーザー通知を送信
        if ($this->shouldSendUserNotification(
            $member, 
            $request->ip(), 
            $request->userAgent(),
            fn() => MemberSetting::getValue('login_notification_mode')
        )) {
            $member->notify(new AdminLoginNotification($loginDetails, false));
        }

        // システム通知を送信
        $this->sendSystemNotification(
            $loginDetails,
            fn() => MemberSetting::getValue('system_login_notice_email') ?? config('mail.from.address'),
            fn() => MemberSetting::getValue('send_login_notice_to_system') === '1',
            AdminLoginNotification::class
        );
    }
}
