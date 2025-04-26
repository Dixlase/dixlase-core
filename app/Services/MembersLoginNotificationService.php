<?php

namespace App\Services;

use App\Models\Member;
use App\Models\MemberSetting;
use App\Mail\MembersLoginNotificationMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use App\Enums\LoginNotificationModeGlobal;
use App\Enums\LoginNotificationMode;

class MembersLoginNotificationService
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

        if ($this->shouldSend($member, $ip, $ua)) {
            Mail::to($member->email)->send(new MembersLoginNotificationMail($member, $ip, $ua, $now, false));
        }

        if (MemberSetting::getValue('send_login_notice_to_system') === '1') {
            $adminEmail = MemberSetting::getValue('system_login_notice_email') ?? config('mail.from.address');
            Mail::to($adminEmail)->send(new MembersLoginNotificationMail($member, $ip, $ua, $now, true));
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
