<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

namespace App\Services\Media;

use ZipArchive;

/**
 * @internal For Core use only. Do not reference from plugins/themes
 *
 * ZIP security service
 *
 * Security checks for ZIP files (ZIP bomb protection, compression ratio check, etc.)
 */
class ZipSecurityService
{
    /**
     * Default maximum compression ratio (uncompressed size / compressed size)
     * 100 = allow up to 100x
     */
    protected int $maxCompressionRatio = 100;

    /**
     * Default maximum number of files
     */
    protected int $maxFileCount = 1000;

    /**
     * Default maximum uncompressed size (bytes)
     * 1GB = 1073741824
     */
    protected int $maxUncompressedSize = 1073741824;

    /**
     * Forbidden extensions
     */
    protected array $forbiddenExtensions = [
        'exe', 'bat', 'cmd', 'com', 'msi', 'scr', 'pif',
        'vbs', 'vbe', 'js', 'jse', 'ws', 'wsf', 'wsc', 'wsh',
        'ps1', 'psm1', 'psd1', 'ps1xml', 'psc1', 'psc2',
        'msc', 'msp', 'mst', 'cpl', 'scf', 'lnk', 'inf',
        'reg', 'dll', 'ocx', 'sys', 'drv',
        'php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'phps',
        'asp', 'aspx', 'cer', 'csr', 'jsp', 'jspx',
        'htaccess', 'htpasswd',
    ];

    /**
     * ZIP file security check result
     */
    public function check(string $filePath): ZipCheckResult
    {
        $result = new ZipCheckResult();

        if (! file_exists($filePath)) {
            $result->addError('file_not_found', __('admin/media/security.zip.file_not_found'));

            return $result;
        }

        $zip = new ZipArchive();
        $opened = $zip->open($filePath, ZipArchive::RDONLY);

        if ($opened !== true) {
            $result->addError('invalid_zip', __('admin/media/security.zip.invalid_zip'));

            return $result;
        }

        $compressedSize = filesize($filePath);
        $uncompressedSize = 0;
        $fileCount = $zip->numFiles;

        // File count check
        if ($fileCount > $this->maxFileCount) {
            $result->addError('too_many_files', __('admin/media/security.zip.too_many_files', [
                'count' => $fileCount,
                'max' => $this->maxFileCount,
            ]));
        }

        $result->setFileCount($fileCount);

        // Check each file
        for ($i = 0; $i < $fileCount; $i++) {
            $stat = $zip->statIndex($i);

            if ($stat === false) {
                continue;
            }

            $fileName = $stat['name'];
            $fileSize = $stat['size'];
            $uncompressedSize += $fileSize;

            // Path traversal check
            if ($this->hasPathTraversal($fileName)) {
                $result->addError('path_traversal', __('admin/media/security.zip.path_traversal', [
                    'file' => $fileName,
                ]));
            }

            // Forbidden extension check
            $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            if (in_array($extension, $this->forbiddenExtensions)) {
                $result->addWarning('forbidden_extension', __('admin/media/security.zip.forbidden_extension', [
                    'file' => $fileName,
                    'extension' => $extension,
                ]));
            }

            // Hidden file check
            $baseName = basename($fileName);
            if (str_starts_with($baseName, '.') && $baseName !== '.') {
                $result->addWarning('hidden_file', __('admin/media/security.zip.hidden_file', [
                    'file' => $fileName,
                ]));
            }
        }

        $result->setUncompressedSize($uncompressedSize);
        $result->setCompressedSize($compressedSize);

        // Uncompressed size check
        if ($uncompressedSize > $this->maxUncompressedSize) {
            $result->addError('size_exceeded', __('admin/media/security.zip.size_exceeded', [
                'size' => $this->formatBytes($uncompressedSize),
                'max' => $this->formatBytes($this->maxUncompressedSize),
            ]));
        }

        // Compression ratio check (ZIP bomb protection)
        if ($compressedSize > 0) {
            $ratio = $uncompressedSize / $compressedSize;
            $result->setCompressionRatio($ratio);

            if ($ratio > $this->maxCompressionRatio) {
                $result->addError('compression_bomb', __('admin/media/security.zip.compression_bomb', [
                    'ratio' => round($ratio, 2),
                    'max' => $this->maxCompressionRatio,
                ]));
            }
        }

        $zip->close();

        return $result;
    }

    /**
     * Check for path traversal
     */
    protected function hasPathTraversal(string $path): bool
    {
        // Paths containing ../ or ..\\ are dangerous
        if (str_contains($path, '../') || str_contains($path, '..\\')) {
            return true;
        }

        // Absolute paths are dangerous
        if (str_starts_with($path, '/') || preg_match('/^[a-zA-Z]:/', $path)) {
            return true;
        }

        return false;
    }

    /**
     * Format byte count in human-readable format
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    /**
     * Set maximum compression ratio
     */
    public function setMaxCompressionRatio(int $ratio): self
    {
        $this->maxCompressionRatio = $ratio;

        return $this;
    }

    /**
     * Set maximum number of files
     */
    public function setMaxFileCount(int $count): self
    {
        $this->maxFileCount = $count;

        return $this;
    }

    /**
     * Set maximum extracted size
     */
    public function setMaxUncompressedSize(int $size): self
    {
        $this->maxUncompressedSize = $size;

        return $this;
    }

    /**
     * Add forbidden extension
     */
    public function addForbiddenExtension(string $extension): self
    {
        $this->forbiddenExtensions[] = strtolower($extension);

        return $this;
    }
}

/**
 * ZIP check result class
 */
class ZipCheckResult
{
    protected array $errors = [];

    protected array $warnings = [];

    protected int $fileCount = 0;

    protected int $uncompressedSize = 0;

    protected int $compressedSize = 0;

    protected float $compressionRatio = 0;

    public function addError(string $code, string $message): void
    {
        $this->errors[] = ['code' => $code, 'message' => $message];
    }

    public function addWarning(string $code, string $message): void
    {
        $this->warnings[] = ['code' => $code, 'message' => $message];
    }

    public function hasErrors(): bool
    {
        return ! empty($this->errors);
    }

    public function hasWarnings(): bool
    {
        return ! empty($this->warnings);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getWarnings(): array
    {
        return $this->warnings;
    }

    public function isValid(): bool
    {
        return ! $this->hasErrors();
    }

    public function setFileCount(int $count): void
    {
        $this->fileCount = $count;
    }

    public function getFileCount(): int
    {
        return $this->fileCount;
    }

    public function setUncompressedSize(int $size): void
    {
        $this->uncompressedSize = $size;
    }

    public function getUncompressedSize(): int
    {
        return $this->uncompressedSize;
    }

    public function setCompressedSize(int $size): void
    {
        $this->compressedSize = $size;
    }

    public function getCompressedSize(): int
    {
        return $this->compressedSize;
    }

    public function setCompressionRatio(float $ratio): void
    {
        $this->compressionRatio = $ratio;
    }

    public function getCompressionRatio(): float
    {
        return $this->compressionRatio;
    }

    public function toArray(): array
    {
        return [
            'valid' => $this->isValid(),
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'file_count' => $this->fileCount,
            'compressed_size' => $this->compressedSize,
            'uncompressed_size' => $this->uncompressedSize,
            'compression_ratio' => $this->compressionRatio,
        ];
    }
}
