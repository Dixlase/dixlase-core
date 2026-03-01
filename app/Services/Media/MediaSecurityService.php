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

namespace App\Services\Media;

use App\Contracts\Repositories\MediaSettingRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * @internal コア専用。プラグイン/テーマから参照しないこと
 *
 * メディアセキュリティサービス
 *
 * メディアファイルのセキュリティ機能を統合管理
 */
class MediaSecurityService
{
    protected SvgSanitizerService $svgSanitizer;

    protected ZipSecurityService $zipSecurity;

    protected MimeValidatorService $mimeValidator;

    protected MediaSettingRepositoryInterface $settingRepository;

    /**
     * ファイルタイプ別のカテゴリ
     */
    protected array $fileTypeCategories = [
        'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'],
        'video' => ['mp4', 'webm', 'mov', 'avi'],
        'document' => ['pdf', 'docx', 'doc', 'txt'],
        'archive' => ['zip'],
    ];

    /**
     * デフォルトのファイルタイプ別サイズ上限（KB）
     */
    protected array $defaultSizeLimits = [
        'image' => 10240,      // 10MB
        'video' => 307200,     // 300MB
        'document' => 30720,   // 30MB
        'archive' => 102400,   // 100MB
    ];

    public function __construct(
        SvgSanitizerService $svgSanitizer,
        ZipSecurityService $zipSecurity,
        MimeValidatorService $mimeValidator,
        MediaSettingRepositoryInterface $settingRepository
    ) {
        $this->svgSanitizer = $svgSanitizer;
        $this->zipSecurity = $zipSecurity;
        $this->mimeValidator = $mimeValidator;
        $this->settingRepository = $settingRepository;
    }

    /**
     * アップロードファイルのセキュリティチェック
     */
    public function validateUpload(UploadedFile $file): MediaSecurityResult
    {
        $result = new MediaSecurityResult();
        $extension = strtolower($file->getClientOriginalExtension());

        // MIME実体検証
        if ($this->isMimeValidationEnabled()) {
            $mimeResult = $this->mimeValidator->validate($file);
            if (! $mimeResult->isValid()) {
                foreach ($mimeResult->getErrors() as $error) {
                    $result->addError($error['code'], $error['message']);
                }
            }
            foreach ($mimeResult->getWarnings() as $warning) {
                $result->addWarning($warning['code'], $warning['message']);
            }
        }

        // ファイルタイプ別サイズチェック
        $category = $this->getFileCategory($extension);
        $sizeLimit = $this->getSizeLimitForCategory($category);
        $fileSizeKb = $file->getSize() / 1024;

        if ($fileSizeKb > $sizeLimit) {
            $result->addError('size_exceeded', __('admin/media.security.size_exceeded', [
                'size' => round($fileSizeKb / 1024, 2).'MB',
                'max' => round($sizeLimit / 1024, 2).'MB',
                'category' => __('admin/media.security.category.'.$category),
            ]));
        }

        // SVGファイルの場合
        if ($extension === 'svg') {
            $svgResult = $this->validateSvg($file);
            foreach ($svgResult['errors'] as $error) {
                $result->addError($error['code'], $error['message']);
            }
            foreach ($svgResult['warnings'] as $warning) {
                $result->addWarning($warning['code'], $warning['message']);
            }
        }

        // ZIPファイルの場合
        if ($extension === 'zip' && $this->isZipSecurityEnabled()) {
            // ZIP設定を適用
            $this->zipSecurity
                ->setMaxCompressionRatio((int) ($this->settingRepository->get('zip_max_compression_ratio') ?? 100))
                ->setMaxFileCount((int) ($this->settingRepository->get('zip_max_file_count') ?? 1000));

            $zipResult = $this->zipSecurity->check($file->getPathname());
            if (! $zipResult->isValid()) {
                foreach ($zipResult->getErrors() as $error) {
                    $result->addError($error['code'], $error['message']);
                }
            }
            foreach ($zipResult->getWarnings() as $warning) {
                $result->addWarning($warning['code'], $warning['message']);
            }
        }

        return $result;
    }

    /**
     * SVGファイルを検証
     */
    protected function validateSvg(UploadedFile $file): array
    {
        $errors = [];
        $warnings = [];

        $content = file_get_contents($file->getPathname());
        if ($content === false) {
            $errors[] = ['code' => 'svg_read_error', 'message' => __('admin/media.security.svg.read_error')];

            return ['errors' => $errors, 'warnings' => $warnings];
        }

        // SVGが安全かチェック
        if (! $this->svgSanitizer->isSafe($content)) {
            if ($this->isSvgSanitizationEnabled()) {
                $warnings[] = ['code' => 'svg_will_sanitize', 'message' => __('admin/media.security.svg.will_sanitize')];
            } else {
                $errors[] = ['code' => 'svg_unsafe', 'message' => __('admin/media.security.svg.unsafe')];
            }
        }

        return ['errors' => $errors, 'warnings' => $warnings];
    }

    /**
     * SVGファイルをサニタイズして保存
     */
    public function sanitizeSvgIfNeeded(string $filePath): bool
    {
        if (! $this->isSvgSanitizationEnabled()) {
            return true;
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if ($extension !== 'svg') {
            return true;
        }

        return $this->svgSanitizer->sanitizeFile($filePath);
    }

    /**
     * ファイルのカテゴリを取得
     */
    public function getFileCategory(string $extension): string
    {
        $extension = strtolower($extension);

        foreach ($this->fileTypeCategories as $category => $extensions) {
            if (in_array($extension, $extensions)) {
                return $category;
            }
        }

        return 'other';
    }

    /**
     * カテゴリ別のサイズ上限を取得（KB）
     */
    public function getSizeLimitForCategory(string $category): int
    {
        $setting = $this->settingRepository->get('max_file_size_'.$category);

        if ($setting !== null) {
            return (int) $setting;
        }

        return $this->defaultSizeLimits[$category] ?? 2048;
    }

    /**
     * SVGサニタイズが有効かどうか
     */
    public function isSvgSanitizationEnabled(): bool
    {
        return (bool) ($this->settingRepository->get('svg_sanitization_enabled') ?? true);
    }

    /**
     * ZIPセキュリティチェックが有効かどうか
     */
    public function isZipSecurityEnabled(): bool
    {
        return (bool) ($this->settingRepository->get('zip_security_enabled') ?? true);
    }

    /**
     * MIME実体検証が有効かどうか
     */
    public function isMimeValidationEnabled(): bool
    {
        return (bool) ($this->settingRepository->get('mime_validation_enabled') ?? true);
    }

    /**
     * セキュアなダウンロードレスポンスを生成
     */
    public function createSecureDownloadResponse(string $filePath, string $fileName, string $mimeType): BinaryFileResponse
    {
        $response = response()->download($filePath, $fileName);

        // セキュリティヘッダーを追加
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Content-Security-Policy', "default-src 'none'");

        // 危険なファイルタイプは強制的にダウンロード
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if ($this->isDangerousExtension($extension)) {
            $response->headers->set('Content-Disposition', 'attachment; filename="'.$fileName.'"');
        }

        return $response;
    }

    /**
     * セキュアなインラインレスポンスを生成（プレビュー用）
     */
    public function createSecureInlineResponse(string $filePath, string $fileName, string $mimeType): BinaryFileResponse
    {
        $response = response()->file($filePath);

        // セキュリティヘッダーを追加
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // SVGの場合は特別なCSPを設定
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if ($extension === 'svg') {
            $response->headers->set('Content-Security-Policy', "default-src 'none'; style-src 'unsafe-inline'");
        }

        return $response;
    }

    /**
     * 危険な拡張子かどうか
     */
    protected function isDangerousExtension(string $extension): bool
    {
        $dangerous = ['svg', 'html', 'htm', 'xml', 'xhtml', 'pdf', 'docx', 'doc', 'zip'];

        return in_array(strtolower($extension), $dangerous);
    }

    /**
     * 設定を取得
     */
    public function getSecuritySettings(): array
    {
        return [
            'svg_sanitization_enabled' => $this->isSvgSanitizationEnabled(),
            'zip_security_enabled' => $this->isZipSecurityEnabled(),
            'mime_validation_enabled' => $this->isMimeValidationEnabled(),
            'max_file_size_image' => $this->getSizeLimitForCategory('image'),
            'max_file_size_video' => $this->getSizeLimitForCategory('video'),
            'max_file_size_document' => $this->getSizeLimitForCategory('document'),
            'max_file_size_archive' => $this->getSizeLimitForCategory('archive'),
            'zip_max_compression_ratio' => (int) ($this->settingRepository->get('zip_max_compression_ratio') ?? 100),
            'zip_max_file_count' => (int) ($this->settingRepository->get('zip_max_file_count') ?? 1000),
        ];
    }
}

/**
 * メディアセキュリティ結果クラス
 */
class MediaSecurityResult
{
    protected array $errors = [];

    protected array $warnings = [];

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

    public function getFirstError(): ?string
    {
        return $this->errors[0]['message'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'valid' => $this->isValid(),
            'errors' => $this->errors,
            'warnings' => $this->warnings,
        ];
    }
}
