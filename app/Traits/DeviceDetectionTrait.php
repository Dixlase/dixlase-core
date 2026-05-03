<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact office@exc-d.com).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * @internal コア専用。プラグイン/テーマから参照しないこと
 *
 * デバイス・環境検出の共通トレイト
 */
trait DeviceDetectionTrait
{
    /**
     * 異なる環境（IP/User-Agent）からのアクセスかどうかを判定
     *
     * @param  Model  $user  ユーザーモデル
     * @param  Request|null  $request  リクエストオブジェクト（nullの場合は現在のリクエストを使用）
     * @return bool 異なる環境かどうか
     */
    public function isDifferentEnvironment(Model $user, ?Request $request = null): bool
    {
        $request = $request ?? request();

        $currentIp = $request->ip();
        $currentUserAgent = $request->userAgent();

        // IPが取得できない場合は異なる環境とみなす（安全側に倒す）
        if (! $currentIp) {
            \Illuminate\Support\Facades\Log::info('DeviceDetectionTrait: Different environment (no IP)', ['user_email' => $user->email]);

            return true;
        }

        // User-Agentがnullの場合はデフォルト値を使用（テスト環境対応）
        if (! $currentUserAgent) {
            $currentUserAgent = 'Unknown User Agent';
        }

        // 最近のログイン履歴を取得（24時間以内）
        $recentLogin = \App\Models\MemberLoginAttempt::where('identifier', $user->email)
            ->where('successful', true)
            ->where('attempted_at', '>=', now()->subDay())
            ->orderBy('attempted_at', 'desc')
            ->first();

        // 初回ログインまたは最近のログイン履歴がない場合は異なる環境とみなす
        if (! $recentLogin) {
            return true;
        }

        // IPアドレスまたはUser-Agentが異なる場合は異なる環境
        if ($recentLogin->ip_address !== $currentIp || $recentLogin->user_agent !== $currentUserAgent) {
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
     * @param  Model  $user  ユーザーモデル
     * @param  string  $ip  IPアドレス
     * @param  string  $userAgent  User-Agent
     * @return bool 新しいデバイスかどうか
     */
    public function isNewDevice(Model $user, string $ip, string $userAgent): bool
    {
        // 過去に同じIP/User-Agentの組み合わせでログインしたことがあるかチェック
        // 現在のログインを除外するため、5分前より古いログインを対象とする
        $previousSameLogin = \App\Models\MemberLoginAttempt::where('identifier', $user->email)
            ->where('successful', true)
            ->where('attempted_at', '>=', now()->subDay())
            ->where('attempted_at', '<', now()->subMinutes(5)) // 5分前より古いログインを対象
            ->where('ip_address', $ip)
            ->where('user_agent', $userAgent)
            ->first();

        // 過去に同じ環境からのログインがある場合は既存デバイス
        return ! $previousSameLogin;
    }
}
