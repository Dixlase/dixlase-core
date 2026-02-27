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
 * @internal コア専用。プラグイン/テーマから参照しないこと
 *
 * プラグイン・テーマの信頼度レベル
 *
 * 信頼度は「出どころ・供給経路の確からしさ」を表します。
 * 署名鍵の発行元、配布経路、作者の認証などを評価します。
 *
 * Health（健全性）との違い:
 * - Trust: 「誰から来たか」
 * - Health: 「中身が今どうか」
 *
 * 例:
 * - Trust: Official / Health: NeedsAttention → 公式でも改ざん疑い
 * - Trust: Local / Health: Healthy → 自作でも整合性OK
 */
enum PluginTrustLevel: string
{
    /**
     * 公式 - Dixlase公式による配布
     */
    case Official = 'official';

    /**
     * 認証済み - 認証済みパブリッシャーによる配布
     */
    case Verified = 'verified';

    /**
     * パートナー - Dixlaseパートナーによる配布
     */
    case Partner = 'partner';

    /**
     * コミュニティ - 未認証の配布者
     */
    case Community = 'community';

    /**
     * ローカル - 手動インストール/ローカル開発
     */
    case Local = 'local';

    /**
     * 翻訳キーを取得
     */
    public function translationKey(): string
    {
        return 'admin/settings/plugins.trust_level.'.$this->value;
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
        return __($this->translationKey().'_description');
    }

    /**
     * CSSクラスを取得（バッジ用）
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Official => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
            self::Verified => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            self::Partner => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
            self::Community => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
            self::Local => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
        };
    }

    /**
     * アイコンクラスを取得
     */
    public function iconClass(): string
    {
        return match ($this) {
            self::Official => 'fas fa-crown',
            self::Verified => 'fas fa-check-circle',
            self::Partner => 'fas fa-handshake',
            self::Community => 'fas fa-users',
            self::Local => 'fas fa-laptop-code',
        };
    }

    /**
     * 色名を取得
     */
    public function colorName(): string
    {
        return match ($this) {
            self::Official => 'purple',
            self::Verified => 'green',
            self::Partner => 'blue',
            self::Community => 'gray',
            self::Local => 'gray',
        };
    }

    /**
     * 署名タイプから信頼度を判定
     */
    public static function fromSignatureType(?string $signatureType): self
    {
        return match ($signatureType) {
            'official' => self::Official,
            'verified' => self::Verified,
            'partner' => self::Partner,
            'community' => self::Community,
            default => self::Local,
        };
    }

    /**
     * 信頼度の優先順位を取得（高いほど信頼度が高い）
     */
    public function priority(): int
    {
        return match ($this) {
            self::Official => 100,
            self::Verified => 80,
            self::Partner => 70,
            self::Community => 30,
            self::Local => 10,
        };
    }

    /**
     * 本番環境で推奨されるかどうか
     */
    public function isProductionRecommended(): bool
    {
        return match ($this) {
            self::Official, self::Verified, self::Partner => true,
            self::Community, self::Local => false,
        };
    }

    /**
     * すべてのレベルを取得
     */
    public static function all(): array
    {
        return self::cases();
    }

    /**
     * 本番環境で推奨されるレベルのみ取得
     */
    public static function productionRecommended(): array
    {
        return array_filter(self::cases(), fn ($level) => $level->isProductionRecommended());
    }
}
