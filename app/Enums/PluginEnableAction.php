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
 * プラグイン有効化ポリシー
 *
 * 健全性スコアと致命的問題の有無に基づき、
 * プラグイン有効化時のアクションを決定します。
 *
 * 判定基準:
 * - score >= 90 & 致命的なし: Allowed
 * - score >= 70 & 致命的なし: WarningRequired
 * - score >= 50 & 致命的なし: AcknowledgementRequired
 * - score < 50 または致命的あり: Blocked
 */
enum PluginEnableAction: string
{
    /**
     * 問題なし → そのまま有効化
     */
    case Allowed = 'allowed';

    /**
     * 軽微な問題 → 警告表示して続行可能
     */
    case WarningRequired = 'warning';

    /**
     * 重要な問題 → 確認チェック必須
     */
    case AcknowledgementRequired = 'ack';

    /**
     * 致命的な問題 → 有効化不可
     */
    case Blocked = 'blocked';

    /**
     * 翻訳キーを取得
     */
    public function translationKey(): string
    {
        return 'admin/settings/plugins/index.enable_action.'.$this->value;
    }

    /**
     * ラベルを取得
     */
    public function label(): string
    {
        return __($this->translationKey());
    }

    /**
     * 有効化を許可するかどうか
     */
    public function isAllowed(): bool
    {
        return $this !== self::Blocked;
    }

    /**
     * 警告表示が必要かどうか
     */
    public function requiresWarning(): bool
    {
        return $this === self::WarningRequired || $this === self::AcknowledgementRequired;
    }

    /**
     * 確認チェックが必須かどうか
     */
    public function requiresAcknowledgement(): bool
    {
        return $this === self::AcknowledgementRequired;
    }
}
