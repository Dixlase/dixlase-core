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

namespace App\DTO\Encryption;

/**
 * 暗号化結果DTO
 *
 * ファイル暗号化処理の結果を保持する不変データオブジェクトです。
 */
final readonly class EncryptionResultDTO
{
    public function __construct(
        public bool $success,
        public string $outputPath,
        public string $algorithm,
        public int $originalSize,
        public int $encryptedSize,
        public ?string $error = null,
    ) {}

    /**
     * 成功結果を生成
     */
    public static function success(string $outputPath, string $algorithm, int $originalSize, int $encryptedSize): self
    {
        return new self(true, $outputPath, $algorithm, $originalSize, $encryptedSize);
    }

    /**
     * 失敗結果を生成
     */
    public static function failure(string $error, string $outputPath = '', string $algorithm = ''): self
    {
        return new self(false, $outputPath, $algorithm, 0, 0, $error);
    }
}
