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
 * CSP (Content Security Policy) モード
 */
enum CspMode: int
{
    /**
     * 開発モード
     * - 緩やかなポリシー
     * - インラインスクリプト許可
     * - 開発・テスト環境向け
     */
    case Development = 0;

    /**
     * 標準モード（推奨）
     * - バランスの取れたポリシー
     * - 一般的なセキュリティ要件を満たす
     * - 本番環境向け
     */
    case Standard = 1;

    /*
     * 厳格モード（初期バージョンでは未実装）
     * - 最も厳しいポリシー
     * - インラインスクリプト禁止
     * - 高セキュリティ要件向け
     *
    case Strict = 2;
     */

    /**
     * 翻訳キーを取得
     */
    public function translationKey(): string
    {
        return 'admin/settings/security/csp.mode_' . $this->toString();
    }

    /**
     * ラベルを取得
     */
    public function label(): string
    {
        return __($this->translationKey());
    }

    /**
     * 説明を取得
     */
    public function description(): string
    {
        return __($this->translationKey() . '_desc');
    }

    /**
     * 文字列表現を取得
     */
    public function toString(): string
    {
        return match ($this) {
            self::Development => 'development',
            self::Standard => 'standard',
            // self::Strict => 'strict', // 初期バージョンでは未実装
        };
    }

    /**
     * 文字列からEnumを取得
     */
    public static function fromString(string $value): ?self
    {
        return match ($value) {
            'development' => self::Development,
            'standard' => self::Standard,
            // 'strict' => self::Strict, // 初期バージョンでは未実装
            default => null,
        };
    }

    /**
     * 数値からEnumを取得
     */
    public static function fromValue(int|string $value): ?self
    {
        $intValue = (int) $value;
        return match ($intValue) {
            0 => self::Development,
            1 => self::Standard,
            // 2 => self::Strict, // 初期バージョンでは未実装
            default => null,
        };
    }

    /**
     * CSSクラスを取得（色分け用）
     */
    public function cssClass(): string
    {
        return match ($this) {
            self::Development => 'text-yellow-600 dark:text-yellow-400',
            self::Standard => 'text-green-600 dark:text-green-400',
            // self::Strict => 'text-red-600 dark:text-red-400', // 初期バージョンでは未実装
        };
    }

    /**
     * 色名を取得（radio-card-group用）
     */
    public function colorName(): string
    {
        return match ($this) {
            self::Development => 'yellow',
            self::Standard => 'blue',
            // self::Strict => 'red', // 初期バージョンでは未実装
        };
    }

    /**
     * アイコンクラスを取得
     */
    public function iconClass(): string
    {
        return match ($this) {
            self::Development => 'fas fa-code',
            self::Standard => 'fas fa-shield-alt',
            // self::Strict => 'fas fa-lock', // 初期バージョンでは未実装
        };
    }

    /**
     * 本番環境で推奨かどうか
     */
    public function isProductionRecommended(): bool
    {
        return match ($this) {
            self::Development => false,
            self::Standard => true,
            // self::Strict => true, // 初期バージョンでは未実装
        };
    }

    /**
     * デフォルト値を取得
     */
    public static function default(): self
    {
        return self::Standard;
    }

    /**
     * すべてのモードを取得
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
        return array_map(fn($case) => $case->toString(), self::cases());
    }

    /**
     * バリデーションルール用の文字列を取得（数値版）
     */
    public static function validationRule(): string
    {
        return 'in:' . implode(',', self::getAllValues());
    }

    /**
     * すべての数値を取得
     */
    public static function getAllValues(): array
    {
        return array_map(fn($case) => (string) $case->value, self::cases());
    }

    /**
     * 機能リストを取得
     */
    public function features(): array
    {
        $baseKey = $this->translationKey();
        return [
            __($baseKey . '_feature1'),
            __($baseKey . '_feature2'),
            __($baseKey . '_feature3'),
        ];
    }

    /**
     * radio-card-groupコンポーネント用のオプション配列を取得
     */
    public static function getRadioCardOptions(): array
    {
        $options = [];
        foreach (self::cases() as $mode) {
            $option = [
                'value' => (string) $mode->value,
                'label' => $mode->translationKey(),
                'description' => $mode->translationKey() . '_desc',
                'icon' => $mode->iconClass(),
                'color' => $mode->colorName(),
                'features' => $mode->features(),
            ];
            
            // 標準モードには推奨バッジを追加
            if ($mode === self::Standard) {
                $option['badge'] = 'admin/settings/security/csp.recommended';
                $option['badgeColor'] = 'blue';
            }
            
            $options[] = $option;
        }
        return $options;
    }
}
