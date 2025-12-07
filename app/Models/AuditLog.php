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

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

/**
 * 監査ログモデル
 * 
 * 誰が / いつ / どこから / 何に対して / 何をしたか を記録
 */
class AuditLog extends Model
{
    protected $table = 'audit_logs';

    protected $fillable = [
        'occurred_at',
        'severity',
        'outcome',
        'category',
        'action',
        'actor_type',
        'actor_id',
        'actor_name',
        'impersonated_by_id',
        'target_type',
        'target_id',
        'target_label',
        'ip_address',
        'user_agent',
        'request_id',
        'session_id',
        'plugin_name',
        'plugin_version',
        'context',
        'schema_version',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'context' => 'array',
        'schema_version' => 'integer',
    ];

    // ========================================
    // Severity（重要度）定数
    // ========================================
    public const SEVERITY_DEBUG = 'debug';
    public const SEVERITY_INFO = 'info';
    public const SEVERITY_NOTICE = 'notice';
    public const SEVERITY_WARNING = 'warning';
    public const SEVERITY_ERROR = 'error';
    public const SEVERITY_CRITICAL = 'critical';
    public const SEVERITY_ALERT = 'alert';
    public const SEVERITY_EMERGENCY = 'emergency';

    // ========================================
    // Outcome（結果）定数
    // ========================================
    public const OUTCOME_SUCCESS = 'success';
    public const OUTCOME_FAILURE = 'failure';
    public const OUTCOME_DENIED = 'denied';
    public const OUTCOME_PENDING = 'pending';
    public const OUTCOME_UNKNOWN = 'unknown';

    // ========================================
    // Category（カテゴリ）定数
    // ========================================
    public const CATEGORY_AUTH = 'auth';
    public const CATEGORY_ACCOUNT = 'account';
    public const CATEGORY_DEVICE = 'device';
    public const CATEGORY_SECURITY = 'security';
    public const CATEGORY_SESSION = 'session';
    public const CATEGORY_EXTENSION = 'extension';
    public const CATEGORY_CONTENT = 'content';
    public const CATEGORY_SYSTEM = 'system';
    public const CATEGORY_PLUGIN = 'plugin';

    // ========================================
    // Action（アクション）定数 - 認証関連
    // ========================================
    public const ACTION_LOGIN = 'login';
    public const ACTION_LOGOUT = 'logout';
    public const ACTION_LOGIN_FAILED = 'login_failed';
    public const ACTION_NEW_DEVICE_LOGIN = 'new_device_login';
    public const ACTION_PASSWORD_CHANGED = 'password_changed';
    public const ACTION_PASSWORD_RESET = 'password_reset';
    public const ACTION_EMAIL_CHANGED = 'email_changed';

    // ========================================
    // Action（アクション）定数 - 2FA関連
    // ========================================
    public const ACTION_2FA_ENABLED = '2fa_enabled';
    public const ACTION_2FA_DISABLED = '2fa_disabled';
    public const ACTION_2FA_CODE_SENT = '2fa_code_sent';
    public const ACTION_2FA_CODE_VERIFIED = '2fa_code_verified';
    public const ACTION_2FA_CODE_FAILED = '2fa_code_failed';
    public const ACTION_RECOVERY_CODE_USED = 'recovery_code_used';
    public const ACTION_PASSKEY_REGISTERED = 'passkey_registered';
    public const ACTION_PASSKEY_REVOKED = 'passkey_revoked';

    // ========================================
    // Action（アクション）定数 - デバイス関連
    // ========================================
    public const ACTION_DEVICE_TRUSTED = 'device_trusted';
    public const ACTION_DEVICE_BLOCKED = 'device_blocked';
    public const ACTION_DEVICE_REMOVED = 'device_removed';

    // ========================================
    // Action（アクション）定数 - セキュリティ関連
    // ========================================
    public const ACTION_IP_BLOCKED = 'ip_blocked';
    public const ACTION_IP_ALLOWED = 'ip_allowed';
    public const ACTION_LOCKOUT_TRIGGERED = 'lockout_triggered';
    public const ACTION_LOCKOUT_RELEASED = 'lockout_released';
    public const ACTION_STEP_UP_AUTH_REQUIRED = 'step_up_auth_required';
    public const ACTION_STEP_UP_AUTH_COMPLETED = 'step_up_auth_completed';

