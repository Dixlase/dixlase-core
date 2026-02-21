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

namespace App\Enums;

/**
 * @api プラグイン/テーマから使用可能な安定APIです
 *
 * セキュリティ検出時のアクション（汎用）
 *
 * 権限不一致、ポリシー違反などの検出時に取るアクションを定義
 */
enum SecurityAction: int
{
    /**
     * 警告のみ
     * - ログに記録
     * - 管理者に通知（設定による）
     * - 操作は許可
     */
    case Warn = 0;

    /**
     * ブロック
     * - ログに記録
     * - 管理者に通知（設定による）
     * - 操作を拒否
     */
    case Block = 1;

    /**
     * 翻訳キーのベースを取得
     */
    public function translationKeyBase(): string
    {
        return 'admin.settings.security.action_'.$this->toString();
    }

    /**
     * ラベルを取得
     */
    public function label(): string
    {
        return __($this->translationKeyBase());
    }

    /**
     * 説明を取得
     */
    public function description(): string
    {
        return __($this->translationKeyBase().'_desc');
    }

    /**
     * 文字列表現を取得
     */
    public function toString(): string
    {
        return match ($this) {
            self::Warn => 'warn',
            self::Block => 'block',
        };
    }

    /**
     * 文字列からEnumを取得
     */
    public static function fromString(string $value): ?self
    {
        return match ($value) {
            'warn' => self::Warn,
            'block' => self::Block,
            default => null,
        };
    }

    /**
     * CSSクラスを取得（色分け用）
     */
    public function cssClass(): string
    {
        return match ($this) {
            self::Warn => 'text-yellow-600 dark:text-yellow-400',
            self::Block => 'text-red-600 dark:text-red-400',
        };
    }

    /**
     * 色名を取得（radio-card-group用）
     */
    public function colorName(): string
    {
        return match ($this) {
            self::Warn => 'yellow',
            self::Block => 'red',
        };
    }

    /**
     * アイコンクラスを取得
     */
    public function iconClass(): string
    {
        return match ($this) {
            self::Warn => 'fas fa-exclamation-triangle',
            self::Block => 'fas fa-ban',
        };
    }

    /**
     * デフォルト値を取得
     */
    public static function default(): self
    {
        return self::Warn;
    }

    /**
     * すべてのアクションを取得
     */
    public static function all(): array
    {
        return self::cases();
    }

    /**
     * すべての文字列値を取得
     */
    public static function getAllStrings(): array
    {
        return array_map(fn ($case) => $case->toString(), self::cases());
    }

    /**
     * バリデーションルール用の文字列を取得
     */
    public static function validationRule(): string
    {
        return 'in:'.implode(',', self::getAllStrings());
    }
}
