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
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
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
 * File integrity verification service interface
 *
 * Used for hash verification of backup files
 */
interface FileVerificationServiceInterface
{
    /**
     * Generate hash of file
     *
     * @param  string  $filePath  Path to the file to calculate hash for
     * @param  string  $algorithm  Hash algorithm (default: sha256)
     */
    public function hashFile(string $filePath, string $algorithm = 'sha256'): string;

    /**
     * Verify that the file hash matches the expected value
     *
     * @param  string  $filePath  Path to the file to verify
     * @param  string  $expectedHash  Expected hash value
     * @param  string  $algorithm  Hash algorithm (default: sha256)
     */
    public function verifyHash(string $filePath, string $expectedHash, string $algorithm = 'sha256'): bool;

    /**
     * Get list of supported hash algorithms
     *
     * @return string[]
     */
    public function getSupportedAlgorithms(): array;
}
