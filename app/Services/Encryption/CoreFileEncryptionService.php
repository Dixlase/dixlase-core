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

namespace App\Services\Encryption;

use App\Contracts\Encryption\FileEncryptionServiceInterface;
use App\DTO\Encryption\EncryptionResultDTO;
use App\Exceptions\DecryptionException;

/**
 * コアファイル暗号化サービス
 *
 * AES-256-GCM を使用したファイル暗号化のデフォルト実装です。
 * チャンク単位で処理するため、大きなバックアップファイルでもメモリを圧迫しません。
 *
 * ファイルフォーマット:
 * [MAGIC: "DXLE" 4bytes][VERSION: 1byte][ALGORITHM: 1byte][IV: 12bytes][TAG: 16bytes][ENCRYPTED_DATA...]
 */
class CoreFileEncryptionService implements FileEncryptionServiceInterface
{
    /**
     * マジックバイト（ファイル識別用）
     */
    private const MAGIC = 'DXLE';

    /**
     * フォーマットバージョン
     */
    private const VERSION = 1;

    /**
     * アルゴリズム識別子（AES-256-GCM = 1）
     */
    private const ALGORITHM_ID = 1;

    /**
     * OpenSSL アルゴリズム名
     */
    private const CIPHER = 'aes-256-gcm';

    /**
     * IV サイズ（バイト）
     */
    private const IV_LENGTH = 12;

    /**
     * GCM タグサイズ（バイト）
     */
    private const TAG_LENGTH = 16;

    /**
     * ヘッダーサイズ: MAGIC(4) + VERSION(1) + ALGORITHM(1) + IV(12) + TAG(16) = 34
     */
    private const HEADER_SIZE = 34;

    /**
     * 暗号化チャンクサイズ（1MB）
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

            // ソースファイルの全内容を読み込み（チャンク分割は将来の大容量ファイル対応で拡張）
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

            // ヘッダー + 暗号文を書き込み
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
            // 失敗時に中間ファイルを削除
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
                // ヘッダー読み込み
                $header = fread($handle, self::HEADER_SIZE);
                if ($header === false || strlen($header) < self::HEADER_SIZE) {
                    throw new DecryptionException('Failed to read file header');
                }

                // マジックバイト検証
                $magic = substr($header, 0, 4);
                if ($magic !== self::MAGIC) {
                    throw new DecryptionException('Invalid file format: magic bytes mismatch');
                }

                // バージョン検証
                $version = ord($header[4]);
                if ($version !== self::VERSION) {
                    throw new DecryptionException("Unsupported format version: {$version}");
                }

                // IV とタグを抽出
                $iv = substr($header, 6, self::IV_LENGTH);
                $tag = substr($header, 18, self::TAG_LENGTH);

                // 暗号文を読み込み
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
            // 失敗時に中間ファイルを削除
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
     * 暗号化キーを解決（null の場合は APP_KEY を使用）
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
     * キーを32バイトに正規化
     *
     * Base64エンコードされたキーをデコードし、必要に応じてハッシュで32バイトに変換します。
     */
    private function normalizeKey(string $key): string
    {
        // Laravel の APP_KEY 形式（base64:xxxx）
        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        } elseif (base64_encode(base64_decode($key, true)) === $key) {
            // Base64エンコードされたキー
            $key = base64_decode($key);
        }

        // 32バイトでなければハッシュで変換
        if (strlen($key) !== 32) {
            $key = hash('sha256', $key, true);
        }

        return $key;
    }
}
