<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

trait DeviceDetectionTrait
{
    /**
     * 異なる環境（IP/User-Agent）からのアクセスかどうかを判定
     * 
     * @param Model $user ユーザーモデル
     * @param Request|null $request リクエストオブジェクト（nullの場合は現在のリクエストを使用）
     * @return bool 異なる環境かどうか
     */
    public function isDifferentEnvironment(Model $user, ?Request $request = null): bool
    {
        $request = $request ?? request();
        
        $currentIp = $request->ip();
        $currentUserAgent = $request->userAgent();
        
        // デバッグログ追加
        \Illuminate\Support\Facades\Log::info('DeviceDetectionTrait::isDifferentEnvironment called', [
            'user_email' => $user->email,
            'current_ip' => $currentIp,
            'current_user_agent' => $currentUserAgent ? substr($currentUserAgent, 0, 100) : 'null',
        ]);
        
        // IPが取得できない場合は異なる環境とみなす（安全側に倒す）
        if (!$currentIp) {
            \Illuminate\Support\Facades\Log::info('DeviceDetectionTrait: Different environment (no IP)', ['user_email' => $user->email]);
            return true;
        }
        
        // User-Agentがnullの場合はデフォルト値を使用（テスト環境対応）
        if (!$currentUserAgent) {
            $currentUserAgent = 'Unknown User Agent';
        }
        
        // 最近のログイン履歴を取得（24時間以内）
        $recentLogin = \App\Models\MemberLoginAttempt::where('identifier', $user->email)
            ->where('successful', true)
            ->where('attempted_at', '>=', now()->subDay())
            ->orderBy('attempted_at', 'desc')
            ->first();
        
        // 初回ログインまたは最近のログイン履歴がない場合は異なる環境とみなす
        if (!$recentLogin) {
            \Illuminate\Support\Facades\Log::info('DeviceDetectionTrait: Different environment (no recent login)', ['user_email' => $user->email]);
            return true;
        }
        
        // デバッグログ: 履歴との比較
        \Illuminate\Support\Facades\Log::info('DeviceDetectionTrait: Comparing with recent login', [
            'user_email' => $user->email,
            'recent_ip' => $recentLogin->ip_address,
            'recent_user_agent' => $recentLogin->user_agent ? substr($recentLogin->user_agent, 0, 100) : 'null',
            'recent_attempted_at' => $recentLogin->attempted_at,
            'ip_match' => $recentLogin->ip_address === $currentIp,
            'ua_match' => $recentLogin->user_agent === $currentUserAgent,
        ]);
        
        // IPアドレスまたはUser-Agentが異なる場合は異なる環境
        if ($recentLogin->ip_address !== $currentIp || $recentLogin->user_agent !== $currentUserAgent) {
            \Illuminate\Support\Facades\Log::info('DeviceDetectionTrait: Different environment (IP/UA mismatch)', ['user_email' => $user->email]);
            return true;
        }
        
        // 信頼済みデバイスのチェック（2FA用）
        if (method_exists($user, 'trustedDevices')) {
            $trustedDeviceToken = $request->cookie('trusted_device');
            if ($trustedDeviceToken && $user->trustedDevices()
                ->where('token', hash('sha256', $trustedDeviceToken))
                ->exists()) {
                return false;
            }
        }
        
        return false; // 同じ環境からのアクセス
    }
    
    /**
     * 新しいデバイス/IPからのアクセスかどうかを判定（ログイン通知用）
     * 現在のログインを除外して過去のログイン履歴と比較
     * 
     * @param Model $user ユーザーモデル
     * @param string $ip IPアドレス
     * @param string $userAgent User-Agent
     * @return bool 新しいデバイスかどうか
     */
    public function isNewDevice(Model $user, string $ip, string $userAgent): bool
    {
        // デバッグログ追加
        \Illuminate\Support\Facades\Log::info('DeviceDetectionTrait::isNewDevice called', [
            'user_email' => $user->email,
            'ip' => $ip,
            'user_agent' => $userAgent ? substr($userAgent, 0, 100) : 'null',
        ]);
        
        // 過去に同じIP/User-Agentの組み合わせでログインしたことがあるかチェック
        // 現在のログインを除外するため、5分前より古いログインを対象とする
        $previousSameLogin = \App\Models\MemberLoginAttempt::where('identifier', $user->email)
            ->where('successful', true)
            ->where('attempted_at', '>=', now()->subDay())
            ->where('attempted_at', '<', now()->subMinutes(5)) // 5分前より古いログインを対象
            ->where('ip_address', $ip)
            ->where('user_agent', $userAgent)
            ->first();
        
        // デバッグログ: 過去のログイン履歴
        if ($previousSameLogin) {
            \Illuminate\Support\Facades\Log::info('DeviceDetectionTrait: Found previous same login', [
                'user_email' => $user->email,
                'previous_ip' => $previousSameLogin->ip_address,
                'previous_user_agent' => $previousSameLogin->user_agent ? substr($previousSameLogin->user_agent, 0, 100) : 'null',
                'previous_attempted_at' => $previousSameLogin->attempted_at,
                'is_new_device' => false,
            ]);
        } else {
            \Illuminate\Support\Facades\Log::info('DeviceDetectionTrait: No previous same login found', [
                'user_email' => $user->email,
                'is_new_device' => true,
            ]);
        }
        
        // 過去に同じ環境からのログインがある場合は既存デバイス
        return !$previousSameLogin;
    }
}
