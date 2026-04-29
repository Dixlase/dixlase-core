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
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
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

/**
 * @internal コア専用。プラグイン/テーマから参照しないこと
 *
 * 二段階認証有効化条件チェック機能
 *
 * このTraitは、二段階認証を安全に有効化できるかをチェックする機能を提供します。
 * コアのMemberモデルとユーザープラグインのUserモデルで共有されます。
 *
 * 使用方法:
 * - モデルで使用: canEnableTwoFa(), getTwoFaEnableBlockReasons()
 * - リクエストクラスで使用: isMailServerConfigured()
 */
trait TwoFactorEnableCheck
{
    /**
     * 二段階認証を有効化できるかチェック
     *
     * 安全ルール: 以下のいずれかの条件を満たす必要がある
     * - メールサーバーが設定されている
     * - 少なくとも1つのパスキーが登録されている
     * - 回復コードが生成されている
     */
    public function canEnableTwoFa(): bool
    {
        // メールサーバーが設定されているかチェック
        $mailConfigured = \App\Services\MailServerValidatorService::isMailServerTested();

        // パスキーが登録されているかチェック
        $hasPasskey = $this->twoFaPasskeys()->exists();

        // 回復コードが生成されているかチェック
        $hasRecoveryCode = $this->twoFaRecoveryCodes()->where('used_at', null)->exists();

        return $mailConfigured || $hasPasskey || $hasRecoveryCode;
    }

    /**
     * 二段階認証を有効化できない理由を取得
     *
     * @return array 理由のリスト
     */
    public function getTwoFaEnableBlockReasons(): array
    {
        $reasons = [];

        $mailConfigured = \App\Services\MailServerValidatorService::isMailServerTested();
        $hasPasskey = $this->twoFaPasskeys()->exists();
        $hasRecoveryCode = $this->twoFaRecoveryCodes()->where('used_at', null)->exists();

        if (! $mailConfigured && ! $hasPasskey && ! $hasRecoveryCode) {
            $reasons[] = 'no_backup_method';
        }

        return $reasons;
    }
}