    // ========================================
    // Action（アクション）定数 - セッション関連
    // ========================================
    public const ACTION_SESSION_CREATED = 'session_created';
    public const ACTION_SESSION_DESTROYED = 'session_destroyed';
    public const ACTION_FORCED_LOGOUT = 'forced_logout';

    // ========================================
    // Action（アクション）定数 - 拡張機能関連
    // ========================================
    public const ACTION_PLUGIN_INSTALLED = 'plugin_installed';
    public const ACTION_PLUGIN_ENABLED = 'plugin_enabled';
    public const ACTION_PLUGIN_DISABLED = 'plugin_disabled';
    public const ACTION_PLUGIN_UNINSTALLED = 'plugin_uninstalled';
    public const ACTION_PLUGIN_UPDATED = 'plugin_updated';
    public const ACTION_THEME_INSTALLED = 'theme_installed';
    public const ACTION_THEME_ENABLED = 'theme_enabled';
    public const ACTION_THEME_DISABLED = 'theme_disabled';
    public const ACTION_THEME_UNINSTALLED = 'theme_uninstalled';
    public const ACTION_THEME_UPDATED = 'theme_updated';

    // ========================================
    // Action（アクション）定数 - 設定関連
    // ========================================
    public const ACTION_SETTINGS_UPDATED = 'settings_updated';
    public const ACTION_MEMBER_CREATED = 'member_created';
    public const ACTION_MEMBER_UPDATED = 'member_updated';
    public const ACTION_MEMBER_DELETED = 'member_deleted';
    public const ACTION_ROLE_CHANGED = 'role_changed';

    // ========================================
    // リレーション
    // ========================================

