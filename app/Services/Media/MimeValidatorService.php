<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
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

namespace App\Services\Media;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

/**
 * @internal コア専用。プラグイン/テーマから参照しないこと
 *
 * MIME実体検証サービス
 *
 * ファイルの実体を検証し、拡張子偽装を検出する
 */
class MimeValidatorService
{
    /**
     * 拡張子とMIMEタイプのマッピング
     */
    protected array $extensionMimeMap = [
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'gif' => ['image/gif'],
        'webp' => ['image/webp'],
        'svg' => ['image/svg+xml', 'text/xml', 'application/xml'],
        'mp4' => ['video/mp4'],
        'pdf' => ['application/pdf'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'doc' => ['application/msword'],
        'zip' => ['application/zip', 'application/x-zip-compressed'],
        'txt' => ['text/plain'],
    ];

    /**
     * ファイルマジックバイト（シグネチャ）
     */
    protected array $magicBytes = [
        'image/jpeg' => ["\xFF\xD8\xFF"],
        'image/png' => ["\x89\x50\x4E\x47\x0D\x0A\x1A\x0A"],
        'image/gif' => ['GIF87a', 'GIF89a'],
        'image/webp' => ['RIFF'],
        'application/pdf' => ['%PDF'],
        'application/zip' => ["PK\x03\x04", "PK\x05\x06", "PK\x07\x08"],
        'video/mp4' => ["\x00\x00\x00\x18ftypmp4", "\x00\x00\x00\x1Cftypisom", "\x00\x00\x00\x20ftypisom"],
    ];

    /**
     * ファイルのMIMEタイプを実体から検証
     */
    public function validate(UploadedFile $file): MimeValidationResult
    {
        $result = new MimeValidationResult();

        $extension = strtolower($file->getClientOriginalExtension());
        $clientMime = $file->getClientMimeType();
        $detectedMime = $this->detectMimeType($file);

        $result->setExtension($extension);
        $result->setClientMime($clientMime);
        $result->setDetectedMime($detectedMime);

        // 拡張子に対応するMIMEタイプを取得
        $expectedMimes = $this->extensionMimeMap[$extension] ?? [];

        if (empty($expectedMimes)) {
            $result->addWarning('unknown_extension', __('admin/media.security.mime.unknown_extension', [
                'extension' => $extension,
            ]));

            return $result;
        }

        // 検出されたMIMEタイプが期待値と一致するかチェック
        if (! in_array($detectedMime, $expectedMimes)) {
            $result->addError('mime_mismatch', __('admin/media.security.mime.mime_mismatch', [
                'extension' => $extension,
                'expected' => implode(', ', $expectedMimes),
                'detected' => $detectedMime,
            ]));
        }

        // 画像ファイルの場合、実際にデコードして検証
        if ($this->isImageExtension($extension) && $extension !== 'svg') {
            if (! $this->validateImageContent($file)) {
                $result->addError('invalid_image', __('admin/media.security.mime.invalid_image'));
            }
        }

        // SVGの場合、XMLとして解析可能かチェック
        if ($extension === 'svg') {
            if (! $this->validateSvgContent($file)) {
                $result->addError('invalid_svg', __('admin/media.security.mime.invalid_svg'));
            }
        }

        // マジックバイトチェック
        if (! $this->validateMagicBytes($file, $detectedMime)) {
            // SVGとテキストファイルはマジックバイトがないのでスキップ
            if (! in_array($extension, ['svg', 'txt', 'docx'])) {
                $result->addWarning('magic_bytes_mismatch', __('admin/media.security.mime.magic_bytes_mismatch'));
            }
        }

        return $result;
    }

    /**
     * finfoを使用してMIMEタイプを検出
     */
    protected function detectMimeType(UploadedFile $file): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file->getPathname());

        return $mime ?: 'application/octet-stream';
    }

    /**
     * 画像拡張子かどうか
     */
    protected function isImageExtension(string $extension): bool
    {
        return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']);
    }

    /**
     * 画像コンテンツを検証（実際にデコードして確認）
     */
    protected function validateImageContent(UploadedFile $file): bool
    {
        try {
            $imageInfo = @getimagesize($file->getPathname());

            return $imageInfo !== false;
        } catch (\Exception $e) {
            Log::warning('Image validation failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * SVGコンテンツを検証
     */
    protected function validateSvgContent(UploadedFile $file): bool
    {
        try {
            $content = file_get_contents($file->getPathname());
            if ($content === false) {
                return false;
            }

            libxml_use_internal_errors(true);
            $dom = new \DOMDocument();
            $loaded = $dom->loadXML($content, LIBXML_NONET | LIBXML_NOENT);
            libxml_clear_errors();

            if (! $loaded) {
                return false;
            }

            // ルート要素がsvgであることを確認
            $root = $dom->documentElement;

            return $root && strtolower($root->nodeName) === 'svg';
        } catch (\Exception $e) {
            Log::warning('SVG validation failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * マジックバイトを検証
     */
    protected function validateMagicBytes(UploadedFile $file, string $mimeType): bool
    {
        if (! isset($this->magicBytes[$mimeType])) {
            return true; // マジックバイトが定義されていない場合はスキップ
        }

        $handle = fopen($file->getPathname(), 'rb');
        if ($handle === false) {
            return false;
        }

        $header = fread($handle, 32);
        fclose($handle);

        if ($header === false) {
            return false;
        }

        foreach ($this->magicBytes[$mimeType] as $magic) {
            if (str_starts_with($header, $magic)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 拡張子からMIMEタイプを取得
     */
    public function getMimeTypeForExtension(string $extension): ?string
    {
        $extension = strtolower($extension);

        return isset($this->extensionMimeMap[$extension])
            ? $this->extensionMimeMap[$extension][0]
            : null;
    }
}

/**
 * MIME検証結果クラス
 */
class MimeValidationResult
{
    protected array $errors = [];

    protected array $warnings = [];

    protected string $extension = '';

    protected string $clientMime = '';

    protected string $detectedMime = '';

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

    public function setExtension(string $extension): void
    {
        $this->extension = $extension;
    }

    public function getExtension(): string
    {
        return $this->extension;
    }

    public function setClientMime(string $mime): void
    {
        $this->clientMime = $mime;
    }

    public function getClientMime(): string
    {
        return $this->clientMime;
    }

    public function setDetectedMime(string $mime): void
    {
        $this->detectedMime = $mime;
    }

    public function getDetectedMime(): string
    {
        return $this->detectedMime;
    }

    public function toArray(): array
    {
        return [
            'valid' => $this->isValid(),
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'extension' => $this->extension,
            'client_mime' => $this->clientMime,
            'detected_mime' => $this->detectedMime,
        ];
    }
}
