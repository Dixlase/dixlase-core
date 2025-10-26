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
        
        // 信頼済みデバイスとして保存
        $this->saveTrustedDevice($challenge);
        
        Log::info("[Device Auth] チャレンジ承認: チャレンジID {$challenge->id}");
        
        return true;
    }
    
    /**
     * 承認されたチャレンジを信頼済みデバイスとして保存
     *
     * @param MembersTwoFactorDevice $challenge
     * @return \App\Models\MembersTrustedDevice
     */
    protected function saveTrustedDevice(MembersTwoFactorDevice $challenge)
    {
        // デバイス名を生成（User Agentから）
        $deviceName = $this->generateDeviceName($challenge->user_agent);
        
        // 新しいデバイストークンを生成（セキュリティ強化）
        $deviceToken = Str::random(64);
        $hashedToken = hash('sha256', $deviceToken);
        
        // 信頼済みデバイスとして保存
        $trustedDevice = \App\Models\MembersTrustedDevice::create([
            'member_id' => $challenge->member_id,
            'device_name' => $deviceName,
            'token' => $hashedToken,
            'ip_address' => $challenge->ip_address,
            'user_agent' => $challenge->user_agent,
            'last_used_at' => now(),
        ]);
        
        // デバイストークンをHTTPOnly Cookieに保存（30日間有効）
        cookie()->queue(
            'trusted_device_token',
            $deviceToken,
            60 * 24 * 30, // 30日
            '/',
            null,
            true, // secure (HTTPS only)
            true, // httpOnly
            false,
            'strict' // sameSite
        );
        
        Log::info("[Device Auth] 信頼済みデバイス保存: ユーザーID {$challenge->member_id}, デバイスID: {$trustedDevice->id}");
        
        return $trustedDevice;
    }
    
    /**
     * 現在のデバイスが信頼済みかチェック
     *
     * @param Member $member
     * @return bool
     */
    public function isTrustedDevice(Member $member): bool
    {
        $deviceToken = request()->cookie('trusted_device_token');
        
        if (!$deviceToken) {
            return false;
        }
        
        $hashedToken = hash('sha256', $deviceToken);
        
        // トークンが一致する信頼済みデバイスを検索
        $trustedDevice = \App\Models\MembersTrustedDevice::where('member_id', $member->id)
            ->where('token', $hashedToken)
            ->first();
        
        if (!$trustedDevice) {
            return false;
        }
        
        // 最終使用日時を更新
        $trustedDevice->update(['last_used_at' => now()]);
        
        Log::info("[Device Auth] 信頼済みデバイス確認: ユーザーID {$member->id}, デバイスID: {$trustedDevice->id}");
        
        return true;
    }
    
    /**
     * User Agentからデバイス名を生成
     *
     * @param string|null $userAgent
     * @return string
     */
    protected function generateDeviceName(?string $userAgent): string
    {
        if (!$userAgent) {
            return 'Unknown Device';
        }
        
        // ブラウザ検出
        $browser = 'Unknown Browser';
        if (preg_match('/Chrome/i', $userAgent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/Firefox/i', $userAgent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/Safari/i', $userAgent) && !preg_match('/Chrome/i', $userAgent)) {
            $browser = 'Safari';
        } elseif (preg_match('/Edge/i', $userAgent)) {
            $browser = 'Edge';
        }
        
        // OS検出
        $os = 'Unknown OS';
        if (preg_match('/Windows/i', $userAgent)) {
            $os = 'Windows';
        } elseif (preg_match('/Macintosh|Mac OS X/i', $userAgent)) {
            $os = 'macOS';
        } elseif (preg_match('/Linux/i', $userAgent)) {
            $os = 'Linux';
        } elseif (preg_match('/iPhone/i', $userAgent)) {
            $os = 'iPhone';
        } elseif (preg_match('/iPad/i', $userAgent)) {
            $os = 'iPad';
        } elseif (preg_match('/Android/i', $userAgent)) {
            $os = 'Android';
        }
        
        return "{$browser} on {$os}";
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

    /**
     * メンバーの信頼済みデバイス一覧を取得
     *
     * @param Member $member
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getDevices(Member $member)
    {
        return \App\Models\MembersTrustedDevice::where('member_id', $member->id)
            ->orderBy('last_used_at', 'desc')
            ->get();
    }

    /**
     * 信頼済みデバイスを削除
     *
     * @param Member $member
     * @param int $deviceId
     * @return bool
     */
    public function revokeDevice(Member $member, int $deviceId): bool
    {
        $deleted = \App\Models\MembersTrustedDevice::where('member_id', $member->id)
            ->where('id', $deviceId)
            ->delete();

        if ($deleted) {
            Log::info("[Device Auth] 信頼済みデバイス削除: ユーザーID {$member->id}, デバイスID: {$deviceId}");
        }

        return $deleted > 0;
    }
}
