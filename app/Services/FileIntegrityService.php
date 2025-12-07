<?php

namespace App\Services;

use App\Models\FileIntegrityAudit;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FileIntegrityService
{
    /**
     * ハッシュアルゴリズム
     */
    protected string $hashAlgo = 'sha256';

    /**
     * ベースラインファイルの保存先
     */
    protected string $baselinePath;

    /**
     * コアファイルの対象パス
     */
    protected array $corePaths = [
        'app',
        'bootstrap',
        'config',
        'routes',
        'public/index.php',
        'artisan',
        'composer.json',
        'composer.lock',
    ];

    /**
     * 除外パターン
     */
    protected array $ignorePatterns = [
        'app/Custom',
        'storage',
        'vendor',
        'node_modules',
        'bootstrap/cache',
        '.git',
        '.env',
        '.env.*',
    ];

    /**
     * 疑わしいファイルパターン（PHPファイルが存在すべきでない場所）
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
     * コアファイルのベースラインを生成
     */
    public function generateCoreBaseline(): array
    {
        $basePath = base_path();
        $files = [];

        foreach ($this->corePaths as $path) {
            $fullPath = $this->normalizePath($basePath . DIRECTORY_SEPARATOR . $path);

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
     * ベースラインをファイルに保存
     */
    public function saveBaseline(array $baseline, string $filename = 'core_hashes.json'): bool
    {
        try {
            if (!File::isDirectory($this->baselinePath)) {
                File::makeDirectory($this->baselinePath, 0755, true);
            }

            $filePath = $this->baselinePath . DIRECTORY_SEPARATOR . $filename;
            File::put($filePath, json_encode($baseline, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            Log::channel('admin_activity')->info('ファイル整合性ベースラインを生成しました', [
                'filename' => $filename,
                'files_count' => count($baseline['files']),
                'version' => $baseline['meta']['app_version'] ?? 'unknown',
            ]);

            return true;
        } catch (\Exception $e) {
            Log::channel('admin_error')->error('ベースライン保存エラー', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * ベースラインを読み込み
     */
    public function loadBaseline(string $filename = 'core_hashes.json'): ?array
    {
        $filePath = $this->baselinePath . DIRECTORY_SEPARATOR . $filename;

        if (!File::exists($filePath)) {
            return null;
        }

        try {
            $content = File::get($filePath);
            return json_decode($content, true);
        } catch (\Exception $e) {
            Log::channel('admin_error')->error('ベースライン読み込みエラー', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * コアファイルをスキャン
     */
    public function scanCore(
        string $trigger = FileIntegrityAudit::TRIGGER_MANUAL,
        string $initiatedByType = FileIntegrityAudit::INITIATED_BY_SYSTEM,
        ?int $initiatedById = null
    ): FileIntegrityAudit {
        $startedAt = now();

        // 監査レコードを作成
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
            // ベースラインを読み込み
            $baseline = $this->loadBaseline();

            if (!$baseline) {
                // ベースラインがない場合は生成して保存
                $baseline = $this->generateCoreBaseline();
                $this->saveBaseline($baseline);

                $audit->update([
                    'status' => FileIntegrityAudit::STATUS_OK,
                    'total_files_scanned' => count($baseline['files']),
                    'finished_at' => now(),
                    'duration_ms' => now()->diffInMilliseconds($startedAt),
                    'summary' => __('command.integrity.baseline_generated'),
                    'baseline_version' => $baseline['meta']['app_version'] ?? null,
                ]);

                return $audit;
            }

            // 現在の状態を取得
            $currentState = $this->generateCoreBaseline();

            // 比較
            $result = $this->compareStates($baseline['files'], $currentState['files']);

            // 疑わしいファイルをチェック
            $suspicious = $this->checkSuspiciousFiles();

            // ステータスを判定
            $status = $this->determineStatus($result, $suspicious);

            // 結果を更新
            $audit->update([
                'status' => $status,
                'total_files_scanned' => count($currentState['files']),
                'changed_files_count' => count($result['changed']),
                'added_files_count' => count($result['added']),
                'removed_files_count' => count($result['removed']),
                'suspicious_files_count' => count($suspicious),
                'finished_at' => now(),
                'duration_ms' => now()->diffInMilliseconds($startedAt),
                'summary' => $this->generateSummary($result, $suspicious, $status),
                'baseline_version' => $baseline['meta']['app_version'] ?? null,
                'result_payload' => [
                    'changed' => $result['changed'],
                    'added' => $result['added'],
                    'removed' => $result['removed'],
                    'suspicious' => $suspicious,
                ],
            ]);

            // 重大な問題があればログに記録
            if ($status === FileIntegrityAudit::STATUS_CRITICAL) {
                Log::channel('admin_error')->critical('ファイル改ざんを検知しました', [
                    'audit_id' => $audit->id,
                    'changed' => count($result['changed']),
                    'added' => count($result['added']),
                    'removed' => count($result['removed']),
                    'suspicious' => count($suspicious),
                ]);
            }

        } catch (\Exception $e) {
            Log::channel('admin_error')->error('ファイル整合性スキャンエラー', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $audit->update([
                'status' => FileIntegrityAudit::STATUS_CRITICAL,
                'finished_at' => now(),
                'duration_ms' => now()->diffInMilliseconds($startedAt),
                'summary' => __('command.integrity.scan_error', ['error' => $e->getMessage()]),
            ]);
        }

        return $audit;
    }

    /**
     * ディレクトリを再帰的にスキャン
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

                // 除外パターンをチェック
                if ($this->shouldIgnore($relativePath)) {
                    continue;
                }

                $files[$relativePath] = hash_file($this->hashAlgo, $fullPath);
            }
        }
    }

    /**
     * 2つの状態を比較
     */
    protected function compareStates(array $baseline, array $current): array
    {
        $changed = [];
        $added = [];
        $removed = [];

        // 変更・削除されたファイルをチェック
        foreach ($baseline as $path => $hash) {
            if (!isset($current[$path])) {
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

        // 追加されたファイルをチェック
        foreach ($current as $path => $hash) {
            if (!isset($baseline[$path])) {
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
     * 疑わしいファイルをチェック
     */
    protected function checkSuspiciousFiles(): array
    {
        $suspicious = [];
        $basePath = base_path();

        foreach ($this->suspiciousLocations as $location) {
            $fullPath = $basePath . DIRECTORY_SEPARATOR . $location;

            if (!is_dir($fullPath)) {
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

        // public直下の未知のPHPファイルをチェック
        $publicPath = public_path();
        $allowedPublicPhp = ['index.php'];

        foreach (glob($publicPath . '/*.php') as $file) {
            $filename = basename($file);
            if (!in_array($filename, $allowedPublicPhp)) {
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
     * ステータスを判定
     */
    protected function determineStatus(array $result, array $suspicious): string
    {
        // 重大な問題
        if (!empty($suspicious)) {
            return FileIntegrityAudit::STATUS_CRITICAL;
        }

        // コアファイルの削除は重大
        foreach ($result['removed'] as $file) {
            if ($this->isCriticalFile($file['path'])) {
                return FileIntegrityAudit::STATUS_CRITICAL;
            }
        }

        // コアファイルの変更は警告
        if (!empty($result['changed']) || !empty($result['removed'])) {
            return FileIntegrityAudit::STATUS_WARNING;
        }

        // 新規ファイルの追加は警告（コア領域内）
        if (!empty($result['added'])) {
            return FileIntegrityAudit::STATUS_WARNING;
        }

        return FileIntegrityAudit::STATUS_OK;
    }

    /**
     * 重要なファイルかどうか
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
     * サマリーを生成
     */
    protected function generateSummary(array $result, array $suspicious, string $status): string
    {
        $parts = [];

        if (!empty($result['changed'])) {
            $parts[] = __('command.integrity.summary_changed', ['count' => count($result['changed'])]);
        }

        if (!empty($result['added'])) {
            $parts[] = __('command.integrity.summary_added', ['count' => count($result['added'])]);
        }

        if (!empty($result['removed'])) {
            $parts[] = __('command.integrity.summary_removed', ['count' => count($result['removed'])]);
        }

        if (!empty($suspicious)) {
            $parts[] = __('command.integrity.summary_suspicious', ['count' => count($suspicious)]);
        }

        if (empty($parts)) {
            return __('command.integrity.summary_ok');
        }

        return implode(', ', $parts);
    }

    /**
     * パスを正規化
     */
    protected function normalizePath(string $path): string
    {
        return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    }

    /**
     * 相対パスを取得
     */
    protected function getRelativePath(string $fullPath, string $basePath): string
    {
        $fullPath = $this->normalizePath($fullPath);
        $basePath = rtrim($this->normalizePath($basePath), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if (str_starts_with($fullPath, $basePath)) {
            return substr($fullPath, strlen($basePath));
        }

        return $fullPath;
    }

    /**
     * 除外すべきパスかどうか
     */
    protected function shouldIgnore(string $path): bool
    {
        foreach ($this->ignorePatterns as $pattern) {
            // 完全一致
            if ($path === $pattern) {
                return true;
            }

            // プレフィックス一致
            if (str_starts_with($path, $pattern . DIRECTORY_SEPARATOR) || str_starts_with($path, $pattern . '/')) {
                return true;
            }

            // ワイルドカードパターン
            if (str_contains($pattern, '*')) {
                $regex = '/^' . str_replace(['*', '/'], ['.*', '\/'], $pattern) . '$/';
                if (preg_match($regex, $path)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * ベースラインが存在するか
     */
    public function hasBaseline(string $filename = 'core_hashes.json'): bool
    {
        $filePath = $this->baselinePath . DIRECTORY_SEPARATOR . $filename;
        return File::exists($filePath);
    }

    /**
     * ベースラインのメタ情報を取得
     */
    public function getBaselineMeta(string $filename = 'core_hashes.json'): ?array
    {
        $baseline = $this->loadBaseline($filename);
        
        if (!$baseline || !isset($baseline['meta'])) {
            return null;
        }

        return array_merge($baseline['meta'], [
            'files' => count($baseline['files'] ?? []),
        ]);
    }

    /**
     * ベースラインを再生成（現在の状態を基準にする）
     */
    public function regenerateBaseline(
        string $trigger = FileIntegrityAudit::TRIGGER_MANUAL,
        string $initiatedByType = FileIntegrityAudit::INITIATED_BY_USER,
        ?int $initiatedById = null
    ): bool {
        $baseline = $this->generateCoreBaseline();
        $result = $this->saveBaseline($baseline);

        if ($result) {
            // 監査ログを記録
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
                'summary' => __('command.integrity.baseline_regenerated'),
            ]);

            Log::channel('admin_activity')->info('ファイル整合性ベースラインを再生成しました', [
                'initiated_by_type' => $initiatedByType,
                'initiated_by_id' => $initiatedById,
                'files_count' => count($baseline['files']),
            ]);
        }

        return $result;
    }
}
