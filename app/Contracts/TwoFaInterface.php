<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
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

namespace App\Contracts;

use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 二段階認証機能を持つユーザーのインターフェース
 */
interface TwoFaInterface
{
    /**
     * ユーザーIDを取得
     */
    public function getId(): int;

    /**
     * メールアドレスを取得
     */
    public function getEmail(): string;

    /**
     * 表示名を取得
     */
    public function getDisplayName(): string;

    /**
     * アカウント名を取得
     */
    public function getAccountName(): ?string;

    /**
     * 二段階認証モードを取得
     */
    public function getTwoFaMode(): int;

    /**
     * パスキーが有効かどうか
     */
    public function isTwoFaPasskeyEnabled(): bool;

    /**
     * デフォルトの二段階認証方法を取得
     */
    public function getTwoFaDefaultMethod(): int;

    /**
     * パスキーデバイスのリレーション
     */
    public function twoFaPasskeys(): HasMany;

    /**
     * 回復コードのリレーション
     */
    public function twoFaRecoveryCodes(): HasMany;

    /**
     * 二段階認証試行のリレーション
     */
    public function twoFaAttempts(): HasMany;

    /**
     * 二段階認証トークンのリレーション
     */
    public function twoFaTokens(): HasMany;
}
