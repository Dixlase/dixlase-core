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

namespace App\Traits\TwoFa;

use App\Enums\AuthenticationMode;
use App\Enums\TwoFaMethod;
use App\Services\TwoFa\TwoFaPasskeyService;
use App\Traits\DeviceDetectionTrait;

/**
 * @internal コア専用。プラグイン/テーマから参照しないこと
 *
 * 二段階認証の低レベルユーティリティ機能を提供するトレイト
 *
 * コード生成・検証、設定取得、判定ロジックなど、
 * 二段階認証の基本的な機能を提供します。
 */
trait TwoFaUtilityTrait
{
    use DeviceDetectionTrait;

    /**
     * 二段階認証コードを生成してデータベースに保存
     *
     * @param  mixed  $user  ユーザーモデル
     * @param  int  $expireMinutes  有効期限（分）
     * @return string 生成されたコード
     */
    public function generateTwoFaCode($user, ?int $expireMinutes = null): string
    {
        $codeService = app(\App\Services\TwoFa\TwoFaCodeService::class);

        return $codeService->generate($user, $expireMinutes);
    }

    /**
     * 二段階認証コードを検証
     *
     * @param  mixed  $user  ユーザーモデル
     * @param  string  $inputCode  入力されたコード
     * @return bool 検証結果
     */
    public function validateTwoFaCode($user, string $inputCode): bool
    {
        $codeService = app(\App\Services\TwoFa\TwoFaCodeService::class);

        return $codeService->validate($user, $inputCode);
    }

    /**
     * 有効な認証方法を取得
     *
     * @param  string  $settingsKey  設定キー
     * @param  array  $defaultMethods  デフォルトの認証方法
     * @return array 有効な認証方法の配列
     */
    public function getEnabledTwoFaMethods(string $settingsKey = 'enabled_two_fa_methods', ?array $defaultMethods = null): array
    {
        if ($defaultMethods === null) {
            $defaultMethods = [TwoFaMethod::EMAIL->value];
        }

        $settingsValue = $this->getSettingValue($settingsKey, json_encode($defaultMethods));

        // 設定値が文字列でない場合（整数など）の処理
        if (! is_string($settingsValue)) {
            return $defaultMethods;
        }

        $decoded = json_decode($settingsValue, true);

        // JSON デコードが失敗した場合、または配列でない場合はデフォルトを返す
        if (! is_array($decoded)) {
            return $defaultMethods;
        }

        return $decoded;
    }

    /**
     * 二段階認証が必要かどうかを判定
     *
     * @param  mixed  $user  ユーザーモデル
     * @param  int  $forceSetting  システム設定の強制2FA設定
     * @param  array  $enabledMethods  有効な認証方法
     * @return bool 2FAが必要かどうか
     */
    public function requiresTwoFa($user, int $forceSetting, array $enabledMethods): bool
    {
        // 有効な認証方法がない場合は2FAを無効化
        if (empty($enabledMethods)) {
            return false;
        }

        // 現在の設定に基づいて2FAが必要かチェック
        $modeValue = $this->getEffectiveTwoFaMode($user, $forceSetting);
        $mode = AuthenticationMode::tryFrom($modeValue);

        // Passkeyが有効な場合は常に2FAを要求
        if (in_array(TwoFaMethod::PASSKEY->value, $enabledMethods)) {
            return true;
        }

        return match ($mode) {
            AuthenticationMode::Always => true,
            default => false,
        };
    }

    /**
     * 有効な2要素認証モードを取得する
     *
     * @param  mixed  $user  ユーザーモデル
     * @param  int  $forceSetting  システム設定の強制2FA設定
     * @return int 有効な2FAモード
     */
    protected function getEffectiveTwoFaMode($user, int $forceSetting): int
    {
        // システム設定で2FAが無効化されている場合
        if ($forceSetting === AuthenticationMode::Disabled->value) {
            return AuthenticationMode::Disabled->value;
        }

        // システム設定で強制されている場合
        if ($forceSetting === AuthenticationMode::Always->value) {
            return AuthenticationMode::Always->value;
        }

        // プロフィール設定を使用する場合
        if ($forceSetting === AuthenticationMode::UseProfileSetting->value) {
            return $this->checkUserTwoFaSetting($user);
        }

        return AuthenticationMode::Disabled->value;
    }

    /**
     * ユーザーの2FA個人設定をチェック
     *
     * @param  mixed  $user  ユーザーモデル
     * @return int 2FAモード
     */
    protected function checkUserTwoFaSetting($user): int
    {
        $mode = $user->two_fa_mode;

        // null の場合はデフォルトで無効
        if ($mode === null) {
            return AuthenticationMode::Disabled->value;
        }

        return match ($mode) {
            AuthenticationMode::Always => AuthenticationMode::Always->value,
            default => AuthenticationMode::Disabled->value,
        };
    }

    /**
     * 信頼済みデバイスからのアクセスかチェック
     *
     * @param  mixed  $user  ユーザーモデル
     * @return bool 信頼済みデバイスの場合true
     */
    protected function isFromTrustedDevice($user): bool
    {
        $passkeyService = new TwoFaPasskeyService();

        return $passkeyService->isTrustedDevice($user);
    }

    /**
     * 設定値を取得する（継承先で実装）
     *
     * @param  string  $key  設定キー
     * @param  mixed  $default  デフォルト値
     * @return mixed 設定値
     */
    abstract protected function getSettingValue(string $key, $default = null);
}
