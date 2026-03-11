<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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

namespace App\Enums;

/**
 * @api プラグイン/テーマから使用可能な安定APIです
 *
 * パスキーモード定義
 */
enum PasskeyMode: int
{
    case Disabled = 0;           // 無効
    case Enabled = 1;            // 有効
    case UseProfileSetting = 2;  // プロフィール設定に従う

    /**
     * ラベルを取得
     */
    public function label(): string
    {
        return match ($this) {
            self::Disabled => __('common.passkey_mode.options.disabled'),
            self::Enabled => __('common.passkey_mode.options.enabled'),
            self::UseProfileSetting => __('common.passkey_mode.options.use_profile_setting'),
        };
    }

    /**
     * 説明を取得
     */
    public function description(): string
    {
        return match ($this) {
            self::Disabled => __('common.passkey_mode.descriptions.disabled'),
            self::Enabled => __('common.passkey_mode.descriptions.enabled'),
            self::UseProfileSetting => __('common.passkey_mode.descriptions.use_profile_setting'),
        };
    }

    /**
     * アイコンを取得
     */
    public function icon(): string
    {
        return match ($this) {
            self::Disabled => 'fas fa-ban',
            self::Enabled => 'fas fa-check-circle',
            self::UseProfileSetting => 'fas fa-user-cog',
        };
    }

    /**
     * 全体設定画面用のオプション配列を取得（ラジオカード用）
     */
    public static function getGlobalOptions(): array
    {
        return [
            [
                'value' => (string) self::Disabled->value,
                'label' => self::Disabled->label(),
                'description' => self::Disabled->description(),
                'icon' => self::Disabled->icon(),
            ],
            [
                'value' => (string) self::Enabled->value,
                'label' => self::Enabled->label(),
                'description' => self::Enabled->description(),
                'icon' => self::Enabled->icon(),
            ],
            [
                'value' => (string) self::UseProfileSetting->value,
                'label' => self::UseProfileSetting->label(),
                'description' => self::UseProfileSetting->description(),
                'icon' => self::UseProfileSetting->icon(),
            ],
        ];
    }

    /**
     * パスキーが有効かどうかを判定
     *
     * @param  int|null  $globalSetting  全体設定の値
     * @param  int|null  $userSetting  ユーザー設定の値
     */
    public static function isEnabled(?int $globalSetting, ?int $userSetting = null): bool
    {
        // 全体設定が無効の場合は常に無効
        if ($globalSetting === self::Disabled->value) {
            return false;
        }

        // 全体設定が有効の場合は常に有効
        if ($globalSetting === self::Enabled->value) {
            return true;
        }

        // 全体設定がプロフィール設定に従う場合は、ユーザー設定を確認
        if ($globalSetting === self::UseProfileSetting->value) {
            return $userSetting === self::Enabled->value;
        }

        return false;
    }

    /**
     * プロフィールで設定可能かどうかを判定
     *
     * @param  int|null  $globalSetting  全体設定の値
     */
    public static function isProfileEditable(?int $globalSetting): bool
    {
        // 全体設定が「プロフィール設定に従う」の場合のみ編集可能
        return $globalSetting === self::UseProfileSetting->value;
    }

    /**
     * プロフィールで強制される値を取得（編集不可の場合）
     *
     * @param  int|null  $globalSetting  全体設定の値
     * @return bool|null 強制される値（null=編集可能）
     */
    public static function getForcedProfileValue(?int $globalSetting): ?bool
    {
        if ($globalSetting === self::Disabled->value) {
            return false; // 無効に強制
        }

        if ($globalSetting === self::Enabled->value) {
            return true; // 有効に強制
        }

        return null; // 編集可能
    }
}
