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

namespace App\Enums;

/**
 * @internal コア専用。プラグイン/テーマから参照しないこと
 *
 * セーフモードレベル
 *
 * システムリカバリーのためのセーフモードレベルを定義する。
 * CSP無効化、プラグイン無効化、テーマ無効化の3段階をサポート。
 */
enum SafeMode: string
{
    /** CSPヘッダー無効化 */
    case Csp = 'csp';

    /** プラグインアセット/ルート無効化 */
    case Plugins = 'plugins';

    /** テーマ無効化（フロント側のみ） */
    case Theme = 'theme';

    /**
     * セッションキーを取得
     */
    public function sessionKey(): string
    {
        return 'safe_mode_'.$this->value;
    }

    /**
     * バナー背景色のTailwindクラスを取得
     */
    public function bannerBgClass(): string
    {
        return match ($this) {
            self::Csp => 'bg-red-600 dark:bg-red-700',
            self::Plugins => 'bg-orange-600 dark:bg-orange-700',
            self::Theme => 'bg-red-600 dark:bg-red-700',
        };
    }

    /**
     * バナーボタン背景色のTailwindクラスを取得
     */
    public function bannerButtonClass(): string
    {
        return match ($this) {
            self::Csp => 'bg-red-800 dark:bg-red-900 hover:bg-red-900 dark:hover:bg-red-950',
            self::Plugins => 'bg-orange-800 dark:bg-orange-900 hover:bg-orange-900 dark:hover:bg-orange-950',
            self::Theme => 'bg-red-800 dark:bg-red-900 hover:bg-red-900 dark:hover:bg-red-950',
        };
    }

    /**
     * バナーリンクテキスト色のTailwindクラスを取得
     */
    public function bannerLinkTextClass(): string
    {
        return match ($this) {
            self::Csp => 'text-red-600 dark:text-red-700',
            self::Plugins => 'text-orange-600 dark:text-orange-700',
            self::Theme => 'text-red-600 dark:text-red-700',
        };
    }

    /**
     * Font Awesomeアイコンクラスを取得
     */
    public function iconClass(): string
    {
        return match ($this) {
            self::Csp => 'fas fa-shield-alt',
            self::Plugins => 'fas fa-puzzle-piece',
            self::Theme => 'fas fa-paint-brush',
        };
    }

    /**
     * 関連する設定ページルート名を取得
     */
    public function settingsRoute(): string
    {
        return match ($this) {
            self::Csp => 'admin.settings.security.csp',
            self::Plugins => 'admin.settings.plugins.index',
            self::Theme => 'admin.settings.themes.index',
        };
    }

    /**
     * 翻訳キーのプレフィックスを取得
     */
    public function translationPrefix(): string
    {
        return 'admin/safe-mode.'.$this->value;
    }

    /**
     * URLパラメータ値からEnumを取得（カンマ区切り対応）
     *
     * @return SafeMode[]
     */
    public static function fromUrlParam(string $param): array
    {
        $modes = [];

        // ?safe=1 の後方互換性
        if ($param === '1') {
            return [self::Csp];
        }

        $parts = array_map('trim', explode(',', $param));

        foreach ($parts as $part) {
            $mode = self::tryFrom($part);
            if ($mode !== null) {
                $modes[] = $mode;
            }
        }

        return array_unique($modes, SORT_REGULAR);
    }
}
