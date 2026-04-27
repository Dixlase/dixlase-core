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

namespace App\Contracts\Verification;

/**
 * ファイル整合性検証サービスインターフェース
 *
 * バックアップファイルのハッシュ検証に使用します。
 */
interface FileVerificationServiceInterface
{
    /**
     * ファイルのハッシュを生成
     *
     * @param  string  $filePath  ハッシュを計算するファイルのパス
     * @param  string  $algorithm  ハッシュアルゴリズム（デフォルト: sha256）
     */
    public function hashFile(string $filePath, string $algorithm = 'sha256'): string;

    /**
     * ファイルのハッシュが期待値と一致するか検証
     *
     * @param  string  $filePath  検証するファイルのパス
     * @param  string  $expectedHash  期待されるハッシュ値
     * @param  string  $algorithm  ハッシュアルゴリズム（デフォルト: sha256）
     */
    public function verifyHash(string $filePath, string $expectedHash, string $algorithm = 'sha256'): bool;

    /**
     * サポートされているハッシュアルゴリズムの一覧を取得
     *
     * @return string[]
     */
    public function getSupportedAlgorithms(): array;
}
