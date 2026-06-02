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

namespace App\Services;

use App\Contracts\FileIntegrity\FileIntegrityServiceInterface;
use App\DTO\FileIntegrity\BaselineDTO;
use App\DTO\FileIntegrity\FileChangeDTO;
use App\DTO\FileIntegrity\ScanResultDTO;
use App\DTO\FileIntegrity\ScanTargetDTO;
use App\Models\FileIntegrityAudit;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FileIntegrityService implements FileIntegrityServiceInterface
{
    /**
     * Hash algorithm
     */
    protected string $hashAlgo = 'sha256';

    /**
     * Baseline file storage location
     */
    protected string $baselinePath;

    /**
     * Target path for Core files
     */
    protected array $corePaths = [
        'app',
        'bootstrap',
        'config',
        'routes',
        'resources',
        'database/migrations',
        'public/index.php',
        'artisan',
        'composer.json',
        'composer.lock',
    ];

    /**
     * Exclusion patterns
     */
    protected array $ignorePatterns = [
        'custom',
        'storage',
        'vendor',
        'node_modules',
        'bootstrap/cache',
        '.git',
        '.env',
        '.env.*',
        'public/uploads',
        'public/storage',
        'public/hot',
        '*.log',
    ];

    /**
     * Suspicious file patterns (locations where PHP files should not exist)
     */
    protected array $suspiciousLocations = [
        'public/uploads',
        'public/storage',
        'storage/app/public',
    ];

    public function __construct()
    {
        $this->baselinePath = storage_path('app/dixlase/security');
    }

    /**
     * Generate baseline for Core files
     */
    public function generateCoreBaseline(): array
    {
        $basePath = base_path();
        $files = [];

        foreach ($this->corePaths as $path) {
            $fullPath = $this->normalizePath($basePath.DIRECTORY_SEPARATOR.$path);

            if (is_dir($fullPath)) {
                $this->scanDirectory($fullPath, $basePath, $files);
            } elseif (is_file($fullPath)) {
                $relativePath = $this->getRelativePath($fullPath, $basePath);
                $files[$relativePath] = hash_file($this->hashAlgo, $fullPath);
            }
        }

        $baseline = [
            'meta' => [
                'generated_at' => now()->toIso8601String(),
                'app_version' => config('app.version', '1.0.0'),
                'hash_algo' => $this->hashAlgo,
                'paths' => $this->corePaths,
                'ignore_patterns' => $this->ignorePatterns,
            ],
            'files' => $files,
        ];

        return $baseline;
    }

    /**
     * Save baseline to file (array version - internal use)
     */
    public function saveBaselineArray(array $baseline, string $filename = 'core_hashes.json'): bool
    {
        try {
            if (! File::isDirectory($this->baselinePath)) {
                File::makeDirectory($this->baselinePath, 0755, true);
            }

            $filePath = $this->baselinePath.DIRECTORY_SEPARATOR.$filename;
            File::put($filePath, json_encode($baseline, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            Log::channel('admin_activity')->info(__('services/file_integrity_service.baseline_generated'), [
                'filename' => $filename,
                'files_count' => count($baseline['files']),
                'version' => $baseline['meta']['app_version'] ?? 'unknown',
            ]);

            return true;
        } catch (\Exception $e) {
            Log::channel('admin_error')->error(__('services/file_integrity_service.baseline_save_error'), [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Save baseline (Contract compatible)
     *
     * @param  BaselineDTO  $baseline  Baseline
     * @param  string  $filename  File name
     */
    public function saveBaseline(BaselineDTO $baseline, string $filename = 'core_hashes.json'): bool
    {
        try {
            if (! File::isDirectory($this->baselinePath)) {
                File::makeDirectory($this->baselinePath, 0755, true);
            }

            $filePath = $this->baselinePath.DIRECTORY_SEPARATOR.$filename;
            File::put($filePath, json_encode($baseline->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            Log::channel('admin_activity')->info(__('services/file_integrity_service.baseline_generated'), [
                'filename' => $filename,
                'files_count' => $baseline->getFileCount(),
                'version' => $baseline->appVersion,
                'scope' => $baseline->scope,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::channel('admin_error')->error(__('services/file_integrity_service.baseline_save_error'), [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Load baseline (array version - internal use)
     */
    public function loadBaselineArray(string $filename = 'core_hashes.json'): ?array
    {
        $filePath = $this->baselinePath.DIRECTORY_SEPARATOR.$filename;

        if (! File::exists($filePath)) {
            return null;
        }

        try {
            $content = File::get($filePath);

            return json_decode($content, true);
        } catch (\Exception $e) {
            Log::channel('admin_error')->error(__('services/file_integrity_service.baseline_load_error'), [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Load baseline (Contract compatible)
     *
     * @param  string  $filename  File name
     */
    public function loadBaseline(string $filename = 'core_hashes.json'): ?BaselineDTO
    {
        $filePath = $this->baselinePath.DIRECTORY_SEPARATOR.$filename;

        if (! File::exists($filePath)) {
            return null;
        }

        try {
            $content = File::get($filePath);
            $data = json_decode($content, true);

            return BaselineDTO::fromArray($data);
        } catch (\Exception $e) {
            Log::channel('admin_error')->error(__('services/file_integrity_service.baseline_load_error'), [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Scan Core files
     */
    public function scanCore(
        string $trigger = FileIntegrityAudit::TRIGGER_MANUAL,
        string $initiatedByType = FileIntegrityAudit::INITIATED_BY_SYSTEM,
        ?int $initiatedById = null
    ): FileIntegrityAudit {
        $startedAt = now();

        // Create audit record
        $audit = new FileIntegrityAudit([
            'scope' => FileIntegrityAudit::SCOPE_CORE,
            'trigger' => $trigger,
            'initiated_by_type' => $initiatedByType,
            'initiated_by_id' => $initiatedById,
            'hash_algo' => $this->hashAlgo,
            'started_at' => $startedAt,
            'status' => FileIntegrityAudit::STATUS_OK,
        ]);
        $audit->save();

        try {
            // Load baseline
            $baseline = $this->loadBaselineArray();

            if (! $baseline) {
                // Generate and save baseline if it doesn't exist
                $baseline = $this->generateCoreBaseline();
                $this->saveBaselineArray($baseline);

                $audit->update([
                    'status' => FileIntegrityAudit::STATUS_OK,
                    'total_files_scanned' => count($baseline['files']),
                    'finished_at' => now(),
                    'duration_ms' => $startedAt->diffInMilliseconds(now()),
                    'summary' => __('admin/command/integrity.baseline_generated'),
                    'baseline_version' => $baseline['meta']['app_version'] ?? null,
                ]);

                return $audit;
            }

            // Get current state
            $currentState = $this->generateCoreBaseline();

            // Compare
            $result = $this->compareStates($baseline['files'], $currentState['files']);

            // Check for suspicious files
            $suspicious = $this->checkSuspiciousFiles();

            // Determine status
            $status = $this->determineStatus($result, $suspicious);

            // Update results
            $audit->update([
                'status' => $status,
                'total_files_scanned' => count($currentState['files']),
                'changed_files_count' => count($result['changed']),
                'added_files_count' => count($result['added']),
                'removed_files_count' => count($result['removed']),
                'suspicious_files_count' => count($suspicious),
                'finished_at' => now(),
                'duration_ms' => $startedAt->diffInMilliseconds(now()),
                'summary' => $this->generateSummary($result, $suspicious, $status),
                'baseline_version' => $baseline['meta']['app_version'] ?? null,
                'result_payload' => [
                    'changed' => $result['changed'],
                    'added' => $result['added'],
                    'removed' => $result['removed'],
                    'suspicious' => $suspicious,
                ],
            ]);

            // Log if there are critical issues
            if ($status === FileIntegrityAudit::STATUS_CRITICAL) {
                Log::channel('admin_error')->critical(__('services/file_integrity_service.tampering_detected'), [
                    'audit_id' => $audit->id,
                    'changed' => count($result['changed']),
                    'added' => count($result['added']),
                    'removed' => count($result['removed']),
                    'suspicious' => count($suspicious),
                ]);
            }
        } catch (\Exception $e) {
            Log::channel('admin_error')->error(__('services/file_integrity_service.scan_error'), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $audit->update([
                'status' => FileIntegrityAudit::STATUS_CRITICAL,
                'finished_at' => now(),
                'duration_ms' => $startedAt->diffInMilliseconds(now()),
                'summary' => __('admin/command/integrity.scan_error', ['error' => $e->getMessage()]),
            ]);
        }

        return $audit;
    }

    /**
     * Recursively scan directory
     */
    protected function scanDirectory(string $directory, string $basePath, array &$files): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $fullPath = $file->getRealPath();
                $relativePath = $this->getRelativePath($fullPath, $basePath);

                // Check exclusion patterns
                if ($this->shouldIgnore($relativePath)) {
                    continue;
                }

                $files[$relativePath] = hash_file($this->hashAlgo, $fullPath);
            }
        }
    }

    /**
     * Compare two states
     */
    protected function compareStates(array $baseline, array $current): array
    {
        $changed = [];
        $added = [];
        $removed = [];

        // Check modified and deleted files
        foreach ($baseline as $path => $hash) {
            if (! isset($current[$path])) {
                $removed[] = [
                    'path' => $path,
                    'old_hash' => $hash,
                ];
            } elseif ($current[$path] !== $hash) {
                $changed[] = [
                    'path' => $path,
                    'old_hash' => $hash,
                    'new_hash' => $current[$path],
                ];
            }
        }

        // Check added files
        foreach ($current as $path => $hash) {
            if (! isset($baseline[$path])) {
                $added[] = [
                    'path' => $path,
                    'new_hash' => $hash,
                ];
            }
        }

        return [
            'changed' => $changed,
            'added' => $added,
            'removed' => $removed,
        ];
    }

    /**
     * Check for suspicious files
     */
    protected function checkSuspiciousFiles(): array
    {
        $suspicious = [];
        $basePath = base_path();

        foreach ($this->suspiciousLocations as $location) {
            $fullPath = $basePath.DIRECTORY_SEPARATOR.$location;

            if (! is_dir($fullPath)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($fullPath, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && strtolower($file->getExtension()) === 'php') {
                    $relativePath = $this->getRelativePath($file->getRealPath(), $basePath);
                    $suspicious[] = [
                        'path' => $relativePath,
                        'reason' => 'php_in_uploads',
                        'hash' => hash_file($this->hashAlgo, $file->getRealPath()),
                    ];
                }
            }
        }

        // Check for unknown PHP files directly under public
        $publicPath = public_path();
        // setup-required.php is the friendly first-run page that takes
        // over when vendor/autoload.php is missing — see public/index.php
        // for the dispatch. It's a known, shipped-with-core entry point,
        // so it must not be flagged as "unknown_php_in_public".
        $allowedPublicPhp = ['index.php', 'setup-required.php'];

        foreach (glob($publicPath.'/*.php') as $file) {
            $filename = basename($file);
            if (! in_array($filename, $allowedPublicPhp)) {
                $relativePath = $this->getRelativePath($file, $basePath);
                $suspicious[] = [
                    'path' => $relativePath,
                    'reason' => 'unknown_php_in_public',
                    'hash' => hash_file($this->hashAlgo, $file),
                ];
            }
        }

        return $suspicious;
    }

    /**
     * Determine status
     */
    protected function determineStatus(array $result, array $suspicious): string
    {
        // Critical issue
        if (! empty($suspicious)) {
            return FileIntegrityAudit::STATUS_CRITICAL;
        }

        // Deletion of Core files is critical
        foreach ($result['removed'] as $file) {
            if ($this->isCriticalFile($file['path'])) {
                return FileIntegrityAudit::STATUS_CRITICAL;
            }
        }

        // Modification of Core files is a warning
        if (! empty($result['changed']) || ! empty($result['removed'])) {
            return FileIntegrityAudit::STATUS_WARNING;
        }

        // Addition of new files is a warning (within Core area)
        if (! empty($result['added'])) {
            return FileIntegrityAudit::STATUS_WARNING;
        }

        return FileIntegrityAudit::STATUS_OK;
    }

    /**
     * Whether it is a critical file
     */
    protected function isCriticalFile(string $path): bool
    {
        $criticalFiles = [
            'public/index.php',
            'artisan',
            'bootstrap/app.php',
            'composer.lock',
        ];

        return in_array($path, $criticalFiles);
    }

    /**
     * Generate summary
     */
    protected function generateSummary(array $result, array $suspicious, string $status): string
    {
        $parts = [];

        if (! empty($result['changed'])) {
            $parts[] = __('admin/command/integrity.summary_changed', ['count' => count($result['changed'])]);
        }

        if (! empty($result['added'])) {
            $parts[] = __('admin/command/integrity.summary_added', ['count' => count($result['added'])]);
        }

        if (! empty($result['removed'])) {
            $parts[] = __('admin/command/integrity.summary_removed', ['count' => count($result['removed'])]);
        }

        if (! empty($suspicious)) {
            $parts[] = __('admin/command/integrity.summary_suspicious', ['count' => count($suspicious)]);
        }

        if (empty($parts)) {
            return __('admin/command/integrity.summary_ok');
        }

        return implode(', ', $parts);
    }

    /**
     * Normalize path
     */
    protected function normalizePath(string $path): string
    {
        return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    }

    /**
     * Get relative path
     */
    protected function getRelativePath(string $fullPath, string $basePath): string
    {
        $fullPath = $this->normalizePath($fullPath);
        $basePath = rtrim($this->normalizePath($basePath), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        if (str_starts_with($fullPath, $basePath)) {
            return substr($fullPath, strlen($basePath));
        }

        return $fullPath;
    }

    /**
     * Whether the path should be excluded
     */
    protected function shouldIgnore(string $path): bool
    {
        foreach ($this->ignorePatterns as $pattern) {
            // Exact match
            if ($path === $pattern) {
                return true;
            }

            // Prefix match
            if (str_starts_with($path, $pattern.DIRECTORY_SEPARATOR) || str_starts_with($path, $pattern.'/')) {
                return true;
            }

            // Wildcard pattern
            if (str_contains($pattern, '*')) {
                $regex = '/^'.str_replace(['*', '/'], ['.*', '\/'], $pattern).'$/';
                if (preg_match($regex, $path)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if baseline exists
     */
    public function hasBaseline(string $filename = 'core_hashes.json'): bool
    {
        $filePath = $this->baselinePath.DIRECTORY_SEPARATOR.$filename;

        return File::exists($filePath);
    }

    /**
     * Get baseline metadata
     */
    public function getBaselineMeta(string $filename = 'core_hashes.json'): ?array
    {
        $baseline = $this->loadBaselineArray($filename);

        if (! $baseline || ! isset($baseline['meta'])) {
            return null;
        }

        return array_merge($baseline['meta'], [
            'version' => $baseline['meta']['app_version'] ?? 'N/A',
            'files_count' => count($baseline['files'] ?? []),
        ]);
    }

    /**
     * Regenerate baseline (based on current state)
     *
     * @deprecated New code should use regenerateBaselineWithTarget() instead.
     */
    public function regenerateBaselineCore(
        string $trigger = FileIntegrityAudit::TRIGGER_MANUAL,
        string $initiatedByType = FileIntegrityAudit::INITIATED_BY_USER,
        ?int $initiatedById = null
    ): bool {
        $baseline = $this->generateCoreBaseline();
        $result = $this->saveBaselineArray($baseline);

        if ($result) {
            // Record audit log
            FileIntegrityAudit::create([
                'scope' => FileIntegrityAudit::SCOPE_CORE,
                'trigger' => $trigger,
                'initiated_by_type' => $initiatedByType,
                'initiated_by_id' => $initiatedById,
                'status' => FileIntegrityAudit::STATUS_OK,
                'hash_algo' => $this->hashAlgo,
                'baseline_version' => $baseline['meta']['app_version'] ?? null,
                'total_files_scanned' => count($baseline['files']),
                'started_at' => now(),
                'finished_at' => now(),
                'duration_ms' => 0,
                'summary' => __('admin/command/integrity.baseline_regenerated'),
            ]);

            Log::channel('admin_activity')->info(__('services/file_integrity_service.baseline_regenerated'), [
                'initiated_by_type' => $initiatedByType,
                'initiated_by_id' => $initiatedById,
                'files_count' => count($baseline['files']),
            ]);
        }

        return $result;
    }

    // ========================================
    // Contract-compatible method (DTO version)
    // ========================================

    /**
     * Generate baseline (Contract-compatible)
     *
     * @param  ScanTargetDTO  $target  Scan target
     */
    public function generateBaseline(ScanTargetDTO $target): BaselineDTO
    {
        $basePath = base_path();
        $files = [];
        $paths = ! empty($target->paths) ? $target->paths : $this->corePaths;
        $ignorePatterns = ! empty($target->ignorePatterns) ? $target->ignorePatterns : $this->ignorePatterns;

        foreach ($paths as $path) {
            $fullPath = $this->normalizePath($basePath.DIRECTORY_SEPARATOR.$path);

            if (is_dir($fullPath)) {
                $this->scanDirectoryWithPatterns($fullPath, $basePath, $files, $ignorePatterns);
            } elseif (is_file($fullPath)) {
                $relativePath = $this->getRelativePath($fullPath, $basePath);
                $files[$relativePath] = hash_file($target->hashAlgo, $fullPath);
            }
        }

        return new BaselineDTO(
            generatedAt: now()->toIso8601String(),
            appVersion: config('app.version', '1.0.0'),
            hashAlgo: $target->hashAlgo,
            scope: $target->scope,
            identifier: $target->identifier,
            paths: $paths,
            ignorePatterns: $ignorePatterns,
            files: $files,
        );
    }

    /**
     * Execute file integrity scan (Contract-compatible)
     *
     * @param  ScanTargetDTO  $target  Scan target
     * @param  string  $trigger  Trigger
     * @param  string  $initiatedByType  Executor type
     * @param  int|null  $initiatedById  Executor ID
     */
    public function scan(
        ScanTargetDTO $target,
        string $trigger = 'manual',
        string $initiatedByType = 'system',
        ?int $initiatedById = null
    ): ScanResultDTO {
        $startedAt = now();
        $scanId = Str::uuid()->toString();
        $filename = $target->getBaselineFilename();

        // Create audit record
        $audit = new FileIntegrityAudit([
            'scope' => $target->scope,
            'scope_identifier' => $target->identifier,
            'trigger' => $trigger,
            'initiated_by_type' => $initiatedByType,
            'initiated_by_id' => $initiatedById,
            'hash_algo' => $target->hashAlgo,
            'started_at' => $startedAt,
            'status' => FileIntegrityAudit::STATUS_OK,
        ]);
        $audit->save();

        try {
            // Load baseline
            $baseline = $this->loadBaseline($filename);

            if (! $baseline) {
                // Generate and save baseline if it doesn't exist
                $baseline = $this->generateBaseline($target);
                $this->saveBaseline($baseline, $filename);

                $audit->update([
                    'status' => FileIntegrityAudit::STATUS_OK,
                    'total_files_scanned' => $baseline->getFileCount(),
                    'finished_at' => now(),
                    'duration_ms' => $startedAt->diffInMilliseconds(now()),
                    'summary' => __('admin/command/integrity.baseline_generated'),
                    'baseline_version' => $baseline->appVersion,
                ]);

                return new ScanResultDTO(
                    id: $scanId,
                    scope: $target->scope,
                    identifier: $target->identifier,
                    status: ScanResultDTO::STATUS_OK,
                    trigger: $trigger,
                    initiatedByType: $initiatedByType,
                    initiatedById: $initiatedById,
                    hashAlgo: $target->hashAlgo,
                    baselineVersion: $baseline->appVersion,
                    totalFilesScanned: $baseline->getFileCount(),
                    changedFiles: [],
                    addedFiles: [],
                    removedFiles: [],
                    suspiciousFiles: [],
                    startedAt: $startedAt->toIso8601String(),
                    finishedAt: now()->toIso8601String(),
                    durationMs: $startedAt->diffInMilliseconds(now()),
                    summary: __('admin/command/integrity.baseline_generated'),
                );
            }

            // Get current state
            $currentBaseline = $this->generateBaseline($target);

            // Compare and convert to DTO
            $changedFiles = [];
            $addedFiles = [];
            $removedFiles = [];

            // Check modified and deleted files
            foreach ($baseline->files as $path => $hash) {
                if (! isset($currentBaseline->files[$path])) {
                    $removedFiles[] = FileChangeDTO::removed($path, $hash);
                } elseif ($currentBaseline->files[$path] !== $hash) {
                    $changedFiles[] = FileChangeDTO::changed($path, $hash, $currentBaseline->files[$path]);
                }
            }

            // Check added files
            foreach ($currentBaseline->files as $path => $hash) {
                if (! $baseline->hasFile($path)) {
                    $addedFiles[] = FileChangeDTO::added($path, $hash);
                }
            }

            // Check for suspicious files
            $suspiciousFiles = $this->checkSuspiciousFilesDTO();

            // Determine status
            $status = $this->determineStatusDTO($changedFiles, $addedFiles, $removedFiles, $suspiciousFiles);

            // Generate summary
            $summary = $this->generateSummaryDTO($changedFiles, $addedFiles, $removedFiles, $suspiciousFiles, $status);

            // Update results
            $audit->update([
                'status' => $status,
                'total_files_scanned' => $currentBaseline->getFileCount(),
                'changed_files_count' => count($changedFiles),
                'added_files_count' => count($addedFiles),
                'removed_files_count' => count($removedFiles),
                'suspicious_files_count' => count($suspiciousFiles),
                'finished_at' => now(),
                'duration_ms' => $startedAt->diffInMilliseconds(now()),
                'summary' => $summary,
                'baseline_version' => $baseline->appVersion,
                'result_payload' => [
                    'changed' => array_map(fn ($f) => $f->toArray(), $changedFiles),
                    'added' => array_map(fn ($f) => $f->toArray(), $addedFiles),
                    'removed' => array_map(fn ($f) => $f->toArray(), $removedFiles),
                    'suspicious' => array_map(fn ($f) => $f->toArray(), $suspiciousFiles),
                ],
            ]);

            // Log if there are critical issues
            if ($status === ScanResultDTO::STATUS_CRITICAL) {
                Log::channel('admin_error')->critical(__('services/file_integrity_service.tampering_detected'), [
                    'audit_id' => $audit->id,
                    'scope' => $target->scope,
                    'identifier' => $target->identifier,
                    'changed' => count($changedFiles),
                    'added' => count($addedFiles),
                    'removed' => count($removedFiles),
                    'suspicious' => count($suspiciousFiles),
                ]);
            }

            return new ScanResultDTO(
                id: $scanId,
                scope: $target->scope,
                identifier: $target->identifier,
                status: $status,
                trigger: $trigger,
                initiatedByType: $initiatedByType,
                initiatedById: $initiatedById,
                hashAlgo: $target->hashAlgo,
                baselineVersion: $baseline->appVersion,
                totalFilesScanned: $currentBaseline->getFileCount(),
                changedFiles: $changedFiles,
                addedFiles: $addedFiles,
                removedFiles: $removedFiles,
                suspiciousFiles: $suspiciousFiles,
                startedAt: $startedAt->toIso8601String(),
                finishedAt: now()->toIso8601String(),
                durationMs: $startedAt->diffInMilliseconds(now()),
                summary: $summary,
            );
        } catch (\Exception $e) {
            Log::channel('admin_error')->error(__('services/file_integrity_service.scan_error'), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $audit->update([
                'status' => FileIntegrityAudit::STATUS_CRITICAL,
                'finished_at' => now(),
                'duration_ms' => $startedAt->diffInMilliseconds(now()),
                'summary' => __('admin/command/integrity.scan_error', ['error' => $e->getMessage()]),
            ]);

            return new ScanResultDTO(
                id: $scanId,
                scope: $target->scope,
                identifier: $target->identifier,
                status: ScanResultDTO::STATUS_CRITICAL,
                trigger: $trigger,
                initiatedByType: $initiatedByType,
                initiatedById: $initiatedById,
                hashAlgo: $target->hashAlgo,
                baselineVersion: null,
                totalFilesScanned: 0,
                changedFiles: [],
                addedFiles: [],
                removedFiles: [],
                suspiciousFiles: [],
                startedAt: $startedAt->toIso8601String(),
                finishedAt: now()->toIso8601String(),
                durationMs: $startedAt->diffInMilliseconds(now()),
                summary: __('admin/command/integrity.scan_error', ['error' => $e->getMessage()]),
            );
        }
    }

    /**
     * Regenerate baseline (Contract-compatible)
     *
     * @param  ScanTargetDTO  $target  Scan target
     * @param  string  $trigger  Trigger
     * @param  string  $initiatedByType  Executor type
     * @param  int|null  $initiatedById  Executor ID
     */
    public function regenerateBaseline(
        ScanTargetDTO $target,
        string $trigger = 'manual',
        string $initiatedByType = 'user',
        ?int $initiatedById = null
    ): bool {
        $baseline = $this->generateBaseline($target);
        $filename = $target->getBaselineFilename();
        $result = $this->saveBaseline($baseline, $filename);

        if ($result) {
            // Record audit log
            FileIntegrityAudit::create([
                'scope' => $target->scope,
                'scope_identifier' => $target->identifier,
                'trigger' => $trigger,
                'initiated_by_type' => $initiatedByType,
                'initiated_by_id' => $initiatedById,
                'status' => FileIntegrityAudit::STATUS_OK,
                'hash_algo' => $target->hashAlgo,
                'baseline_version' => $baseline->appVersion,
                'total_files_scanned' => $baseline->getFileCount(),
                'started_at' => now(),
                'finished_at' => now(),
                'duration_ms' => 0,
                'summary' => __('admin/command/integrity.baseline_regenerated'),
            ]);

            Log::channel('admin_activity')->info(__('services/file_integrity_service.baseline_regenerated'), [
                'scope' => $target->scope,
                'identifier' => $target->identifier,
                'initiated_by_type' => $initiatedByType,
                'initiated_by_id' => $initiatedById,
                'files_count' => $baseline->getFileCount(),
            ]);
        }

        return $result;
    }

    /**
     * Recursively scan directory (pattern-specified version)
     */
    protected function scanDirectoryWithPatterns(string $directory, string $basePath, array &$files, array $ignorePatterns): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $fullPath = $file->getRealPath();
                $relativePath = $this->getRelativePath($fullPath, $basePath);

                // Check exclusion patterns
                if ($this->shouldIgnoreWithPatterns($relativePath, $ignorePatterns)) {
                    continue;
                }

                $files[$relativePath] = hash_file($this->hashAlgo, $fullPath);
            }
        }
    }

    /**
     * Check if path should be excluded (pattern-specified version)
     */
    protected function shouldIgnoreWithPatterns(string $path, array $ignorePatterns): bool
    {
        foreach ($ignorePatterns as $pattern) {
            if ($path === $pattern) {
                return true;
            }

            if (str_starts_with($path, $pattern.DIRECTORY_SEPARATOR) || str_starts_with($path, $pattern.'/')) {
                return true;
            }

            if (str_contains($pattern, '*')) {
                $regex = '/^'.str_replace(['*', '/'], ['.*', '\/'], $pattern).'$/';
                if (preg_match($regex, $path)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check suspicious files (DTO version)
     *
     * @return array<FileChangeDTO>
     */
    protected function checkSuspiciousFilesDTO(): array
    {
        $suspicious = [];
        $basePath = base_path();

        foreach ($this->suspiciousLocations as $location) {
            $fullPath = $basePath.DIRECTORY_SEPARATOR.$location;

            if (! is_dir($fullPath)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($fullPath, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && strtolower($file->getExtension()) === 'php') {
                    $relativePath = $this->getRelativePath($file->getRealPath(), $basePath);
                    $suspicious[] = FileChangeDTO::suspicious(
                        $relativePath,
                        hash_file($this->hashAlgo, $file->getRealPath()),
                        'php_in_uploads'
                    );
                }
            }
        }

        // Check for unknown PHP files directly under public
        $publicPath = public_path();
        // setup-required.php is the friendly first-run page that takes
        // over when vendor/autoload.php is missing — see public/index.php
        // for the dispatch. It's a known, shipped-with-core entry point,
        // so it must not be flagged as "unknown_php_in_public".
        $allowedPublicPhp = ['index.php', 'setup-required.php'];

        foreach (glob($publicPath.'/*.php') as $file) {
            $filename = basename($file);
            if (! in_array($filename, $allowedPublicPhp)) {
                $relativePath = $this->getRelativePath($file, $basePath);
                $suspicious[] = FileChangeDTO::suspicious(
                    $relativePath,
                    hash_file($this->hashAlgo, $file),
                    'unknown_php_in_public'
                );
            }
        }

        return $suspicious;
    }

    /**
     * Determine status (DTO version)
     *
     * @param  array<FileChangeDTO>  $changed
     * @param  array<FileChangeDTO>  $added
     * @param  array<FileChangeDTO>  $removed
     * @param  array<FileChangeDTO>  $suspicious
     */
    protected function determineStatusDTO(array $changed, array $added, array $removed, array $suspicious): string
    {
        if (! empty($suspicious)) {
            return ScanResultDTO::STATUS_CRITICAL;
        }

        foreach ($removed as $file) {
            if ($this->isCriticalFile($file->path)) {
                return ScanResultDTO::STATUS_CRITICAL;
            }
        }

        if (! empty($changed) || ! empty($removed)) {
            return ScanResultDTO::STATUS_WARNING;
        }

        if (! empty($added)) {
            return ScanResultDTO::STATUS_WARNING;
        }

        return ScanResultDTO::STATUS_OK;
    }

    /**
     * Generate summary (DTO version)
     */
    protected function generateSummaryDTO(array $changed, array $added, array $removed, array $suspicious, string $status): string
    {
        $parts = [];

        if (! empty($changed)) {
            $parts[] = __('admin/command/integrity.summary_changed', ['count' => count($changed)]);
        }

        if (! empty($added)) {
            $parts[] = __('admin/command/integrity.summary_added', ['count' => count($added)]);
        }

        if (! empty($removed)) {
            $parts[] = __('admin/command/integrity.summary_removed', ['count' => count($removed)]);
        }

        if (! empty($suspicious)) {
            $parts[] = __('admin/command/integrity.summary_suspicious', ['count' => count($suspicious)]);
        }

        if (empty($parts)) {
            return __('admin/command/integrity.summary_ok');
        }

        return implode(', ', $parts);
    }
}
