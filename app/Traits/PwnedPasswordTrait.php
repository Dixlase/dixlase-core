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
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
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

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * @internal コア専用。プラグイン/テーマから参照しないこと
 *
 * Have I Been Pwned API連携トレイト
 *
 * パスワードが漏洩データベースに含まれているかチェックする機能を提供
 * ユーザー管理プラグインでも再利用可能
 */
trait PwnedPasswordTrait
{
    /**
     * パスワードが漏洩しているかチェック
     *
     * @param  string  $password  チェックするパスワード
     * @return array ['is_pwned' => bool, 'count' => int, 'error' => string|null]
     */
    public function checkPwnedPassword(string $password): array
    {
        try {
            // パスワードをSHA-1ハッシュ化
            $hash = strtoupper(sha1($password));
            $prefix = substr($hash, 0, 5);
            $suffix = substr($hash, 5);

            // Have I Been Pwned API v3にリクエスト
            $apiEndpoint = config('security.pwned_passwords.api_endpoint', 'https://api.pwnedpasswords.com');
            $timeout = config('security.pwned_passwords.timeout', 5);

            $response = Http::timeout($timeout)
                ->withHeaders([
                    'User-Agent' => 'Dixlase-Password-Checker/1.0',
                    'Add-Padding' => 'true', // レスポンスサイズを一定にしてプライバシー保護
                ])
                ->get("{$apiEndpoint}/range/{$prefix}");

            if (! $response->successful()) {
                Log::warning('Have I Been Pwned API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'is_pwned' => false,
                    'count' => 0,
                    'error' => 'API request failed',
                ];
            }

            // レスポンスを解析
            $hashes = $response->body();
            $lines = explode("\n", $hashes);

            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) {
                    continue;
                }

                [$hashSuffix, $count] = explode(':', $line);

                if (strtoupper($hashSuffix) === $suffix) {
                    return [
                        'is_pwned' => true,
                        'count' => (int) $count,
                        'error' => null,
                    ];
                }
            }

            return [
                'is_pwned' => false,
                'count' => 0,
                'error' => null,
            ];
        } catch (\Exception $e) {
            Log::error('Pwned password check failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return [
                'is_pwned' => false,
                'count' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * パスワード辞書攻撃対策が有効かチェック
     *
     * @param  string  $settingKey  設定キー（デフォルト: 'pwned_password_check_enabled'）
     */
    public function isPwnedPasswordCheckEnabled(string $settingKey = 'pwned_password_check_enabled'): bool
    {
        // SecuritySettingクラスが存在する場合はそれを使用
        if (class_exists('\App\Models\SecuritySetting')) {
            return filter_var(
                \App\Models\SecuritySetting::get($settingKey, false),
                FILTER_VALIDATE_BOOLEAN
            );
        }

        // フォールバック: config値を使用
        return config('security.pwned_password_check_enabled', false);
    }

    /**
     * パスワードの安全性をチェック（辞書攻撃対策込み）
     *
     * @param  string  $password  チェックするパスワード
     * @param  string  $settingKey  設定キー
     * @return array ['is_safe' => bool, 'message' => string, 'pwned_info' => array]
     */
    public function validatePasswordSafety(string $password, string $settingKey = 'pwned_password_check_enabled'): array
    {
        // 辞書攻撃対策が無効の場合は常に安全
        if (! $this->isPwnedPasswordCheckEnabled($settingKey)) {
            return [
                'is_safe' => true,
                'message' => '',
                'pwned_info' => ['is_pwned' => false, 'count' => 0, 'error' => null],
            ];
        }

        $pwnedInfo = $this->checkPwnedPassword($password);

        // API エラーの場合は警告ログを出すが、パスワードは許可
        if ($pwnedInfo['error']) {
            Log::warning('Pwned password check failed, allowing password', [
                'error' => $pwnedInfo['error'],
            ]);

            return [
                'is_safe' => true,
                'message' => __('validation.pwned_password_api_error'),
                'pwned_info' => $pwnedInfo,
            ];
        }

        // パスワードが漏洩している場合
        if ($pwnedInfo['is_pwned']) {
            return [
                'is_safe' => false,
                'message' => __('validation.pwned_password_found', ['count' => number_format($pwnedInfo['count'])]),
                'pwned_info' => $pwnedInfo,
            ];
        }

        // パスワードは安全
        return [
            'is_safe' => true,
            'message' => '',
            'pwned_info' => $pwnedInfo,
        ];
    }
}
