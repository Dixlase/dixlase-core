<?php

namespace App\Services;

use App\Models\Member;
use App\Models\MemberSetting;
use App\Mail\MembersLoginNotificationMail;
use App\Notifications\AdminLoginNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use App\Enums\LoginNotificationModeGlobal;
use App\Enums\LoginNotificationMode;
use Illuminate\Support\Facades\Log;

class AdminLoginNotificationService
{
    public function handle(Member $member, Request $request): void
    {
        $ip = $request->ip();
        $ua = $request->userAgent();
        $now = now();

        $member->last_login_ip = $ip;
        $member->last_login_ua = $ua;
        $member->last_login_at = $now;
        $member->save();

        // Prepare login details for notification
        $loginDetails = [
            'datetime' => $now->format('Y-m-d H:i:s'),
            'ip' => $ip,
            'user_agent' => $ua,
        ];

        // メールサーバーが設定・テスト済みの場合のみ通知を送信
        if (!MailServerValidatorService::canSendMail()) {
            Log::info('Login notification skipped: ' . MailServerValidatorService::getMailDisabledReason(), [
                'member_id' => $member->id,
                'ip' => $ip
            ]);
            return;
        }

        // Send user notification if conditions are met
        if ($this->shouldSend($member, $ip, $ua)) {
            $member->notify(new AdminLoginNotification($loginDetails, false));
        }

        // Send system notification if enabled
        if (MemberSetting::getValue('send_login_notice_to_system') === '1') {
            $adminEmail = MemberSetting::getValue('system_login_notice_email') ?? config('mail.from.address');
            
            // Create a temporary user object for system notification
            $systemNotifiable = new class($adminEmail) {
                public function __construct(public string $email) {}
                public function routeNotificationForMail() { return $this->email; }
                public $name = 'System Administrator';
            };
            
            $systemNotifiable->notify(new AdminLoginNotification($loginDetails, true));
        }
    }

    private function shouldSend(Member $member, string $ip, string $ua): bool
    {
        $globalSetting = MemberSetting::getValue('login_notification_mode');
        $globalMode = LoginNotificationMode::tryFrom((int) $globalSetting);

        return match ($globalMode) {
            LoginNotificationMode::Disabled => false,
            LoginNotificationMode::Always => true,
            LoginNotificationMode::OnlyNewDevice => $ip !== $member->last_login_ip || $ua !== $member->last_login_ua,
            LoginNotificationMode::UseProfileSetting => match (LoginNotificationMode::tryFrom($member->login_notification_mode)) {
                LoginNotificationMode::Disabled => false,
                LoginNotificationMode::Always => true,
                LoginNotificationMode::OnlyNewDevice => $ip !== $member->last_login_ip || $ua !== $member->last_login_ua,
                default => false,
            },
            default => false,
        };
    }
}
