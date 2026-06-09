<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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

namespace App\Services;

use App\Enums\OperationRiskLevel;
use App\Events\AuditLogCreated;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * @internal Core only. Do not reference from plugins/themes
 *
 * Audit log service
 *
 * Provides unified API, available from plugins
 */
class AuditService
{
    /**
     * Current request ID (shared within a single request)
     */
    protected ?string $requestId = null;

    /**
     * Current plugin context
     */
    protected ?string $currentPlugin = null;

    protected ?string $currentPluginVersion = null;

    /**
     * Impersonated actor ID
     */
    protected ?int $impersonatedById = null;

    /**
     * Whether to enable file logging
     */
    protected bool $fileLoggingEnabled = true;

    /**
     * Source channel (web, api, cli, scheduler, ai_plugin, webhook, queue)
     */
    protected ?string $actorSource = null;

    /**
     * Record audit log (DB + file)
     */
    public function log(array $data): ?AuditLog
    {
        try {
            // Auto-set request ID
            if (! isset($data['request_id'])) {
                $data['request_id'] = $this->getRequestId();
            }

            // Auto-set plugin context
            if (! isset($data['plugin_name']) && $this->currentPlugin) {
                $data['plugin_name'] = $this->currentPlugin;
                $data['plugin_version'] = $this->currentPluginVersion;
            }

            // Auto-set impersonation ID
            if (! isset($data['impersonated_by_id']) && $this->impersonatedById) {
                $data['impersonated_by_id'] = $this->impersonatedById;
            }

            // Auto-set source channel
            if (! isset($data['actor_source'])) {
                $data['actor_source'] = $this->getActorSource();
            }

            // Auto-set AI generation flag
            if (! isset($data['is_ai_generated'])) {
                $data['is_ai_generated'] = ($data['actor_source'] ?? null) === AuditLog::ACTOR_SOURCE_AI_PLUGIN;
            }

            $auditLog = null;

            // Save to DB (only if table exists)
            if (Schema::hasTable('audit_logs')) {
                $auditLog = AuditLog::log($data);
            }

            // Output to file as well
            if ($this->fileLoggingEnabled) {
                $this->writeToFile($data, $auditLog);
            }

            // Fire event for SIEM integration
            if ($auditLog) {
                event(AuditLogCreated::fromAuditLog($auditLog));
            }

            return $auditLog;
        } catch (\Throwable $e) {
            // Audit log recording failure does not stop the application
            Log::error('Audit log failed', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            return null;
        }
    }

    /**
     * Output log to file
     */
    protected function writeToFile(array $data, ?AuditLog $auditLog = null): void
    {
        try {
            // Get actor information
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

            // Get target information
            $targetInfo = null;
            if (isset($data['target']) && $data['target'] instanceof Model) {
                $targetInfo = class_basename($data['target']).':'.$data['target']->getKey();
            } elseif ($auditLog && $auditLog->target_label) {
                $targetInfo = $auditLog->target_label;
            }

            // Build log message
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

            // Get message from context
            if (isset($data['context']['message'])) {
                $logData['message'] = $data['context']['message'];
            }

            // Output log in JSON format (one record per line, SIEM-friendly format)
            Log::channel('audit')->info(json_encode($logData, JSON_UNESCAPED_UNICODE));
        } catch (\Throwable $e) {
            // Ignore file output failure (already recorded in DB)
            Log::warning('Audit file log failed: '.$e->getMessage());
        }
    }

    /**
     * Record authentication log
     */
    public function logAuth(string $action, array $data = []): ?AuditLog
    {
        return $this->log(array_merge($data, [
            'category' => AuditLog::CATEGORY_AUTH,
            'action' => $action,
        ]));
    }

    /**
     * Record security log
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
     * Record extension log
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
     * Record account log
     */
    public function logAccount(string $action, array $data = []): ?AuditLog
    {
        return $this->log(array_merge($data, [
            'category' => AuditLog::CATEGORY_ACCOUNT,
            'action' => $action,
        ]));
    }

    /**
     * Record system log
     */
    public function logSystem(string $action, array $data = []): ?AuditLog
    {
        return $this->log(array_merge($data, [
            'category' => AuditLog::CATEGORY_SYSTEM,
            'action' => $action,
        ]));
    }

    /**
     * Record content log
     */
    public function logContent(string $action, array $data = []): ?AuditLog
    {
        return $this->log(array_merge($data, [
            'category' => AuditLog::CATEGORY_CONTENT,
            'action' => $action,
        ]));
    }

    /**
     * Record plugin log
     */
    public function logPlugin(string $action, array $data = []): ?AuditLog
    {
        return $this->log(array_merge($data, [
            'category' => AuditLog::CATEGORY_PLUGIN,
            'action' => $action,
        ]));
    }

    /**
     * Record AI operation log
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
    // Context management
    // ========================================

    /**
     * Get request ID (generate if not exists)
     */
    public function getRequestId(): string
    {
        if ($this->requestId === null) {
            $this->requestId = request()->header('X-Request-ID') ?? (string) Str::uuid();
        }

        return $this->requestId;
    }

    /**
     * Set request ID
     */
    public function setRequestId(string $requestId): self
    {
        $this->requestId = $requestId;

        return $this;
    }

    /**
     * Set operation source channel
     */
    public function setActorSource(?string $source): self
    {
        $this->actorSource = $source;

        return $this;
    }

    /**
     * Get operation source channel (auto-detect if not set)
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
     * Set plugin context
     */
    public function setPluginContext(?string $pluginName, ?string $version = null): self
    {
        $this->currentPlugin = $pluginName;
        $this->currentPluginVersion = $version;

        return $this;
    }

    /**
     * Clear plugin context
     */
    public function clearPluginContext(): self
    {
        $this->currentPlugin = null;
        $this->currentPluginVersion = null;

        return $this;
    }

    /**
     * Set impersonation ID
     */
    public function setImpersonatedBy(?int $memberId): self
    {
        $this->impersonatedById = $memberId;

        return $this;
    }

    /**
     * Enable file logging
     */
    public function enableFileLogging(): self
    {
        $this->fileLoggingEnabled = true;

        return $this;
    }

    /**
     * Disable file logging
     */
    public function disableFileLogging(): self
    {
        $this->fileLoggingEnabled = false;

        return $this;
    }

    /**
     * Check if file logging is enabled
     */
    public function isFileLoggingEnabled(): bool
    {
        return $this->fileLoggingEnabled;
    }

    // ========================================
    // Convenience methods
    // ========================================

    /**
     * Generate change diff
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
     * Log model changes
     */
    public function logModelChange(
        Model $model,
        string $action,
        ?Model $actor = null,
        ?array $additionalContext = null
    ): ?AuditLog {
        $context = [];

        // Get before/after values
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

        // Merge additional context
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
     * Log settings change
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
     * Log bulk settings changes (record one page as one entry)
     *
     * @param  string  $settingsPage  Page identifier (e.g., "security.password")
     * @param  array  $before  Settings before change ['key' => value, ...]
     * @param  array  $after  Settings after change ['key' => value, ...]
     * @param  Model|null  $actor  Operator
     * @param  array  $sensitiveKeys  Key names to mask
     */
    public function logBulkSettingsChange(
        string $settingsPage,
        array $before,
        array $after,
        ?Model $actor = null,
        array $sensitiveKeys = [],
    ): ?AuditLog {
        // Normalize scalar values to strings to prevent false diffs from type mismatches (arrays are left as-is)
        $before = array_map(fn ($v) => $v === null ? null : (is_array($v) ? $v : (string) $v), $before);
        $after = array_map(fn ($v) => $v === null ? null : (is_array($v) ? $v : (string) $v), $after);

        $diff = $this->diff($before, $after);

        if (empty($diff)) {
            return null;
        }

        // Mask sensitive values
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
    // Query helpers
    // ========================================

    /**
     * Get logs by actor
     */
    public function getLogsForActor(Model $actor, int $limit = 50)
    {
        return AuditLog::forActor($actor)
            ->orderByDesc('occurred_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get target logs
     */
    public function getLogsForTarget(Model $target, int $limit = 50)
    {
        return AuditLog::forTarget($target)
            ->orderByDesc('occurred_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get related logs by request ID
     */
    public function getLogsForRequest(string $requestId)
    {
        return AuditLog::forRequest($requestId)
            ->orderBy('occurred_at')
            ->get();
    }

    /**
     * Get recent logs at warning level or above
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
     * Get recent failed logs
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
    // Critical operation log (foundation for forced re-authentication in beta)
    // ========================================

    /**
     * Log dangerous operations (High/Critical level)
     *
     * Foundation for implementing forced re-authentication in beta
     */
    public function logDangerousOperation(string $action, array $data = []): ?AuditLog
    {
        $riskLevel = AuditLog::getRiskLevelForAction($action);

        // Add risk level information to context
        $context = $data['context'] ?? [];
        $context['risk_level'] = $riskLevel->toString();
        $context['risk_level_value'] = $riskLevel->value;
        $context['requires_step_up_auth'] = $riskLevel->requiresStepUpAuth();
        $data['context'] = $context;

        // Dangerous operations are logged at warning level or above
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
     * Log critical operations
     */
    public function logCriticalOperation(string $action, array $data = []): ?AuditLog
    {
        $data['severity'] = $data['severity'] ?? AuditLog::SEVERITY_CRITICAL;

        return $this->logDangerousOperation($action, $data);
    }

    /**
     * Get recent dangerous operations
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
     * Get recent critical operations
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
     * Get operations at or above specified risk level
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
     * Get dangerous operation history by actor
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
     * Get dangerous operation history for target
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
     * Get statistics of dangerous operations
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

            // By risk level
            if ($riskLevel->isCritical()) {
                $stats['by_risk_level']['critical']++;
            } else {
                $stats['by_risk_level']['high']++;
            }

            // By action
            $action = $log->action;
            $stats['by_action'][$action] = ($stats['by_action'][$action] ?? 0) + 1;

            // By actor
            $actorKey = $log->actor_name ?? 'unknown';
            $stats['by_actor'][$actorKey] = ($stats['by_actor'][$actorKey] ?? 0) + 1;
        }

        return $stats;
    }
}
