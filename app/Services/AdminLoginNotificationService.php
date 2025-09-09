<?php

namespace App\Services;

use App\Models\Member;
use App\Models\MemberSetting;
use App\Notifications\AdminLoginNotification;
use App\Traits\LoginNotificationsTrait;
use App\Traits\DeviceDetectionTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminLoginNotificationService
{
    use LoginNotificationsTrait, DeviceDetectionTrait;

    public function handle(Member $member, Request $request): void
    {
        // ログイン詳細データを準備
        $loginDetails = $this->prepareLoginDetails($request);

        // デバッグログ追加
        Log::info('AdminLoginNotificationService::handle called', [
            'member_email' => $member->email,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'uri' => $request->getRequestUri(),
        ]);

        // メール送信可能性をチェック
        if (!$this->canSendNotification($member, 'Admin login notification')) {
            Log::info('Login notification skipped: Mail sending not available');
            // ログイン情報は記録するが通知は送信しない
            $this->recordLoginInfo($member, $request);
            return;
        }

        // IP/UAが取得できない場合は通知をスキップ
        $ip = $request->ip();
        $userAgent = $request->userAgent();
        
        if (!$ip || !$userAgent) {
            Log::warning('Login notification skipped: IP or User-Agent not available', [
                'ip' => $ip,
                'user_agent' => $userAgent,
                'member_id' => $member->id
            ]);
            // ログイン情報は記録するが通知は送信しない
            $this->recordLoginInfo($member, $request);
            return;
        }

        // 通知送信判定（ログイン情報記録後に実行）
        // 現在のログインを除外して過去のログイン履歴と比較
        $isDifferentDevice = $this->isNewDevice($member, $ip, $userAgent);
        
        $globalMode = MemberSetting::getValue('login_notification_mode');
        $shouldSendUser = match ($globalMode) {
            '0' => false, // Disabled
            '1' => $this->shouldSendBasedOnProfile($member, $ip, $userAgent), // UseProfileSetting
            '2' => $isDifferentDevice, // OnlyNewDevice - 異なるデバイス時のみ
            '3' => true, // Always
            default => false,
        };

        // デバッグログ追加
        Log::info('Login notification decision', [
            'member_email' => $member->email,
            'isDifferentDevice' => $isDifferentDevice,
            'globalMode' => $globalMode,
            'shouldSendUser' => $shouldSendUser,
        ]);

        // ユーザー通知を送信（ログイン情報記録前）
        if ($shouldSendUser) {
            Log::info('Sending login notification', ['member_email' => $member->email]);
            $member->notify(new AdminLoginNotification($loginDetails, false));
        } else {
            Log::info('Login notification not sent', ['member_email' => $member->email, 'reason' => 'shouldSendUser is false']);
        }

        // ログイン情報を記録（通知送信後）
        $this->recordLoginInfo($member, $request);
    }
}
