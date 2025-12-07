<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class FileIntegrityAudit extends Model
{
    use HasFactory;

    protected $table = 'file_integrity_audits';

    protected $fillable = [
        'scan_uuid',
        'scope',
        'scope_identifier',
        'trigger',
        'initiated_by_type',
        'initiated_by_id',
        'status',
        'hash_algo',
        'baseline_version',
        'total_files_scanned',
        'changed_files_count',
        'added_files_count',
        'removed_files_count',
        'suspicious_files_count',
        'started_at',
        'finished_at',
        'duration_ms',
        'summary',
        'result_payload',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'result_payload' => 'array',
        'total_files_scanned' => 'integer',
        'changed_files_count' => 'integer',
        'added_files_count' => 'integer',
        'removed_files_count' => 'integer',
        'suspicious_files_count' => 'integer',
        'duration_ms' => 'integer',
    ];

    // スコープ定数
    public const SCOPE_CORE = 'core';
    public const SCOPE_PLUGIN = 'plugin';
    public const SCOPE_THEME = 'theme';
    public const SCOPE_ALL = 'all';

    // トリガー定数
    public const TRIGGER_MANUAL = 'manual';
    public const TRIGGER_SCHEDULE = 'schedule';
    public const TRIGGER_INSTALL = 'install';
    public const TRIGGER_UPDATE = 'update';

    // 実行者タイプ定数
    public const INITIATED_BY_USER = 'user';
    public const INITIATED_BY_CLI = 'cli';
    public const INITIATED_BY_SYSTEM = 'system';

    // ステータス定数
    public const STATUS_OK = 'ok';
    public const STATUS_WARNING = 'warning';
    public const STATUS_CRITICAL = 'critical';

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->scan_uuid)) {
                $model->scan_uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * 実行者（メンバー）とのリレーション
     */
    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'initiated_by_id');
    }

    /**
     * 問題があるかどうか
     */
    public function hasIssues(): bool
    {
        return $this->status !== self::STATUS_OK;
    }

    /**
     * 重大な問題があるかどうか
     */
    public function isCritical(): bool
    {
        return $this->status === self::STATUS_CRITICAL;
    }

    /**
     * 変更されたファイル一覧を取得
     */
    public function getChangedFiles(): array
    {
        return $this->result_payload['changed'] ?? [];
    }

    /**
     * 追加されたファイル一覧を取得
     */
    public function getAddedFiles(): array
    {
        return $this->result_payload['added'] ?? [];
    }

    /**
     * 削除されたファイル一覧を取得
     */
    public function getRemovedFiles(): array
    {
        return $this->result_payload['removed'] ?? [];
    }

    /**
     * 疑わしいファイル一覧を取得
     */
    public function getSuspiciousFiles(): array
    {
        return $this->result_payload['suspicious'] ?? [];
    }

    /**
     * 最新のスキャン結果を取得
     */
    public static function getLatest(?string $scope = null): ?self
    {
        $query = static::query()->orderBy('created_at', 'desc');

        if ($scope) {
            $query->where('scope', $scope);
        }

        return $query->first();
    }

    /**
     * 最新のコアスキャン結果を取得
     */
    public static function getLatestCore(): ?self
    {
        return static::getLatest(self::SCOPE_CORE);
    }

    /**
     * ステータスに応じたCSSクラスを取得
     */
    public function getStatusColorClass(): string
    {
        return match ($this->status) {
            self::STATUS_OK => 'text-green-600 bg-green-100',
            self::STATUS_WARNING => 'text-yellow-600 bg-yellow-100',
            self::STATUS_CRITICAL => 'text-red-600 bg-red-100',
            default => 'text-gray-600 bg-gray-100',
        };
    }

    /**
     * ステータスに応じたアイコンを取得
     */
    public function getStatusIcon(): string
    {
        return match ($this->status) {
            self::STATUS_OK => 'fas fa-check-circle',
            self::STATUS_WARNING => 'fas fa-exclamation-triangle',
            self::STATUS_CRITICAL => 'fas fa-times-circle',
            default => 'fas fa-question-circle',
        };
    }

    /**
     * スコープ別の最新スキャン結果一覧を取得
     */
    public static function getLatestByScopes(): array
    {
        $scopes = [self::SCOPE_CORE, self::SCOPE_PLUGIN, self::SCOPE_THEME];
        $results = [];

        foreach ($scopes as $scope) {
            $results[$scope] = static::getLatest($scope);
        }

        return $results;
    }
}
