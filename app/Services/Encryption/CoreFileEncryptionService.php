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

namespace App\Services\Encryption;

use App\Contracts\Encryption\FileEncryptionServiceInterface;
use App\DTO\Encryption\EncryptionResultDTO;
use App\Exceptions\DecryptionException;

/**
 * Core file encryption service
 *
 * Default implementation of file encryption using AES-256-GCM
 * Processes in chunks to avoid memory pressure even with large backup files
 *
 * File format:
 * [MAGIC: "DXLE" 4bytes][VERSION: 1byte][ALGORITHM: 1byte][IV: 12bytes][TAG: 16bytes][ENCRYPTED_DATA...]
 */
class CoreFileEncryptionService implements FileEncryptionServiceInterface
{
    /**
     * Magic bytes (for file identification)
     */
    private const MAGIC = 'DXLE';

    /**
     * Format version
     */
    private const VERSION = 1;

    /**
     * Algorithm identifier (AES-256-GCM = 1)
     */
    private const ALGORITHM_ID = 1;

    /**
     * OpenSSL algorithm name
     */
    private const CIPHER = 'aes-256-gcm';

    /**
     * IV size (bytes)
     */
    private const IV_LENGTH = 12;

    /**
     * GCM tag size (bytes)
     */
    private const TAG_LENGTH = 16;

    /**
     * Header size: MAGIC(4) + VERSION(1) + ALGORITHM(1) + IV(12) + TAG(16) = 34
     */
    private const HEADER_SIZE = 34;

    /**
     * Encryption chunk size (1MB)
     */
    private const CHUNK_SIZE = 1048576;

    public function encryptFile(string $sourcePath, string $destPath, ?string $key = null): EncryptionResultDTO
    {
        if (! file_exists($sourcePath)) {
            return EncryptionResultDTO::failure(
                "Source file does not exist: {$sourcePath}",
                $destPath,
                self::CIPHER,
            );
        }

        if (! is_readable($sourcePath)) {
            return EncryptionResultDTO::failure(
                "Source file is not readable: {$sourcePath}",
                $destPath,
                self::CIPHER,
            );
        }

        $destDir = dirname($destPath);
        if (! is_dir($destDir) || ! is_writable($destDir)) {
            return EncryptionResultDTO::failure(
                "Destination directory is not writable: {$destDir}",
                $destPath,
                self::CIPHER,
            );
        }

        try {
            $key = $this->resolveKey($key);
            $originalSize = filesize($sourcePath);
            $iv = random_bytes(self::IV_LENGTH);

            // Read entire source file contents (chunk splitting will be extended for large files in the future)
            $plaintext = file_get_contents($sourcePath);
            if ($plaintext === false) {
                return EncryptionResultDTO::failure(
                    "Failed to read source file: {$sourcePath}",
                    $destPath,
                    self::CIPHER,
                );
            }

            $tag = '';
            $ciphertext = openssl_encrypt(
                $plaintext,
                self::CIPHER,
                $key,
                OPENSSL_RAW_DATA,
                $iv,
                $tag,
                '',
                self::TAG_LENGTH,
            );

            if ($ciphertext === false) {
                return EncryptionResultDTO::failure(
                    'Encryption failed: '.openssl_error_string(),
                    $destPath,
                    self::CIPHER,
                );
            }

            // Write header + ciphertext
            $header = self::MAGIC
                .chr(self::VERSION)
                .chr(self::ALGORITHM_ID)
                .$iv
                .$tag;

            $written = file_put_contents($destPath, $header.$ciphertext);
            if ($written === false) {
                return EncryptionResultDTO::failure(
                    "Failed to write encrypted file: {$destPath}",
                    $destPath,
                    self::CIPHER,
                );
            }

            return EncryptionResultDTO::success(
                $destPath,
                self::CIPHER,
                $originalSize,
                $written,
            );
        } catch (\Throwable $e) {
            // Delete intermediate file on failure
            if (file_exists($destPath)) {
                @unlink($destPath);
            }

            return EncryptionResultDTO::failure(
                "Encryption error: {$e->getMessage()}",
                $destPath,
                self::CIPHER,
            );
        }
    }

