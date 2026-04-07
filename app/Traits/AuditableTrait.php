<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
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
 * モデルの監査ログ自動記録トレイト
 *
 * モデルの作成・更新・削除時に自動的に監査ログを記録
 *
 * 使用例:
 * ```php
 * class Post extends Model
 * {
 *     use AuditableTrait;
 *
 *     // オプション: 監査対象外のカラム
 *     protected array $auditExclude = ['updated_at', 'remember_token'];
 *
 *     // オプション: 監査対象のカラム（指定した場合、これらのみ記録）
 *     protected array $auditInclude = ['title', 'status'];
 *
 *     // オプション: カテゴリを指定
 *     protected string $auditCategory = 'content';
 *
 *     // オプション: プラグイン名を指定
 *     protected ?string $auditPluginName = 'my-plugin';
 * }
 * ```
 */
trait AuditableTrait
{
    /**
     * モデルのブート時にイベントリスナーを登録
     */
    public static function bootAuditableTrait(): void
    {
        // 作成時
        static::created(function ($model) {
            $model->logAuditEvent('created');
        });

        // 更新時
        static::updated(function ($model) {
            $model->logAuditEvent('updated');
        });

        // 削除時
        static::deleted(function ($model) {
            $model->logAuditEvent('deleted');
        });
    }

    /**
     * 監査イベントをログに記録
     */
    protected function logAuditEvent(string $event): void
    {
        // 監査が無効化されている場合はスキップ
        if (property_exists($this, 'auditDisabled') && $this->auditDisabled) {
            return;
        }

        $action = $this->getAuditAction($event);
        $category = $this->getAuditCategory();
        $context = $this->buildAuditContext($event);

        // 変更がない場合はスキップ（更新時のみ）
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
     * 監査アクション名を取得
     */
    protected function getAuditAction(string $event): string
    {
        // カスタムアクション名が定義されている場合
        if (property_exists($this, 'auditActions') && isset($this->auditActions[$event])) {
            return $this->auditActions[$event];
        }

        // デフォルト: モデル名_イベント（例: post_created）
        $modelName = strtolower(class_basename($this));

        return "{$modelName}_{$event}";
    }

    /**
     * 監査カテゴリを取得
     */
    protected function getAuditCategory(): string
    {
        if (property_exists($this, 'auditCategory')) {
            return $this->auditCategory;
        }

        return AuditLog::CATEGORY_CONTENT;
    }

    /**
     * 監査コンテキストを構築
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

        // 追加のメタ情報
        $context['meta'] = [
            'model' => get_class($this),
            'id' => $this->getKey(),
        ];

        return $context;
    }

    /**
     * 監査対象の属性をフィルタ
     */
    protected function filterAuditAttributes(array $attributes): array
    {
        // 除外リスト
        $exclude = property_exists($this, 'auditExclude')
            ? $this->auditExclude
            : ['password', 'remember_token', 'two_fa_secret', 'two_fa_recovery_codes'];

        // 含めるリスト（指定がある場合はこれらのみ）
        if (property_exists($this, 'auditInclude') && ! empty($this->auditInclude)) {
            $attributes = array_intersect_key($attributes, array_flip($this->auditInclude));
        }

        // 除外
        return array_diff_key($attributes, array_flip($exclude));
    }

    /**
     * 差分を構築
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
     * 監査メッセージを取得
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
     * 監査対象ラベルを取得
     */
    protected function getAuditTargetLabel(): ?string
    {
        // カスタムラベル属性が定義されている場合
        if (property_exists($this, 'auditLabelAttribute')) {
            return $this->{$this->auditLabelAttribute} ?? null;
        }

        // デフォルト: name, title, email の順で探す
        return $this->name ?? $this->title ?? $this->email ?? null;
    }

    /**
     * 監査の行為者を取得
     */
    protected function getAuditActor()
    {
        // カスタム行為者が設定されている場合
        if (property_exists($this, 'auditActor') && $this->auditActor) {
            return $this->auditActor;
        }

        // 認証済みユーザーを返す
        return auth()->user();
    }

    /**
     * 監査の重要度を取得
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
     * 監査のプラグイン名を取得
     */
    protected function getAuditPluginName(): ?string
    {
        return property_exists($this, 'auditPluginName') ? $this->auditPluginName : null;
    }

    /**
     * 監査を一時的に無効化
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
     * 行為者を設定
     */
    public function setAuditActor($actor): self
    {
        $this->auditActor = $actor;

        return $this;
    }
}
