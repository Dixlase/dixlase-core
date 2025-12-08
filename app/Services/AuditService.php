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

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
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
     * 監査ログを記録（DB + ファイル）
     */
    public function log(array $data): ?AuditLog
    {
        try {
            // リクエストIDを自動設定
            if (!isset($data['request_id'])) {
                $data['request_id'] = $this->getRequestId();
            }

            // プラグインコンテキストを自動設定
            if (!isset($data['plugin_name']) && $this->currentPlugin) {
                $data['plugin_name'] = $this->currentPlugin;
                $data['plugin_version'] = $this->currentPluginVersion;
            }

            // なりすましIDを自動設定
            if (!isset($data['impersonated_by_id']) && $this->impersonatedById) {
                $data['impersonated_by_id'] = $this->impersonatedById;
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
                $actorInfo = class_basename($data['actor']) . ':' . $data['actor']->getKey();
                if ($data['actor']->name ?? $data['actor']->email ?? null) {
                    $actorInfo .= '(' . ($data['actor']->name ?? $data['actor']->email) . ')';
                }
            } elseif ($auditLog && $auditLog->actor_name) {
                $actorInfo = $auditLog->actor_name;
            }

            // targetの情報を取得
            $targetInfo = null;
            if (isset($data['target']) && $data['target'] instanceof Model) {
                $targetInfo = class_basename($data['target']) . ':' . $data['target']->getKey();
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
            ];

            // contextからmessageを取得
            if (isset($data['context']['message'])) {
                $logData['message'] = $data['context']['message'];
            }

            // JSON形式でログ出力（1行1レコード、SIEM連携しやすい形式）
            Log::channel('audit')->info(json_encode($logData, JSON_UNESCAPED_UNICODE));
        } catch (\Throwable $e) {
            // ファイル出力失敗は無視（DBには記録済み）
            Log::warning('Audit file log failed: ' . $e->getMessage());
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
}