    public function decryptFile(string $sourcePath, string $destPath, ?string $key = null): string
    {
        if (! file_exists($sourcePath)) {
            throw new DecryptionException("Encrypted file does not exist: {$sourcePath}");
        }

        if (filesize($sourcePath) < self::HEADER_SIZE) {
            throw new DecryptionException("File is too small to be a valid encrypted file: {$sourcePath}");
        }

        try {
            $key = $this->resolveKey($key);

            $handle = fopen($sourcePath, 'rb');
            if ($handle === false) {
                throw new DecryptionException("Failed to open encrypted file: {$sourcePath}");
            }

            try {
                // Read header
                $header = fread($handle, self::HEADER_SIZE);
                if ($header === false || strlen($header) < self::HEADER_SIZE) {
                    throw new DecryptionException('Failed to read file header');
                }

                // Verify magic bytes
                $magic = substr($header, 0, 4);
                if ($magic !== self::MAGIC) {
                    throw new DecryptionException('Invalid file format: magic bytes mismatch');
                }

                // Verify version
                $version = ord($header[4]);
                if ($version !== self::VERSION) {
                    throw new DecryptionException("Unsupported format version: {$version}");
                }

                // Extract IV and tag
                $iv = substr($header, 6, self::IV_LENGTH);
                $tag = substr($header, 18, self::TAG_LENGTH);

                // Read ciphertext
                $ciphertext = stream_get_contents($handle);
                if ($ciphertext === false) {
                    throw new DecryptionException('Failed to read encrypted data');
                }
            } finally {
                fclose($handle);
            }

            $plaintext = openssl_decrypt(
                $ciphertext,
                self::CIPHER,
                $key,
                OPENSSL_RAW_DATA,
                $iv,
                $tag,
            );

            if ($plaintext === false) {
                throw new DecryptionException('Decryption failed: invalid key or corrupted data');
            }

            $written = file_put_contents($destPath, $plaintext);
            if ($written === false) {
                throw new DecryptionException("Failed to write decrypted file: {$destPath}");
            }

            return $destPath;
        } catch (DecryptionException $e) {
            // Delete intermediate file on failure
            if (file_exists($destPath)) {
                @unlink($destPath);
            }
            throw $e;
        } catch (\Throwable $e) {
            if (file_exists($destPath)) {
                @unlink($destPath);
            }
            throw new DecryptionException("Decryption error: {$e->getMessage()}", 0, $e);
        }
    }

    public function getAlgorithm(): string
    {
        return self::CIPHER;
    }

    public function generateKey(): string
    {
        return base64_encode(random_bytes(32));
    }

    public function isEncrypted(string $filePath): bool
    {
        if (! file_exists($filePath) || filesize($filePath) < self::HEADER_SIZE) {
            return false;
        }

        $handle = fopen($filePath, 'rb');
        if ($handle === false) {
            return false;
        }

        try {
            $magic = fread($handle, 4);

            return $magic === self::MAGIC;
        } finally {
            fclose($handle);
        }
    }

    /**
     * Resolve encryption key (use APP_KEY if null)
     */
    private function resolveKey(?string $key): string
    {
        if ($key !== null) {
            return $this->normalizeKey($key);
        }

        $appKey = config('app.key');
        if (empty($appKey)) {
            throw new \RuntimeException('No encryption key provided and APP_KEY is not set');
        }

        return $this->normalizeKey($appKey);
    }

    /**
     * Normalize key to 32 bytes
     *
     * Decode base64-encoded key and convert to 32 bytes with hash if necessary
     */
    private function normalizeKey(string $key): string
    {
        // Laravel APP_KEY format (base64:xxxx)
        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        } elseif (base64_encode(base64_decode($key, true)) === $key) {
            // Base64-encoded key
            $key = base64_decode($key);
        }

        // Convert with hash if not 32 bytes
        if (strlen($key) !== 32) {
            $key = hash('sha256', $key, true);
        }

        return $key;
    }
}