    /**
     * 行為者（Polymorphic）
     */
    public function actor(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * 対象（Polymorphic）
     */
    public function target(): MorphTo
    {
        return $this->morphTo();
    }

    // ========================================
    // 統一ログAPI
    // ========================================

    /**
     * 監査ログを記録（メインAPI）
     * 
     * @param array $data ログデータ
     * @return self
     */
    public static function log(array $data): self
    {
        // リクエストコンテキストを自動取得
        $request = request();
        
        // request_idがなければ生成
        $requestId = $data['request_id'] ?? $request->header('X-Request-ID') ?? (string) Str::uuid();
        
        // actorの処理
        $actorType = null;
        $actorId = null;
        $actorName = $data['actor_name'] ?? null;
        
        if (isset($data['actor']) && $data['actor'] instanceof Model) {
            $actorType = get_class($data['actor']);
            $actorId = $data['actor']->getKey();
            $actorName = $actorName ?? $data['actor']->name ?? $data['actor']->email ?? null;
        }
        
        // targetの処理
        $targetType = null;
        $targetId = null;
        $targetLabel = $data['target_label'] ?? null;
        
        if (isset($data['target']) && $data['target'] instanceof Model) {
            $targetType = get_class($data['target']);
            $targetId = $data['target']->getKey();
            $targetLabel = $targetLabel ?? $data['target']->name ?? $data['target']->title ?? null;
        }
        
        return self::create([
            'occurred_at' => $data['occurred_at'] ?? now(),
            'severity' => $data['severity'] ?? self::SEVERITY_INFO,
            'outcome' => $data['outcome'] ?? self::OUTCOME_SUCCESS,
            'category' => $data['category'],
            'action' => $data['action'],
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'actor_name' => $actorName,
            'impersonated_by_id' => $data['impersonated_by_id'] ?? null,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'target_label' => $targetLabel,
            'ip_address' => $data['ip_address'] ?? $request->ip(),
            'user_agent' => $data['user_agent'] ?? $request->userAgent(),
            'request_id' => $requestId,
            'session_id' => $data['session_id'] ?? session()->getId(),
            'plugin_name' => $data['plugin_name'] ?? $data['plugin'] ?? null,
            'plugin_version' => $data['plugin_version'] ?? null,
            'context' => $data['context'] ?? null,
            'schema_version' => 1,
        ]);
    }

    /**
     * 認証ログを記録
     */
    public static function logAuth(string $action, array $data = []): self
    {
        return self::log(array_merge($data, [
            'category' => self::CATEGORY_AUTH,
            'action' => $action,
        ]));
    }

    /**
     * セキュリティログを記録
     */
    public static function logSecurity(string $action, array $data = []): self
    {
        return self::log(array_merge($data, [
            'category' => self::CATEGORY_SECURITY,
            'action' => $action,
            'severity' => $data['severity'] ?? self::SEVERITY_WARNING,
        ]));
    }

    /**
     * 拡張機能ログを記録
     */
    public static function logExtension(string $action, array $data = []): self
    {
        return self::log(array_merge($data, [
            'category' => self::CATEGORY_EXTENSION,
            'action' => $action,
            'severity' => $data['severity'] ?? self::SEVERITY_NOTICE,
        ]));
    }

    /**
     * アカウントログを記録
     */
    public static function logAccount(string $action, array $data = []): self
    {
        return self::log(array_merge($data, [
            'category' => self::CATEGORY_ACCOUNT,
            'action' => $action,
        ]));
    }

    /**
     * システムログを記録
     */
    public static function logSystem(string $action, array $data = []): self
    {
        return self::log(array_merge($data, [
            'category' => self::CATEGORY_SYSTEM,
            'action' => $action,
        ]));
    }

    // ========================================
    // スコープ
    // ========================================

    /**
     * 行為者でフィルタ
     */
    public function scopeForActor($query, Model $actor)
    {
        return $query->where('actor_type', get_class($actor))
                     ->where('actor_id', $actor->getKey());
    }

    /**
     * 対象でフィルタ
     */
    public function scopeForTarget($query, Model $target)
    {
        return $query->where('target_type', get_class($target))
                     ->where('target_id', $target->getKey());
    }

    /**
     * カテゴリでフィルタ
     */
    public function scopeInCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * アクションでフィルタ
     */
    public function scopeWithAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * 重要度でフィルタ
     */
    public function scopeWithSeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * 結果でフィルタ
     */
    public function scopeWithOutcome($query, string $outcome)
    {
        return $query->where('outcome', $outcome);
    }

    /**
     * プラグインでフィルタ
     */
    public function scopeForPlugin($query, string $pluginName)
    {
        return $query->where('plugin_name', $pluginName);
    }

    /**
     * リクエストIDでフィルタ（1リクエスト内の関連ログ取得）
     */
    public function scopeForRequest($query, string $requestId)
    {
        return $query->where('request_id', $requestId);
    }

    /**
     * 期間でフィルタ
     */
    public function scopeOccurredBetween($query, $start, $end)
    {
        return $query->whereBetween('occurred_at', [$start, $end]);
    }

    /**
     * 最近のログを取得
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('occurred_at', '>=', now()->subHours($hours));
    }

    /**
     * 警告以上の重要度
     */
    public function scopeWarningOrAbove($query)
    {
        return $query->whereIn('severity', [
            self::SEVERITY_WARNING,
            self::SEVERITY_ERROR,
            self::SEVERITY_CRITICAL,
            self::SEVERITY_ALERT,
            self::SEVERITY_EMERGENCY,
        ]);
    }

    /**
     * 失敗したイベント
     */
    public function scopeFailed($query)
    {
        return $query->whereIn('outcome', [
            self::OUTCOME_FAILURE,
            self::OUTCOME_DENIED,
        ]);
    }

    // ========================================
    // ヘルパーメソッド
    // ========================================

    /**
     * アクションからデフォルトのカテゴリを推測
     */
    public static function getCategoryForAction(string $action): string
    {
        $authActions = [
            self::ACTION_LOGIN, self::ACTION_LOGOUT, self::ACTION_LOGIN_FAILED,
            self::ACTION_NEW_DEVICE_LOGIN, self::ACTION_2FA_CODE_SENT,
            self::ACTION_2FA_CODE_VERIFIED, self::ACTION_2FA_CODE_FAILED,
        ];

        $accountActions = [
            self::ACTION_PASSWORD_CHANGED, self::ACTION_PASSWORD_RESET,
            self::ACTION_EMAIL_CHANGED, self::ACTION_2FA_ENABLED,
            self::ACTION_2FA_DISABLED, self::ACTION_RECOVERY_CODE_USED,
            self::ACTION_PASSKEY_REGISTERED, self::ACTION_PASSKEY_REVOKED,
            self::ACTION_MEMBER_CREATED, self::ACTION_MEMBER_UPDATED,
            self::ACTION_MEMBER_DELETED, self::ACTION_ROLE_CHANGED,
        ];

        $deviceActions = [
            self::ACTION_DEVICE_TRUSTED, self::ACTION_DEVICE_BLOCKED,
            self::ACTION_DEVICE_REMOVED,
        ];

        $securityActions = [
            self::ACTION_IP_BLOCKED, self::ACTION_IP_ALLOWED,
            self::ACTION_LOCKOUT_TRIGGERED, self::ACTION_LOCKOUT_RELEASED,
            self::ACTION_STEP_UP_AUTH_REQUIRED, self::ACTION_STEP_UP_AUTH_COMPLETED,
        ];

        $sessionActions = [
            self::ACTION_SESSION_CREATED, self::ACTION_SESSION_DESTROYED,
            self::ACTION_FORCED_LOGOUT,
        ];

        $extensionActions = [
            self::ACTION_PLUGIN_INSTALLED, self::ACTION_PLUGIN_ENABLED,
            self::ACTION_PLUGIN_DISABLED, self::ACTION_PLUGIN_UNINSTALLED,
            self::ACTION_PLUGIN_UPDATED, self::ACTION_THEME_INSTALLED,
            self::ACTION_THEME_ENABLED, self::ACTION_THEME_DISABLED,
            self::ACTION_THEME_UNINSTALLED, self::ACTION_THEME_UPDATED,
        ];

        if (in_array($action, $authActions)) return self::CATEGORY_AUTH;
        if (in_array($action, $accountActions)) return self::CATEGORY_ACCOUNT;
        if (in_array($action, $deviceActions)) return self::CATEGORY_DEVICE;
        if (in_array($action, $securityActions)) return self::CATEGORY_SECURITY;
        if (in_array($action, $sessionActions)) return self::CATEGORY_SESSION;
        if (in_array($action, $extensionActions)) return self::CATEGORY_EXTENSION;

        return self::CATEGORY_SYSTEM;
    }

    /**
     * アクションからデフォルトの重要度を取得
     */
    public static function getDefaultSeverity(string $action): string
    {
        $criticalActions = [
            self::ACTION_LOCKOUT_TRIGGERED,
        ];

        $warningActions = [
            self::ACTION_LOGIN_FAILED, self::ACTION_NEW_DEVICE_LOGIN,
            self::ACTION_PASSWORD_CHANGED, self::ACTION_EMAIL_CHANGED,
            self::ACTION_2FA_DISABLED, self::ACTION_IP_BLOCKED,
            self::ACTION_FORCED_LOGOUT, self::ACTION_DEVICE_BLOCKED,
            self::ACTION_MEMBER_DELETED,
        ];

        $noticeActions = [
            self::ACTION_2FA_ENABLED, self::ACTION_RECOVERY_CODE_USED,
            self::ACTION_PASSKEY_REGISTERED, self::ACTION_PASSKEY_REVOKED,
            self::ACTION_PLUGIN_INSTALLED, self::ACTION_PLUGIN_ENABLED,
            self::ACTION_PLUGIN_DISABLED, self::ACTION_THEME_INSTALLED,
            self::ACTION_THEME_ENABLED, self::ACTION_STEP_UP_AUTH_REQUIRED,
            self::ACTION_SETTINGS_UPDATED, self::ACTION_MEMBER_CREATED,
            self::ACTION_ROLE_CHANGED,
        ];

        if (in_array($action, $criticalActions)) return self::SEVERITY_CRITICAL;
        if (in_array($action, $warningActions)) return self::SEVERITY_WARNING;
        if (in_array($action, $noticeActions)) return self::SEVERITY_NOTICE;

        return self::SEVERITY_INFO;
    }

    /**
     * 重要度のCSSクラスを取得
     */
    public function getSeverityColorClass(): string
    {
        return match ($this->severity) {
            self::SEVERITY_EMERGENCY, self::SEVERITY_ALERT => 'text-red-800 bg-red-100',
            self::SEVERITY_CRITICAL, self::SEVERITY_ERROR => 'text-red-600 bg-red-50',
            self::SEVERITY_WARNING => 'text-yellow-600 bg-yellow-50',
            self::SEVERITY_NOTICE => 'text-blue-600 bg-blue-50',
            self::SEVERITY_INFO => 'text-gray-600 bg-gray-50',
            default => 'text-gray-500 bg-gray-50',
        };
    }

    /**
     * 結果のCSSクラスを取得
     */
    public function getOutcomeColorClass(): string
    {
        return match ($this->outcome) {
            self::OUTCOME_SUCCESS => 'text-green-600 bg-green-50',
            self::OUTCOME_FAILURE => 'text-red-600 bg-red-50',
            self::OUTCOME_DENIED => 'text-orange-600 bg-orange-50',
            self::OUTCOME_PENDING => 'text-yellow-600 bg-yellow-50',
            default => 'text-gray-600 bg-gray-50',
        };
    }
}
