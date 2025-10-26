<?php

namespace App\Services;

use App\Models\Member;
use App\Models\MembersTrustedDevice;
use App\Models\MembersTwoFactorDevice;
use App\Mail\MembersTwoFactorDeviceMail;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class DeviceAuthenticationService
{
    /**
     * デバイスを信頼済みとして登録
     *
     * @param Member $member
     * @param string $deviceName
     * @param int $expireDays
     * @return string トークン
     */
    public function registerTrustedDevice(Member $member, string $deviceName = null, int $expireDays = 30): string
    {
        $token = Str::random(64);
        $hashedToken = hash('sha256', $token);
        
        $deviceName = $deviceName ?? $this->generateDeviceName();
        
        MembersTrustedDevice::create([
            'member_id' => $member->id,
            'token' => $hashedToken,
            'device_name' => $deviceName,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'expires_at' => now()->addDays($expireDays),
            'last_used_at' => now(),
        ]);
        
        // Cookieに設定
        Cookie::queue('trusted_device', $token, $expireDays * 24 * 60);
        
        Log::info("[Device Auth] 信頼済みデバイス登録: ユーザーID {$member->id}, デバイス: {$deviceName}");
        
        return $token;
    }
    
    /**
     * 信頼済みデバイスかどうかを確認
     *
     * @param Member $member
     * @return bool
     */
    public function isTrustedDevice(Member $member): bool
    {
        $token = request()->cookie('trusted_device');
        
        if (!$token) {
            return false;
        }
        
        $hashedToken = hash('sha256', $token);
        
        $trustedDevice = MembersTrustedDevice::where('member_id', $member->id)
            ->where('token', $hashedToken)
            ->where('expires_at', '>', now())
            ->first();
            
        if ($trustedDevice) {
            // 最終使用日時を更新
            $trustedDevice->update(['last_used_at' => now()]);
            return true;
        }
        
        return false;
    }
    
    /**
     * 信頼済みデバイスを削除
     *
     * @param Member $member
     * @param string|null $token 特定のトークン（nullの場合は全て削除）
     * @return int 削除された件数
     */
    public function revokeTrustedDevice(Member $member, string $token = null): int
    {
        $query = MembersTrustedDevice::where('member_id', $member->id);
        
        if ($token) {
            $hashedToken = hash('sha256', $token);
            $query->where('token', $hashedToken);
        }
        
        $count = $query->count();
        $query->delete();
        
        // 現在のデバイスのCookieも削除
        if (!$token || $token === request()->cookie('trusted_device')) {
            Cookie::queue(Cookie::forget('trusted_device'));
        }
        
        Log::info("[Device Auth] 信頼済みデバイス削除: ユーザーID {$member->id}, 削除件数: {$count}");
        
        return $count;
    }
    
    /**
     * 期限切れの信頼済みデバイスをクリーンアップ
     *
     * @return int 削除された件数
     */
    public function cleanupExpiredDevices(): int
    {
        return MembersTrustedDevice::where('expires_at', '<', now())->delete();
    }
    
    /**
     * メンバーの信頼済みデバイス一覧を取得
     *
     * @param Member $member
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTrustedDevices(Member $member)
    {
        return MembersTrustedDevice::where('member_id', $member->id)
            ->where('expires_at', '>', now())
            ->orderBy('last_used_at', 'desc')
            ->get();
    }
    
    /**
     * デバイス名を生成
     *
     * @return string
     */
    private function generateDeviceName(): string
    {
        $userAgent = request()->userAgent();
        
        // 簡単なデバイス検出
        if (str_contains($userAgent, 'iPhone')) {
            return 'iPhone';
        } elseif (str_contains($userAgent, 'iPad')) {
            return 'iPad';
        } elseif (str_contains($userAgent, 'Android')) {
            return 'Android Device';
        } elseif (str_contains($userAgent, 'Windows')) {
            return 'Windows PC';
        } elseif (str_contains($userAgent, 'Macintosh')) {
            return 'Mac';
        } elseif (str_contains($userAgent, 'Linux')) {
            return 'Linux PC';
        }
        
        return 'Unknown Device';
    }
    
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
