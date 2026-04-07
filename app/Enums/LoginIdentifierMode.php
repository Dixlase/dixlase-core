<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
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
 * ログイン識別子モード
 *
 * 管理画面ログインで受け付ける識別子（メールアドレス / アカウント名）を制御する
 */
enum LoginIdentifierMode: int
{
    case EmailOnly = 0;            // メールアドレスのみ
    case EmailOrAccountName = 1;   // メールアドレスまたはアカウント名
    case AccountNameOnly = 2;      // アカウント名のみ

    /**
     * メールアドレスでのログインをサポートするか
     */
    public function supportsEmail(): bool
    {
        return match ($this) {
            self::EmailOnly, self::EmailOrAccountName => true,
            self::AccountNameOnly => false,
        };
    }

    /**
     * アカウント名でのログインをサポートするか
     */
    public function supportsAccountName(): bool
    {
        return match ($this) {
            self::AccountNameOnly, self::EmailOrAccountName => true,
            self::EmailOnly => false,
        };
    }

    /**
     * ラベルを取得
     */
    public function label(): string
    {
        return __($this->translationKey());
    }

    /**
     * 翻訳キーを取得
     */
    public function translationKey(): string
    {
        return match ($this) {
            self::EmailOnly => 'common.login_identifier_mode.email_only',
            self::EmailOrAccountName => 'common.login_identifier_mode.email_or_account_name',
            self::AccountNameOnly => 'common.login_identifier_mode.account_name_only',
        };
    }

    /**
     * 説明文の翻訳キーを取得
     */
    public function descriptionKey(): string
    {
        return match ($this) {
            self::EmailOnly => 'common.login_identifier_mode.email_only_description',
            self::EmailOrAccountName => 'common.login_identifier_mode.email_or_account_name_description',
            self::AccountNameOnly => 'common.login_identifier_mode.account_name_only_description',
        };
    }

    /**
     * アイコンクラスを取得
     */
    public function iconClass(): string
    {
        return match ($this) {
            self::EmailOnly => 'fas fa-envelope',
            self::EmailOrAccountName => 'fas fa-users',
            self::AccountNameOnly => 'fas fa-user',
        };
    }

    /**
     * ラジオカードグループ用のオプション配列を取得
     *
     * @return array<int, array{label: string, description: string, icon: string}>
     */
    public static function radioCardOptions(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[] = [
                'value' => (string) $case->value,
                'label' => $case->label(),
                'description' => __($case->descriptionKey()),
                'icon' => $case->iconClass(),
            ];
        }

        return $options;
    }
}
