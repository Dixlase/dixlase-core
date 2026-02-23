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
 * @internal コア専用。プラグイン/テーマから参照しないこと
 *
 * 拡張機能（プラグイン・テーマ）の健全性レベル
 *
 * 健全性レベルは、拡張機能がシステムに与える影響の範囲を示します。
 * 「リスク」ではなく「健全性」という表現を使用することで、
 * 開発者に対してより前向きで建設的なフィードバックを提供します。
 *
 * プリセットモード:
 * - Strict (厳格): 署名必須、権限定義必須、健全性「良好」のみ許可
 * - Balanced (バランス): 署名または信頼済みソース由来ならOK、「注意」まで許可
 * - Development (開発): 未署名や未定義もインストール可（警告表示）
 */
enum ExtensionSecurityLevel: int
{
    /**
     * 良好のみ - 基本機能のみを使用する拡張機能
     */
    case Healthy = 0;

    /**
     * 注意まで許可 - 一部の拡張機能を使用
     */
    case Warning = 1;

    /**
     * 要確認まで許可 - より多くの機能を使用
     */
    case NeedsAttention = 2;

    /**
     * 未確認も許可 - すべての拡張機能を許可
     */
    case NotVerified = 3;

    /**
     * 翻訳キーを取得
     */
    public function translationKey(): string
    {
        return match ($this) {
            self::Healthy => 'admin/settings/security/extensions.security.health_level.healthy',
            self::Warning => 'admin/settings/security/extensions.security.health_level.warning',
            self::NeedsAttention => 'admin/settings/security/extensions.security.health_level.needs_attention',
            self::NotVerified => 'admin/settings/security/extensions.security.health_level.not_verified',
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
     * 短いラベルを取得（Range用）
     */
    public function shortLabel(): string
    {
        return match ($this) {
            self::Healthy => __('admin/settings/security/extensions.security.health_level_short.healthy'),
            self::Warning => __('admin/settings/security/extensions.security.health_level_short.warning'),
            self::NeedsAttention => __('admin/settings/security/extensions.security.health_level_short.needs_attention'),
            self::NotVerified => __('admin/settings/security/extensions.security.health_level_short.not_verified'),
        };
    }

    /**
     * 説明を取得
     */
    public function description(): string
    {
        return match ($this) {
            self::Healthy => __('admin/settings/security/extensions.security.health_level_description.healthy'),
            self::Warning => __('admin/settings/security/extensions.security.health_level_description.warning'),
            self::NeedsAttention => __('admin/settings/security/extensions.security.health_level_description.needs_attention'),
            self::NotVerified => __('admin/settings/security/extensions.security.health_level_description.not_verified'),
        };
    }

    /**
     * CSSクラスを取得（色分け用）
     */
    public function cssClass(): string
    {
        return match ($this) {
            self::Healthy => 'text-green-600 dark:text-green-400',
            self::Warning => 'text-yellow-600 dark:text-yellow-400',
            self::NeedsAttention => 'text-orange-600 dark:text-orange-400',
            self::NotVerified => 'text-gray-600 dark:text-gray-400',
        };
    }

    /**
     * バッジクラスを取得
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Healthy => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
            self::Warning => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
            self::NeedsAttention => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300',
            self::NotVerified => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
        };
    }

    /**
     * 指定された健全性レベルがこのレベル以下かどうかを判定
     */
    public function allows(self $healthLevel): bool
    {
        return $healthLevel->value <= $this->value;
    }

    /**
     * すべてのレベルを取得
     */
    public static function all(): array
    {
        return self::cases();
    }

    /**
     * Range用のラベル配列を取得
     */
    public static function getRangeLabels(): array
    {
        $labels = [];
        foreach (self::cases() as $case) {
            $labels[$case->value] = $case->translationKey();
        }

        return $labels;
    }

    /**
     * Range用のラベル色配列を取得
     * 良好→緑、注意→黄、要確認→オレンジ、未確認→赤
     */
    public static function getRangeLabelColors(): array
    {
        return [
            self::Healthy->value => 'green',
            self::Warning->value => 'yellow',
            self::NeedsAttention->value => 'orange',
            self::NotVerified->value => 'red',
        ];
    }

    /**
     * デフォルト値を取得（バランスモード = Warning）
     */
    public static function default(): self
    {
        return self::Warning;
    }
}
