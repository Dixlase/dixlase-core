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
        $tokenLength = config('two-factor.device_token_length', 64);
        $token = Str::random($tokenLength);
        $hashedToken = hash('sha256', $token);
        
        // 有効期限をMemberSettingから取得（メンバー設定 > コンフィグ）
        $expireMinutes = (int) \App\Models\MemberSetting::getValue('two_factor_expire_minutes', config('two-factor.code_expiration', 5));
        
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
     * 同じデバイス名（ブラウザ+OS）の既存レコードがあれば更新、なければ新規作成
     *
     * @param MembersTwoFactorDevice $challenge
     * @return \App\Models\MembersTrustedDevice
     */
    protected function saveTrustedDevice(MembersTwoFactorDevice $challenge)
    {
        // デバイス名を生成（User Agentから）
        $deviceName = $this->generateDeviceName($challenge->user_agent);
        
        // 新しいデバイストークンを生成（セキュリティ強化）
        $tokenLength = config('two-factor.device_token_length', 64);
        $deviceToken = Str::random($tokenLength);
        $hashedToken = hash('sha256', $deviceToken);
        
        // 同じデバイス名の既存レコードを検索
        $trustedDevice = \App\Models\MembersTrustedDevice::where('member_id', $challenge->member_id)
            ->where('device_name', $deviceName)
            ->first();
        
        if ($trustedDevice) {
            // 既存レコードを更新（IP、UA、トークンを上書き）
            $trustedDevice->update([
                'token' => $hashedToken,
                'ip_address' => $challenge->ip_address,
                'user_agent' => $challenge->user_agent,
            ]);
            
            Log::info("[Device Auth] 信頼済みデバイス更新: ユーザーID {$challenge->member_id}, デバイスID: {$trustedDevice->id}, デバイス名: {$deviceName}");
        } else {
            // 新規レコードを作成
            $trustedDevice = \App\Models\MembersTrustedDevice::create([
                'member_id' => $challenge->member_id,
                'device_name' => $deviceName,
                'token' => $hashedToken,
                'ip_address' => $challenge->ip_address,
                'user_agent' => $challenge->user_agent,
            ]);
            
            Log::info("[Device Auth] 信頼済みデバイス新規作成: ユーザーID {$challenge->member_id}, デバイスID: {$trustedDevice->id}, デバイス名: {$deviceName}");
        }
        
        // デバイストークンをHTTPOnly Cookieに保存
        $cookieConfig = config('two-factor.device_cookie', []);
        cookie()->queue(
            $cookieConfig['name'] ?? 'trusted_device_token',
            $deviceToken,
            $cookieConfig['lifetime'] ?? 60 * 24 * 30,
            $cookieConfig['path'] ?? '/',
            $cookieConfig['domain'] ?? null,
            $cookieConfig['secure'] ?? true,
            $cookieConfig['http_only'] ?? true,
            false,
            $cookieConfig['same_site'] ?? 'strict'
        );
        
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
        $trustedDevice = null;
        
        // 方法1: Cookieトークンで検証（最も安全）
        if ($deviceToken) {
            $hashedToken = hash('sha256', $deviceToken);
            
            $trustedDevice = \App\Models\MembersTrustedDevice::where('member_id', $member->id)
                ->where('token', $hashedToken)
                ->first();
            
            if ($trustedDevice) {
                Log::info("[Device Auth] Cookie認証成功: ユーザーID {$member->id}, デバイスID: {$trustedDevice->id}");
            }
        }
        
        // 方法2: Cookieがない場合、IP + User Agentで検証（フォールバック）
        if (!$trustedDevice) {
            $ipAddress = request()->ip();
            $userAgent = request()->userAgent();
            
            $trustedDevice = \App\Models\MembersTrustedDevice::where('member_id', $member->id)
                ->where('ip_address', $ipAddress)
                ->where('user_agent', $userAgent)
                ->orderBy('updated_at', 'desc')
                ->first();
            
            if ($trustedDevice) {
                Log::info("[Device Auth] IP+UA認証成功: ユーザーID {$member->id}, デバイスID: {$trustedDevice->id}");
                
                // Cookieを再設定（次回からCookie認証を使用）
                $tokenLength = config('two-factor.device_token_length', 64);
                $newToken = Str::random($tokenLength);
                $hashedToken = hash('sha256', $newToken);
                $trustedDevice->update(['token' => $hashedToken]);
                
                $cookieConfig = config('two-factor.device_cookie', []);
                cookie()->queue(
                    $cookieConfig['name'] ?? 'trusted_device_token',
                    $newToken,
                    $cookieConfig['lifetime'] ?? 60 * 24 * 30,
                    $cookieConfig['path'] ?? '/',
                    $cookieConfig['domain'] ?? null,
                    $cookieConfig['secure'] ?? true,
                    $cookieConfig['http_only'] ?? true,
                    false,
                    $cookieConfig['same_site'] ?? 'strict'
                );
            }
        }
        
        if (!$trustedDevice) {
            Log::info("[Device Auth] 信頼済みデバイスなし: ユーザーID {$member->id}");
            return false;
        }
        
        // 有効期限チェック（メンバー設定 > コンフィグ）
        $expirationDays = (int) \App\Models\MemberSetting::getValue(
            'trusted_device_expire_days',
            config('two-factor.device_expiration_days', 30)
        );
        
        $expirationDate = $trustedDevice->updated_at->addDays($expirationDays);
        
        if (now()->greaterThan($expirationDate)) {
            Log::info("[Device Auth] デバイス有効期限切れ: ユーザーID {$member->id}, デバイスID: {$trustedDevice->id}, 最終更新: {$trustedDevice->updated_at}");
            return false;
        }
        
        // updated_atは自動更新されないため、touch()で更新
        $trustedDevice->touch();
        
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
            return config('two-factor.device_detection.defaults.device', 'Unknown Device');
        }
        
        // コンフィグからブラウザパターンを取得
        $browserPatterns = config('two-factor.device_detection.browsers', []);
        $browser = config('two-factor.device_detection.defaults.browser', 'Unknown Browser');
        
        foreach ($browserPatterns as $name => $pattern) {
            if (preg_match($pattern, $userAgent)) {
                // 除外パターンをチェック
                $exclusions = config("two-factor.device_detection.exclusions.{$name}", []);
                $excluded = false;
                
                foreach ($exclusions as $exclusionPattern) {
                    if (preg_match($exclusionPattern, $userAgent)) {
                        $excluded = true;
                        break;
                    }
                }
                
                if (!$excluded) {
                    $browser = $name;
                    break;
                }
            }
        }
        
        // コンフィグからOSパターンを取得
        $osPatterns = config('two-factor.device_detection.operating_systems', []);
        $os = config('two-factor.device_detection.defaults.os', 'Unknown OS');
        
        foreach ($osPatterns as $name => $pattern) {
            if (preg_match($pattern, $userAgent)) {
                $os = $name;
                break;
            }
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
            ->orderBy('updated_at', 'desc')
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
