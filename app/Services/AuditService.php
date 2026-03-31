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

namespace App\Services;

use App\Enums\OperationRiskLevel;
use App\Events\AuditLogCreated;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * @internal コア専用。プラグイン/テーマから参照しないこと
 *
 * 監査ログサービス
 *
 * 統一APIを提供し、プラグインからも利用可能
 */
class AuditService
{
    /**
     * 現在のリクエストID（1リクエスト内で共通）
     */
    protected ?string $requestId = null;

    /**
     * 現在のプラグインコンテキスト
     */
    protected ?string $currentPlugin = null;

    protected ?string $currentPluginVersion = null;

    /**
     * なりすまし操作者ID
     */
    protected ?int $impersonatedById = null;

    /**
     * ファイルログを有効にするか
     */
    protected bool $fileLoggingEnabled = true;

    /**
     * 操作元チャネル (web, api, cli, scheduler, ai_plugin, webhook, queue)
     */
    protected ?string $actorSource = null;

    /**
     * 監査ログを記録（DB + ファイル）
     */
    public function log(array $data): ?AuditLog
    {
        try {
            // リクエストIDを自動設定
            if (! isset($data['request_id'])) {
                $data['request_id'] = $this->getRequestId();
            }

            // プラグインコンテキストを自動設定
            if (! isset($data['plugin_name']) && $this->currentPlugin) {
                $data['plugin_name'] = $this->currentPlugin;
                $data['plugin_version'] = $this->currentPluginVersion;
            }

            // なりすましIDを自動設定
            if (! isset($data['impersonated_by_id']) && $this->impersonatedById) {
                $data['impersonated_by_id'] = $this->impersonatedById;
            }

            // 操作元チャネルを自動設定
            if (! isset($data['actor_source'])) {
                $data['actor_source'] = $this->getActorSource();
            }

            // AI生成フラグを自動設定
            if (! isset($data['is_ai_generated'])) {
                $data['is_ai_generated'] = ($data['actor_source'] ?? null) === AuditLog::ACTOR_SOURCE_AI_PLUGIN;
            }

            $auditLog = null;

            // DBに保存（テーブルが存在する場合のみ）
            if (Schema::hasTable('audit_logs')) {
                $auditLog = AuditLog::log($data);
            }

            // ファイルにも出力
            if ($this->fileLoggingEnabled) {
                $this->writeToFile($data, $auditLog);
            }

            // SIEM連携用イベントを発火
            if ($auditLog) {
                event(new AuditLogCreated($auditLog));
            }

            return $auditLog;
        } catch (\Throwable $e) {
            // 監査ログの記録失敗はアプリケーションを止めない
            Log::error('Audit log failed', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            return null;
        }
    }

    /**
     * ファイルにログを出力
     */
    protected function writeToFile(array $data, ?AuditLog $auditLog = null): void
    {
        try {
            // actorの情報を取得
            $actorInfo = 'system';
            if (isset($data['actor']) && $data['actor'] instanceof Model) {
                $actorInfo = class_basename($data['actor']).':'.$data['actor']->getKey();
                $actorDisplayName = $data['actor']->display_name ?? $data['actor']->account_name ?? $data['actor']->name ?? $data['actor']->email ?? null;
                if ($actorDisplayName) {
                    $actorInfo .= '('.$actorDisplayName.')';
                }
            } elseif ($auditLog && $auditLog->actor_name) {
                $actorInfo = $auditLog->actor_name;
            }

            // targetの情報を取得
            $targetInfo = null;
            if (isset($data['target']) && $data['target'] instanceof Model) {
                $targetInfo = class_basename($data['target']).':'.$data['target']->getKey();
            } elseif ($auditLog && $auditLog->target_label) {
                $targetInfo = $auditLog->target_label;
            }

            // ログメッセージを構築
            $logData = [
                'id' => $auditLog?->id,
                'category' => $data['category'] ?? 'unknown',
                'action' => $data['action'] ?? 'unknown',
                'severity' => $data['severity'] ?? AuditLog::SEVERITY_INFO,
                'outcome' => $data['outcome'] ?? AuditLog::OUTCOME_SUCCESS,
                'actor' => $actorInfo,
                'target' => $targetInfo,
                'ip' => $data['ip_address'] ?? request()->ip(),
                'plugin' => $data['plugin_name'] ?? $this->currentPlugin,
                'request_id' => $data['request_id'] ?? $this->requestId,
                'actor_source' => $data['actor_source'] ?? $this->actorSource,
                'is_ai_generated' => $data['is_ai_generated'] ?? false,
            ];

            // contextからmessageを取得
            if (isset($data['context']['message'])) {
                $logData['message'] = $data['context']['message'];
            }

            // JSON形式でログ出力（1行1レコード、SIEM連携しやすい形式）
            Log::channel('audit')->info(json_encode($logData, JSON_UNESCAPED_UNICODE));
        } catch (\Throwable $e) {
            // ファイル出力失敗は無視（DBには記録済み）
            Log::warning('Audit file log failed: '.$e->getMessage());
        }
    }

    /**
     * 認証ログを記録
     */
    public function logAuth(string $action, array $data = []): ?AuditLog
    {
        return $this->log(array_merge($data, [
            'category' => AuditLog::CATEGORY_AUTH,
            'action' => $action,
        ]));
    }

    /**
     * セキュリティログを記録
     */
    public function logSecurity(string $action, array $data = []): ?AuditLog
    {
        return $this->log(array_merge($data, [
            'category' => AuditLog::CATEGORY_SECURITY,
            'action' => $action,
            'severity' => $data['severity'] ?? AuditLog::SEVERITY_WARNING,
        ]));
    }

    /**
     * 拡張機能ログを記録
     */
    public function logExtension(string $action, array $data = []): ?AuditLog
    {
        return $this->log(array_merge($data, [
            'category' => AuditLog::CATEGORY_EXTENSION,
            'action' => $action,
            'severity' => $data['severity'] ?? AuditLog::SEVERITY_NOTICE,
        ]));
    }

    /**
     * アカウントログを記録
     */
    public function logAccount(string $action, array $data = []): ?AuditLog
    {
        return $this->log(array_merge($data, [
            'category' => AuditLog::CATEGORY_ACCOUNT,
            'action' => $action,
        ]));
    }

    /**
     * システムログを記録
     */
    public function logSystem(string $action, array $data = []): ?AuditLog
    {
        return $this->log(array_merge($data, [
            'category' => AuditLog::CATEGORY_SYSTEM,
            'action' => $action,
        ]));
    }

    /**
     * コンテンツログを記録
     */
    public function logContent(string $action, array $data = []): ?AuditLog
    {
        return $this->log(array_merge($data, [
            'category' => AuditLog::CATEGORY_CONTENT,
            'action' => $action,
        ]));
    }

    /**
     * プラグインログを記録
     */
    public function logPlugin(string $action, array $data = []): ?AuditLog
    {
        return $this->log(array_merge($data, [
            'category' => AuditLog::CATEGORY_PLUGIN,
            'action' => $action,
        ]));
    }

    /**
     * AI操作ログを記録
     */
    public function logAi(string $action, array $data = []): ?AuditLog
    {
        return $this->log(array_merge($data, [
            'category' => AuditLog::CATEGORY_AI,
            'action' => $action,
            'is_ai_generated' => true,
            'actor_source' => $data['actor_source'] ?? AuditLog::ACTOR_SOURCE_AI_PLUGIN,
        ]));
    }

    /**
     * Build structured context for AI operations
     *
     * @param  string  $reason  Why this AI operation was performed
     * @param  string|null  $intent  What the AI intended to achieve
     * @param  array  $extra  Additional context data
     * @return array Structured context array
     */
    public function buildAiContext(string $reason, ?string $intent = null, array $extra = []): array
    {
        return array_merge([
            'reason' => $reason,
            'intent' => $intent,
        ], $extra);
    }

    // ========================================
    // コンテキスト管理
    // ========================================

    /**
     * リクエストIDを取得（なければ生成）
     */
    public function getRequestId(): string
    {
        if ($this->requestId === null) {
            $this->requestId = request()->header('X-Request-ID') ?? (string) Str::uuid();
        }

        return $this->requestId;
    }

    /**
     * リクエストIDを設定
     */
    public function setRequestId(string $requestId): self
    {
        $this->requestId = $requestId;

        return $this;
    }

    /**
     * 操作元チャネルを設定
     */
    public function setActorSource(?string $source): self
    {
        $this->actorSource = $source;

        return $this;
    }

    /**
     * 操作元チャネルを取得（未設定なら自動検出）
     */
    public function getActorSource(): ?string
    {
        if ($this->actorSource !== null) {
            return $this->actorSource;
        }

        if (app()->runningInConsole()) {
            return AuditLog::ACTOR_SOURCE_CLI;
        }

        $request = request();
        if ($request && $request->is('api/*')) {
            return AuditLog::ACTOR_SOURCE_API;
        }

        return AuditLog::ACTOR_SOURCE_WEB;
    }

    /**
     * プラグインコンテキストを設定
     */
    public function setPluginContext(?string $pluginName, ?string $version = null): self
    {
        $this->currentPlugin = $pluginName;
        $this->currentPluginVersion = $version;

        return $this;
    }

    /**
     * プラグインコンテキストをクリア
     */
    public function clearPluginContext(): self
    {
        $this->currentPlugin = null;
        $this->currentPluginVersion = null;

        return $this;
    }

    /**
     * なりすましIDを設定
     */
    public function setImpersonatedBy(?int $memberId): self
    {
        $this->impersonatedById = $memberId;

        return $this;
    }

    /**
     * ファイルログを有効化
     */
    public function enableFileLogging(): self
    {
        $this->fileLoggingEnabled = true;

        return $this;
    }

    /**
     * ファイルログを無効化
     */
    public function disableFileLogging(): self
    {
        $this->fileLoggingEnabled = false;

        return $this;
    }

    /**
     * ファイルログが有効かどうか
     */
    public function isFileLoggingEnabled(): bool
    {
        return $this->fileLoggingEnabled;
    }

    // ========================================
    // 便利メソッド
    // ========================================

    /**
     * 変更差分を生成
     */
    public function diff(array $before, array $after): array
    {
        $diff = [];
        $allKeys = array_unique(array_merge(array_keys($before), array_keys($after)));

        foreach ($allKeys as $key) {
            $oldValue = $before[$key] ?? null;
            $newValue = $after[$key] ?? null;

            if ($oldValue !== $newValue) {
                $diff[$key] = [
                    'from' => $oldValue,
                    'to' => $newValue,
                ];
            }
        }

        return $diff;
    }

    /**
     * モデルの変更をログ
     */
    public function logModelChange(
        Model $model,
        string $action,
        ?Model $actor = null,
        ?array $additionalContext = null
    ): ?AuditLog {
        $context = [];

        // 変更前後の値を取得
        if ($model->wasRecentlyCreated) {
            $context['after'] = $model->getAttributes();
        } elseif ($model->wasChanged()) {
            $context['before'] = $model->getOriginal();
            $context['after'] = $model->getAttributes();
            $context['diff'] = $this->diff(
                array_intersect_key($model->getOriginal(), $model->getChanges()),
                $model->getChanges()
            );
        }

        // 追加コンテキストをマージ
        if ($additionalContext) {
            $context = array_merge($context, $additionalContext);
        }

        return $this->log([
            'category' => AuditLog::getCategoryForAction($action),
            'action' => $action,
            'actor' => $actor,
            'target' => $model,
            'context' => $context ?: null,
        ]);
    }

    /**
     * 設定変更をログ
     */
    public function logSettingsChange(
        string $settingKey,
        mixed $oldValue,
        mixed $newValue,
        ?Model $actor = null,
        ?string $pluginName = null
    ): ?AuditLog {
        return $this->log([
            'category' => AuditLog::CATEGORY_SYSTEM,
            'action' => AuditLog::ACTION_SETTINGS_UPDATED,
            'actor' => $actor,
            'target_label' => $settingKey,
            'plugin_name' => $pluginName,
            'context' => [
                'setting_key' => $settingKey,
                'before' => $oldValue,
                'after' => $newValue,
                'diff' => [
                    $settingKey => [
                        'from' => $oldValue,
                        'to' => $newValue,
                    ],
                ],
            ],
        ]);
    }

    /**
     * 設定の一括変更をログ（1ページ分を1エントリで記録）
     *
     * @param  string  $settingsPage  ページ識別子 (例: "security.password")
     * @param  array  $before  変更前の設定 ['key' => value, ...]
     * @param  array  $after  変更後の設定 ['key' => value, ...]
     * @param  Model|null  $actor  操作者
     * @param  array  $sensitiveKeys  マスク対象のキー名
     */
    public function logBulkSettingsChange(
        string $settingsPage,
        array $before,
        array $after,
        ?Model $actor = null,
        array $sensitiveKeys = [],
    ): ?AuditLog {
        // 型の不一致による偽の差分を防ぐため、全値を文字列に統一
        $before = array_map(fn ($v) => $v === null ? null : (string) $v, $before);
        $after = array_map(fn ($v) => $v === null ? null : (string) $v, $after);

        $diff = $this->diff($before, $after);

        if (empty($diff)) {
            return null;
        }

        // 機密値をマスク
        foreach ($sensitiveKeys as $key) {
            if (isset($diff[$key])) {
                $diff[$key]['from'] = $diff[$key]['from'] ? '********' : null;
                $diff[$key]['to'] = $diff[$key]['to'] ? '********' : null;
            }
        }

        return $this->log([
            'category' => AuditLog::CATEGORY_SECURITY,
            'action' => AuditLog::ACTION_SETTINGS_UPDATED,
            'actor' => $actor,
            'target_label' => $settingsPage,
            'severity' => AuditLog::SEVERITY_WARNING,
            'context' => [
                'settings_page' => $settingsPage,
                'changed_count' => count($diff),
                'changed_keys' => array_keys($diff),
                'diff' => $diff,
            ],
        ]);
    }

    // ========================================
    // クエリヘルパー
    // ========================================

    /**
     * 行為者のログを取得
     */
    public function getLogsForActor(Model $actor, int $limit = 50)
    {
        return AuditLog::forActor($actor)
            ->orderByDesc('occurred_at')
            ->limit($limit)
            ->get();
    }

    /**
     * 対象のログを取得
     */
    public function getLogsForTarget(Model $target, int $limit = 50)
    {
        return AuditLog::forTarget($target)
            ->orderByDesc('occurred_at')
            ->limit($limit)
            ->get();
    }

    /**
     * リクエストIDで関連ログを取得
     */
    public function getLogsForRequest(string $requestId)
    {
        return AuditLog::forRequest($requestId)
            ->orderBy('occurred_at')
            ->get();
    }

    /**
     * 最近の警告以上のログを取得
     */
    public function getRecentWarnings(int $hours = 24, int $limit = 100)
    {
        return AuditLog::recent($hours)
            ->warningOrAbove()
            ->orderByDesc('occurred_at')
            ->limit($limit)
            ->get();
    }

    /**
     * 最近の失敗ログを取得
     */
    public function getRecentFailures(int $hours = 24, int $limit = 100)
    {
        return AuditLog::recent($hours)
            ->failed()
            ->orderByDesc('occurred_at')
            ->limit($limit)
            ->get();
    }

    // ========================================
    // 重大操作ログ（β版 強制再認証の基盤）
    // ========================================

    /**
     * 危険な操作をログ（High/Criticalレベル）
     *
     * β版で強制再認証を実装する際の基盤
     */
    public function logDangerousOperation(string $action, array $data = []): ?AuditLog
    {
        $riskLevel = AuditLog::getRiskLevelForAction($action);

        // contextにリスクレベル情報を追加
        $context = $data['context'] ?? [];
        $context['risk_level'] = $riskLevel->toString();
        $context['risk_level_value'] = $riskLevel->value;
        $context['requires_step_up_auth'] = $riskLevel->requiresStepUpAuth();
        $data['context'] = $context;

        // 危険な操作は警告レベル以上で記録
        if ($riskLevel->isDangerous() && ! isset($data['severity'])) {
            $data['severity'] = $riskLevel->isCritical()
                ? AuditLog::SEVERITY_CRITICAL
                : AuditLog::SEVERITY_WARNING;
        }

        return $this->log(array_merge($data, [
            'action' => $action,
        ]));
    }

    /**
     * クリティカルな操作をログ
     */
    public function logCriticalOperation(string $action, array $data = []): ?AuditLog
    {
        $data['severity'] = $data['severity'] ?? AuditLog::SEVERITY_CRITICAL;

        return $this->logDangerousOperation($action, $data);
    }

    /**
     * 最近の危険な操作を取得
     */
    public function getRecentDangerousOperations(int $hours = 24, int $limit = 100)
    {
        return AuditLog::recent($hours)
            ->dangerousOperations()
            ->orderByDesc('occurred_at')
            ->limit($limit)
            ->get();
    }

    /**
     * 最近のクリティカルな操作を取得
     */
    public function getRecentCriticalOperations(int $hours = 24, int $limit = 100)
    {
        return AuditLog::recent($hours)
            ->criticalOperations()
            ->orderByDesc('occurred_at')
            ->limit($limit)
            ->get();
    }

    /**
     * 指定リスクレベル以上の操作を取得
     */
    public function getOperationsWithMinRiskLevel(
        OperationRiskLevel $minLevel,
        int $hours = 24,
        int $limit = 100
    ) {
        return AuditLog::recent($hours)
            ->withMinRiskLevel($minLevel)
            ->orderByDesc('occurred_at')
            ->limit($limit)
            ->get();
    }

    /**
     * 行為者の危険な操作履歴を取得
     */
    public function getDangerousOperationsForActor(Model $actor, int $limit = 50)
    {
        return AuditLog::forActor($actor)
            ->dangerousOperations()
            ->orderByDesc('occurred_at')
            ->limit($limit)
            ->get();
    }

    /**
     * 対象に対する危険な操作履歴を取得
     */
    public function getDangerousOperationsForTarget(Model $target, int $limit = 50)
    {
        return AuditLog::forTarget($target)
            ->dangerousOperations()
            ->orderByDesc('occurred_at')
            ->limit($limit)
            ->get();
    }

    /**
     * 危険な操作の統計を取得
     */
    public function getDangerousOperationStats(int $hours = 24): array
    {
        $logs = AuditLog::recent($hours)->dangerousOperations()->get();

        $stats = [
            'total' => $logs->count(),
            'by_risk_level' => [
                'high' => 0,
                'critical' => 0,
            ],
            'by_action' => [],
            'by_actor' => [],
        ];

        foreach ($logs as $log) {
            $riskLevel = $log->getRiskLevel();

            // リスクレベル別
            if ($riskLevel->isCritical()) {
                $stats['by_risk_level']['critical']++;
            } else {
                $stats['by_risk_level']['high']++;
            }

            // アクション別
            $action = $log->action;
            $stats['by_action'][$action] = ($stats['by_action'][$action] ?? 0) + 1;

            // 行為者別
            $actorKey = $log->actor_name ?? 'unknown';
            $stats['by_actor'][$actorKey] = ($stats['by_actor'][$actorKey] ?? 0) + 1;
        }

        return $stats;
    }
}
