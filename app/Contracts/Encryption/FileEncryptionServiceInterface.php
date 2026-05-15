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
 *       (see LICENSE.commercial, or contact info@dixlase.org).
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
 * File encryption service interface
 *
 * Used for backup encryption, attachment protection, etc.
 */
interface FileEncryptionServiceInterface
{
    /**
     * Encrypt a file
     *
     * @param  string  $sourcePath  Path to the file to encrypt
     * @param  string  $destPath  Output path for the encrypted file
     * @param  string|null  $key  Encryption key (uses APP_KEY if null)
     */
    public function encryptFile(string $sourcePath, string $destPath, ?string $key = null): EncryptionResultDTO;

    /**
     * Decrypt a file
     *
     * @param  string  $sourcePath  Path to the encrypted file
     * @param  string  $destPath  Output path for the decrypted file
     * @param  string|null  $key  Decryption key (uses APP_KEY if null)
     * @return string Path to the decrypted file
     *
     * @throws \App\Exceptions\DecryptionException If decryption fails
     */
    public function decryptFile(string $sourcePath, string $destPath, ?string $key = null): string;

    /**
     * Get the encryption algorithm identifier
     */
    public function getAlgorithm(): string;

    /**
     * Generate a new encryption key
     */
    public function generateKey(): string;

    /**
     * Determine if a file is encrypted by checking the magic bytes in the header
     */
    public function isEncrypted(string $filePath): bool;
}
