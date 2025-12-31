<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * https://exc-d.com
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

namespace App\Services;

use App\Traits\LoginNotificationsTrait;
use App\Traits\DeviceDetectionTrait;
use App\Enums\LoginNotificationMode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * ログイン通知サービス
 * 
 * メンバー管理とユーザー管理の両方で使用できる共通のログイン通知機能を提供
 * 設定の取得は呼び出し側で行い、このサービスは通知ロジックのみを提供
 */
class LoginNotificationService
{
    use LoginNotificationsTrait, DeviceDetectionTrait;

    /**
     * ログイン通知を処理する
     * 
     * @param Model $user ユーザーモデル（Member または User）
     * @param Request $request リクエスト
     * @param callable $getGlobalSetting グローバル設定取得関数
     * @param string $notificationClass 通知クラス名
     * @param string $context ログ用のコンテキスト名
     * @return void
     */
    public function handle(
        Model $user,
        Request $request,
        callable $getGlobalSetting,
        string $notificationClass,
        string $context = 'Login notification'
    ): void {
        // ログイン詳細データを準備
        $loginDetails = $this->prepareLoginDetails($request);

        // デバッグログ
        Log::info($context . ' called', [
            'user_email' => $user->email ?? 'N/A',
            'user_id' => $user->id,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // メール送信可能性をチェック
        if (!$this->canSendNotification($user, $context)) {
            Log::info($context . ' skipped: Mail sending not available');
            // ログイン情報は記録するが通知は送信しない
            $this->recordLoginInfo($user, $request);
            return;
        }

        // IP/UAが取得できない場合は通知をスキップ
        $ip = $request->ip();
        $userAgent = $request->userAgent();
        
        if (!$ip || !$userAgent) {
            Log::warning($context . ' skipped: IP or User-Agent not available', [
                'ip' => $ip,
                'user_agent' => $userAgent,
                'user_id' => $user->id
            ]);
            // ログイン情報は記録するが通知は送信しない
            $this->recordLoginInfo($user, $request);
            return;
        }

        // 通知送信判定
        $isDifferentDevice = $this->isNewDevice($user, $ip, $userAgent);
        $shouldSendUser = $this->shouldSendUserNotification(
            $user,
            $ip,
            $userAgent,
            $getGlobalSetting
        );

        // デバッグログ
        Log::info($context . ' decision', [
            'user_email' => $user->email ?? 'N/A',
            'user_id' => $user->id,
            'isDifferentDevice' => $isDifferentDevice,
            'shouldSendUser' => $shouldSendUser,
        ]);

        // ユーザー通知を送信
        if ($shouldSendUser) {
            Log::info('Sending ' . $context, [
                'user_email' => $user->email ?? 'N/A',
                'user_id' => $user->id
            ]);
            $user->notify(new $notificationClass($loginDetails, false));
        } else {
            Log::info($context . ' not sent', [
                'user_email' => $user->email ?? 'N/A',
                'user_id' => $user->id,
                'reason' => 'shouldSendUser is false'
            ]);
        }

        // ログイン情報を記録
        $this->recordLoginInfo($user, $request);
    }
}
