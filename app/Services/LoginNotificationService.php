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

namespace App\Services;

use App\Enums\AuthenticationMode;
use App\Traits\DeviceDetectionTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * @internal コア専用。プラグイン/テーマから参照しないこと
 *
 * ログイン通知サービス
 *
 * メンバー管理とユーザー管理の両方で使用できる共通のログイン通知機能を提供
 * 設定の取得は呼び出し側で行い、このサービスは通知ロジックのみを提供
 */
class LoginNotificationService
{
    use DeviceDetectionTrait;

    /**
     * ログイン通知を処理する
     *
     * @param  Model  $user  ユーザーモデル（Member または User）
     * @param  Request  $request  リクエスト
     * @param  callable  $getGlobalSetting  グローバル設定取得関数
     * @param  string  $notificationClass  通知クラス名
     * @param  string  $context  ログ用のコンテキスト名
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

        // メール送信可能性をチェック
        if (! $this->canSendNotification($user, $context)) {
            Log::info($context.' skipped: Mail sending not available');
            // ログイン情報は記録するが通知は送信しない
            $this->recordLoginInfo($user, $request);

            return;
        }

        // IP/UAが取得できない場合は通知をスキップ
        $ip = $request->ip();
        $userAgent = $request->userAgent();

        if (! $ip || ! $userAgent) {
            Log::warning($context.' skipped: IP or User-Agent not available', [
                'ip' => $ip,
                'user_agent' => $userAgent,
                'user_id' => $user->id,
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

        Log::info($context.' decision', [
            'user_email' => $user->email ?? 'N/A',
            'user_id' => $user->id,
            'isDifferentDevice' => $isDifferentDevice,
            'shouldSendUser' => $shouldSendUser,
        ]);

        // ユーザー通知を送信
        if ($shouldSendUser) {
            Log::info('Sending '.$context, [
                'user_email' => $user->email ?? 'N/A',
                'user_id' => $user->id,
            ]);
            $user->notify(new $notificationClass($loginDetails, false));
        } else {
            Log::info($context.' not sent', [
                'user_email' => $user->email ?? 'N/A',
                'user_id' => $user->id,
                'reason' => 'shouldSendUser is false',
            ]);
        }

        // ログイン情報を記録
        $this->recordLoginInfo($user, $request);
    }

    /**
     * ログイン情報を記録する
     */
    protected function recordLoginInfo(Model $user, Request $request): void
    {
        $user->last_login_ip = $request->ip();
        $user->last_login_ua = $request->userAgent();
        $user->last_login_at = now();
        $user->save();
    }

    /**
     * ログイン詳細データを準備する
     */
    protected function prepareLoginDetails(Request $request): array
    {
        return [
            'datetime' => now()->format('Y-m-d H:i:s'),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];
    }

    /**
     * メール送信が可能かチェックし、不可の場合はログを出力
     */
    protected function canSendNotification(Model $user, string $context = 'Login notification'): bool
    {
        if (! MailServerValidatorService::canSendMail()) {
            Log::info($context.' skipped: '.MailServerValidatorService::getMailDisabledReason(), [
                'user_id' => $user->id,
                'user_type' => get_class($user),
                'ip' => request()->ip(),
            ]);

            return false;
        }

        return true;
    }

    /**
     * ユーザー通知を送信すべきかどうかを判定する
     */
    protected function shouldSendUserNotification(
        Model $user,
        string $ip,
        string $ua,
        callable $getGlobalSetting
    ): bool {
        $globalSetting = $getGlobalSetting();
        $globalMode = AuthenticationMode::tryFrom((int) $globalSetting);

        return match ($globalMode) {
            AuthenticationMode::Disabled => false,
            AuthenticationMode::Always => true,
            AuthenticationMode::DifferentDevice => $this->isNewDevice($user, $ip, $ua),
            AuthenticationMode::UseProfileSetting => $this->shouldSendBasedOnProfile($user, $ip, $ua),
            default => false,
        };
    }

    /**
     * プロフィール設定に基づいて通知を送信すべきかを判定
     */
    protected function shouldSendBasedOnProfile(Model $user, string $ip, string $ua): bool
    {
        $profileValue = $user->login_notification_mode ?? 0;
        // Enumオブジェクトの場合は値を取得、整数の場合はそのまま使用
        $profileValueInt = $profileValue instanceof AuthenticationMode ? $profileValue->value : $profileValue;
        $profileMode = $this->mapProfileValueToEnum($profileValueInt);

        Log::info('Profile-based notification decision', [
            'user_email' => $user->email,
            'profile_value' => $profileValue,
            'profile_mode' => $profileMode->name,
            'profile_mode_value' => $profileMode->value,
        ]);

        $result = match ($profileMode) {
            AuthenticationMode::Disabled => false,
            AuthenticationMode::Always => true,
            AuthenticationMode::DifferentDevice => $this->isNewDevice($user, $ip, $ua),
            default => false,
        };

        Log::info('Profile notification result', [
            'user_email' => $user->email,
            'should_send' => $result,
        ]);

        return $result;
    }

    /**
     * プロフィール設定の値をAuthenticationMode enumにマッピング
     */
    protected function mapProfileValueToEnum(int $profileValue): AuthenticationMode
    {
        return match ($profileValue) {
            0 => AuthenticationMode::Disabled,
            1 => AuthenticationMode::DifferentDevice,
            2 => AuthenticationMode::Always,
            default => AuthenticationMode::Disabled,
        };
    }
}
