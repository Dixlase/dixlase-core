<?php

namespace App\Services;

use App\Models\Member;
use App\Models\MembersTwoFactorDevice;
use App\Mail\MembersTwoFactorDeviceMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class DeviceAuthenticationService
{
    /**
     * デバイス認証チャレンジを生成してメール送信
     *
     * @param Member $member
     * @return MembersTwoFactorDevice
     */
    public function generateDeviceChallenge(Member $member): MembersTwoFactorDevice
    {
        // ランダムトークンを生成
        $token = Str::random(64);
        $hashedToken = hash('sha256', $token);
        
        // 有効期限をMemberSettingから取得
        $expireMinutes = (int) \App\Models\MemberSetting::getValue('two_factor_expire_minutes', 10);
        
        // データベースに保存
        $challenge = MembersTwoFactorDevice::create([
            'member_id' => $member->id,
            'token' => $hashedToken,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'expires_at' => now()->addMinutes($expireMinutes),
        ]);
        
        // セッションにチャレンジIDを保存
        session(['device_challenge_id' => $challenge->id]);
        
        // 承認メールを送信
        Mail::to($member->email)->send(
            new MembersTwoFactorDeviceMail(
                $token,
                $member,
                request()->ip(),
                request()->userAgent()
            )
        );
        
        Log::info("[Device Auth] チャレンジ生成: ユーザーID {$member->id}, チャレンジID {$challenge->id}");
        
        return $challenge;
    }
    
    /**
     * デバイス認証チャレンジの承認状態を確認
     *
     * @return array|null
     */
    public function checkChallengeStatus(): ?array
    {
        $challengeId = session('device_challenge_id');
        
        if (!$challengeId) {
            return null;
        }
        
        $challenge = MembersTwoFactorDevice::find($challengeId);
        
        if (!$challenge) {
            return null;
        }
        
        if ($challenge->isExpired()) {
            return ['status' => 'expired'];
        }
        
        if ($challenge->approved) {
            return [
                'status' => 'approved',
                'member_id' => $challenge->member_id,
            ];
        }
        
        return ['status' => 'pending'];
    }
    
    /**
     * トークンでチャレンジを承認
     *
     * @param string $token
     * @return bool
     */
    public function approveChallenge(string $token): bool
    {
        $hashedToken = hash('sha256', $token);
        
        $challenge = MembersTwoFactorDevice::where('token', $hashedToken)
            ->where('expires_at', '>', now())
            ->where('approved', false)
            ->first();
        
        if (!$challenge) {
            return false;
        }
        
        $challenge->update([
            'approved' => true,
            'approved_at' => now(),
        ]);
        
        Log::info("[Device Auth] チャレンジ承認: チャレンジID {$challenge->id}");
        
        return true;
    }
    
    /**
     * トークンでチャレンジを拒否（削除）
     *
     * @param string $token
     * @return bool
     */
    public function denyChallenge(string $token): bool
    {
        $hashedToken = hash('sha256', $token);
        
        $challenge = MembersTwoFactorDevice::where('token', $hashedToken)
            ->where('expires_at', '>', now())
            ->first();
        
        if (!$challenge) {
            return false;
        }
        
        $challengeId = $challenge->id;
        $challenge->delete();
        
        Log::info("[Device Auth] チャレンジ拒否: チャレンジID {$challengeId}");
        
        return true;
    }
    
    /**
     * 期限切れのチャレンジをクリーンアップ
     *
     * @return int 削除された件数
     */
    public function cleanupExpiredChallenges(): int
    {
        return MembersTwoFactorDevice::where('expires_at', '<', now())->delete();
    }
}
