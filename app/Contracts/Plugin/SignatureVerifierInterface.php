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

namespace App\Contracts\Plugin;

use App\DTO\Plugin\SignatureVerificationResult;

/**
 * 署名検証コントラクト
 *
 * コア側で署名検証の抽象化を提供します。
 * DixlaseDevKit プラグインがインストール済みの場合は Ed25519 ベースの
 * 検証を実行し、未インストール時はスタブ実装が unsigned を返します。
 */
interface SignatureVerifierInterface
{
    /**
     * プラグインの署名を検証する
     *
     * @param  string  $pluginSlug  プラグインのスラッグ（kebab-case）
     * @return SignatureVerificationResult 検証結果
     */
    public function verify(string $pluginSlug): SignatureVerificationResult;

    /**
     * 署名検証が利用可能かどうか
     *
     * DixlaseDevKit プラグインがインストールされていない場合は false を返します。
     */
    public function isAvailable(): bool;
}
