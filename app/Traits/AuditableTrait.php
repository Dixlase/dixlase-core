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
 *       (see LICENSE.commercial, or contact office@exc-d.com).
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

namespace App\Traits;

use App\Facades\Audit;
use App\Models\AuditLog;

/**
 * Trait for automatic audit logging of models
 *
 * Automatically records audit logs when models are created, updated, or deleted
 *
 * Usage example:
 * ```php
 * class Post extends Model
 * {
 *     use AuditableTrait;
 *
 *     // Optional: columns excluded from audit
 *     protected array $auditExclude = ['updated_at', 'remember_token'];
 *
 *     // Optional: columns to audit (if specified, only these are recorded)
 *     protected array $auditInclude = ['title', 'status'];
 *
 *     // Optional: specify category
 *     protected string $auditCategory = 'content';
 *
 *     // Optional: specify plugin name
 *     protected ?string $auditPluginName = 'my-plugin';
 * }
 * ```
 *
 * ## Coexistence with the Action layer
 *
 * If a model that uses this trait is also touched inside an `App\Actions\AbstractAction`
 * subclass, the trait and the action both write `audit_logs` rows, producing a
 * duplicate. The convention is that the action owns the audit log; suppress the
 * trait inside the action by wrapping the save with `$model->withoutAudit(...)`.
 * See `docs/development/action-layer.md` for the full responsibility table.
 */
trait AuditableTrait
{
    /**
     * Register event listeners when booting the model
     */
    public static function bootAuditableTrait(): void
    {
        // On create
        static::created(function ($model) {
            $model->logAuditEvent('created');
        });

        // On update
        static::updated(function ($model) {
            $model->logAuditEvent('updated');
        });

        // On delete
        static::deleted(function ($model) {
            $model->logAuditEvent('deleted');
        });
    }

    /**
     * Record audit event to log
     */
    protected function logAuditEvent(string $event): void
    {
        // Skip if audit is disabled
        if (property_exists($this, 'auditDisabled') && $this->auditDisabled) {
            return;
        }

        $action = $this->getAuditAction($event);
        $category = $this->getAuditCategory();
        $context = $this->buildAuditContext($event);

        // Skip if no changes (update only)
        if ($event === 'updated' && empty($context['diff'])) {
            return;
        }

        Audit::log([
            'category' => $category,
            'action' => $action,
            'actor' => $this->getAuditActor(),
            'target' => $this,
            'target_label' => $this->getAuditTargetLabel(),
            'severity' => $this->getAuditSeverity($event),
            'plugin_name' => $this->getAuditPluginName(),
            'context' => $context,
        ]);
    }

    /**
     * Get audit action name
     */
    protected function getAuditAction(string $event): string
    {
        // If custom action name is defined
        if (property_exists($this, 'auditActions') && isset($this->auditActions[$event])) {
            return $this->auditActions[$event];
        }

        // Default: model_name_event (e.g., post_created)
        $modelName = strtolower(class_basename($this));

        return "{$modelName}_{$event}";
    }

    /**
     * Get audit category
     */
    protected function getAuditCategory(): string
    {
        if (property_exists($this, 'auditCategory')) {
            return $this->auditCategory;
        }

        return AuditLog::CATEGORY_CONTENT;
    }

    /**
     * Build audit context
     */
    protected function buildAuditContext(string $event): array
    {
        $context = [];

        switch ($event) {
            case 'created':
                $context['after'] = $this->filterAuditAttributes($this->getAttributes());
                $context['message'] = $this->getAuditMessage($event);
                break;

            case 'updated':
                $original = $this->filterAuditAttributes($this->getOriginal());
                $changes = $this->filterAuditAttributes($this->getChanges());

                if (! empty($changes)) {
                    $context['before'] = array_intersect_key($original, $changes);
                    $context['after'] = $changes;
                    $context['diff'] = $this->buildDiff($context['before'], $context['after']);
                    $context['message'] = $this->getAuditMessage($event);
                }
                break;

            case 'deleted':
                $context['before'] = $this->filterAuditAttributes($this->getAttributes());
                $context['message'] = $this->getAuditMessage($event);
                break;
        }

        // Additional meta information
        $context['meta'] = [
            'model' => get_class($this),
            'id' => $this->getKey(),
        ];

        return $context;
    }

    /**
     * Filter auditable attributes
     */
    protected function filterAuditAttributes(array $attributes): array
    {
        // Exclusion list
        $exclude = property_exists($this, 'auditExclude')
            ? $this->auditExclude
            : ['password', 'remember_token', 'two_fa_secret', 'two_fa_recovery_codes'];

        // Inclusion list (only these if specified)
        if (property_exists($this, 'auditInclude') && ! empty($this->auditInclude)) {
            $attributes = array_intersect_key($attributes, array_flip($this->auditInclude));
        }

        // Exclude
        return array_diff_key($attributes, array_flip($exclude));
    }

    /**
     * Build diff
     */
    protected function buildDiff(array $before, array $after): array
    {
        $diff = [];
        foreach ($after as $key => $newValue) {
            $oldValue = $before[$key] ?? null;
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
     * Get audit message
     */
    protected function getAuditMessage(string $event): string
    {
        if (property_exists($this, 'auditMessages') && isset($this->auditMessages[$event])) {
            return $this->auditMessages[$event];
        }

        $modelName = class_basename($this);

        return match ($event) {
            'created' => "{$modelName}が作成されました",
            'updated' => "{$modelName}が更新されました",
            'deleted' => "{$modelName}が削除されました",
            default => "{$modelName}が変更されました",
        };
    }

    /**
     * Get auditable label
     */
    protected function getAuditTargetLabel(): ?string
    {
        // If custom label attribute is defined
        if (property_exists($this, 'auditLabelAttribute')) {
            return $this->{$this->auditLabelAttribute} ?? null;
        }

        // Default: search in order of name, title, email
        return $this->name ?? $this->title ?? $this->email ?? null;
    }

    /**
     * Get audit actor
     */
    protected function getAuditActor()
    {
        // If custom actor is set
        if (property_exists($this, 'auditActor') && $this->auditActor) {
            return $this->auditActor;
        }

        // Return authenticated user
        return auth()->user();
    }

    /**
     * Get audit severity
     */
    protected function getAuditSeverity(string $event): string
    {
        if (property_exists($this, 'auditSeverities') && isset($this->auditSeverities[$event])) {
            return $this->auditSeverities[$event];
        }

        return match ($event) {
            'deleted' => AuditLog::SEVERITY_WARNING,
            default => AuditLog::SEVERITY_INFO,
        };
    }

    /**
     * Get audit plugin name
     */
    protected function getAuditPluginName(): ?string
    {
        return property_exists($this, 'auditPluginName') ? $this->auditPluginName : null;
    }

    /**
     * Temporarily disable audit
     */
    public function withoutAudit(callable $callback)
    {
        $this->auditDisabled = true;
        try {
            return $callback($this);
        } finally {
            $this->auditDisabled = false;
        }
    }

    /**
     * Set actor
     */
    public function setAuditActor($actor): self
    {
        $this->auditActor = $actor;

        return $this;
    }
}
