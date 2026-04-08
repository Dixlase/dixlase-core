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

namespace App\Services\Verification;

use App\Contracts\Verification\FileVerificationServiceInterface;

/**
 * コアファイル検証サービス
 *
 * SHA-256 を使用したファイルハッシュ検証のデフォルト実装です。
 * hash_equals() によるタイミング攻撃防止を含みます。
 */
class CoreFileVerificationService implements FileVerificationServiceInterface
{
    /**
     * サポートされているハッシュアルゴリズム
     */
    private const SUPPORTED_ALGORITHMS = ['sha256', 'sha384', 'sha512'];

    public function hashFile(string $filePath, string $algorithm = 'sha256'): string
    {
        $this->validateAlgorithm($algorithm);

        if (! file_exists($filePath)) {
            throw new \InvalidArgumentException("File does not exist: {$filePath}");
        }

        $hash = hash_file($algorithm, $filePath);
        if ($hash === false) {
            throw new \RuntimeException("Failed to hash file: {$filePath}");
        }

        return $hash;
    }

    public function verifyHash(string $filePath, string $expectedHash, string $algorithm = 'sha256'): bool
    {
        try {
            $actualHash = $this->hashFile($filePath, $algorithm);
        } catch (\InvalidArgumentException|\RuntimeException) {
            return false;
        }

        return hash_equals($expectedHash, $actualHash);
    }

    public function getSupportedAlgorithms(): array
    {
        return self::SUPPORTED_ALGORITHMS;
    }

    /**
     * アルゴリズムがサポートされているか検証
     */
    private function validateAlgorithm(string $algorithm): void
    {
        if (! in_array($algorithm, self::SUPPORTED_ALGORITHMS, true)) {
            throw new \InvalidArgumentException(
                "Unsupported hash algorithm: {$algorithm}. Supported: ".implode(', ', self::SUPPORTED_ALGORITHMS)
            );
        }
    }
}
