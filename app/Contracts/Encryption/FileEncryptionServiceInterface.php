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

namespace App\Contracts\Encryption;

use App\DTO\Encryption\EncryptionResultDTO;

/**
 * ファイル暗号化サービスインターフェース
 *
 * バックアップ暗号化、添付ファイル保護等に使用します。
 */
interface FileEncryptionServiceInterface
{
    /**
     * ファイルを暗号化
     *
     * @param  string  $sourcePath  暗号化するファイルのパス
     * @param  string  $destPath  暗号化されたファイルの出力先パス
     * @param  string|null  $key  暗号化キー（null の場合は APP_KEY を使用）
     */
    public function encryptFile(string $sourcePath, string $destPath, ?string $key = null): EncryptionResultDTO;

    /**
     * ファイルを復号
     *
     * @param  string  $sourcePath  暗号化されたファイルのパス
     * @param  string  $destPath  復号されたファイルの出力先パス
     * @param  string|null  $key  復号キー（null の場合は APP_KEY を使用）
     * @return string 復号されたファイルのパス
     *
     * @throws \App\Exceptions\DecryptionException 復号に失敗した場合
     */
    public function decryptFile(string $sourcePath, string $destPath, ?string $key = null): string;

    /**
     * 暗号化アルゴリズムの識別子を取得
     */
    public function getAlgorithm(): string;

    /**
     * 新しい暗号化キーを生成
     */
    public function generateKey(): string;

    /**
     * ファイルが暗号化されているかをヘッダーのマジックバイトで判定
     */
    public function isEncrypted(string $filePath): bool;
}
