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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Enums;

/**
 * 拡張機能セキュリティのプリセットモード
 */
enum ExtensionSecurityPreset: string
{
    /**
     * 厳格モード（推奨）
     * - 署名必須
     * - 権限定義必須
     * - 健全性「良好」のみ許可
     */
    case Strict = 'strict';

    /**
     * バランスモード
     * - 署名または信頼済みマーケット由来ならOK
     * - 健全性「注意」まで許可
     */
    case Balanced = 'balanced';

    /**
     * 開発・検証モード
     * - 未署名や未定義もインストール可（警告表示）
     * - 本番環境では選択不可にすることも検討
     */
    case Development = 'development';

    /**
     * カスタムモード
     * - ユーザーが個別に設定
     */
    case Custom = 'custom';

    /**
     * 翻訳キーを取得
     */
    public function translationKey(): string
    {
        return 'admin.settings.security.extension_security.preset.' . $this->value;
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
        return __($this->translationKey() . '_description');
    }

    /**
     * このプリセットのデフォルト設定を取得
     */
    public function getDefaultSettings(): array
    {
        return match ($this) {
            self::Strict => [
                'require_signature' => true,
                'require_permission_definition' => true,
                'allow_undefined_permissions' => false,
                'max_health_level' => ExtensionSecurityLevel::Healthy->value,
                'plugin_max_health_level' => ExtensionSecurityLevel::Healthy->value,
                'theme_max_health_level' => ExtensionSecurityLevel::Healthy->value,
                'allow_logic_themes' => false,
            ],
            self::Balanced => [
                'require_signature' => false,
                'require_permission_definition' => false,
                'allow_undefined_permissions' => true,
                'max_health_level' => ExtensionSecurityLevel::Warning->value,
                'plugin_max_health_level' => ExtensionSecurityLevel::Warning->value,
                'theme_max_health_level' => ExtensionSecurityLevel::NeedsAttention->value,
                'allow_logic_themes' => true,
            ],
            self::Development => [
                'require_signature' => false,
                'require_permission_definition' => false,
                'allow_undefined_permissions' => true,
                'max_health_level' => ExtensionSecurityLevel::NotVerified->value,
                'plugin_max_health_level' => ExtensionSecurityLevel::NotVerified->value,
                'theme_max_health_level' => ExtensionSecurityLevel::NotVerified->value,
                'allow_logic_themes' => true,
            ],
            self::Custom => [
                // カスタムモードはユーザー設定を使用
                'require_signature' => false,
                'require_permission_definition' => false,
                'allow_undefined_permissions' => true,
                'max_health_level' => ExtensionSecurityLevel::Warning->value,
                'plugin_max_health_level' => ExtensionSecurityLevel::Warning->value,
                'theme_max_health_level' => ExtensionSecurityLevel::Warning->value,
                'allow_logic_themes' => true,
            ],
        };
    }

    /**
     * CSSクラスを取得（色分け用）
     */
    public function cssClass(): string
    {
        return match ($this) {
            self::Strict => 'text-green-600 dark:text-green-400',
            self::Balanced => 'text-blue-600 dark:text-blue-400',
            self::Development => 'text-yellow-600 dark:text-yellow-400',
            self::Custom => 'text-purple-600 dark:text-purple-400',
        };
    }

    /**
     * アイコンクラスを取得
     */
    public function iconClass(): string
    {
        return match ($this) {
            self::Strict => 'fas fa-shield-alt',
            self::Balanced => 'fas fa-balance-scale',
            self::Development => 'fas fa-code',
            self::Custom => 'fas fa-sliders-h',
        };
    }

    /**
     * 本番環境で使用可能かどうか
     */
    public function isProductionSafe(): bool
    {
        return match ($this) {
            self::Strict, self::Balanced, self::Custom => true,
            self::Development => false,
        };
    }

    /**
     * すべてのプリセットを取得
     */
    public static function all(): array
    {
        return self::cases();
    }

    /**
     * デフォルト値を取得
     */
    public static function default(): self
    {
        return self::Balanced;
    }

    /**
     * 本番環境で使用可能なプリセットのみ取得
     */
    public static function productionSafe(): array
    {
        return array_filter(self::cases(), fn($preset) => $preset->isProductionSafe());
    }
}
