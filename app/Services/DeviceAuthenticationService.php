<?php

namespace App\Services;

use App\Models\Member;
use App\Models\MembersTrustedDevice;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
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
     * デバイス認証チャレンジを生成
     *
     * @param Member $member
     * @return array
     */
    public function generateDeviceChallenge(Member $member): array
    {
        $challenge = [
            'challenge_id' => Str::uuid(),
            'timestamp' => now()->timestamp,
            'member_id' => $member->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ];
        
        // セッションに保存
        session(['device_challenge' => $challenge]);
        
        return $challenge;
    }
    
    /**
     * デバイス認証チャレンジを検証
     *
     * @param array $response
     * @return bool
     */
    public function verifyDeviceChallenge(array $response): bool
    {
        $challenge = session('device_challenge');
        
        if (!$challenge) {
            return false;
        }
        
        // 基本的な検証（実際の実装では、より複雑な検証を行う）
        $isValid = isset($response['challenge_id']) &&
                   $response['challenge_id'] === $challenge['challenge_id'] &&
                   isset($response['timestamp']) &&
                   abs($response['timestamp'] - $challenge['timestamp']) < 300; // 5分以内
        
        if ($isValid) {
            session()->forget('device_challenge');
        }
        
        return $isValid;
    }
}
