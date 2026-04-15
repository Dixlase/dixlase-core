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

namespace App\Models;

use App\Enums\OperationRiskLevel;
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
        'actor_source',
        'is_ai_generated',
        'context',
        'schema_version',
        'record_hash',
        'previous_hash',
        'chain_sequence',
        'hash_algorithm',
        'verification_status',
        'last_verified_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'context' => 'array',
        'is_ai_generated' => 'boolean',
        'schema_version' => 'integer',
        'chain_sequence' => 'integer',
        'last_verified_at' => 'datetime',
    ];

    // ========================================
    // ハッシュチェーン検証ステータス定数
    // ========================================
    public const VERIFICATION_VALID = 'valid';

    public const VERIFICATION_INVALID = 'invalid';

    public const VERIFICATION_SKIPPED = 'skipped';

    // ハッシュアルゴリズム
    public const HASH_ALGORITHM = 'sha256';

    // 最初のレコードの previous_hash
    public const GENESIS_HASH = 'genesis';

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

    public const CATEGORY_AI = 'ai';

    // ========================================
    // Action（アクション）定数 - 認証関連
    // ========================================
    public const ACTION_LOGIN = 'login';

    public const ACTION_LOGOUT = 'logout';

    public const ACTION_LOGIN_FAILED = 'login_failed';

    public const ACTION_LOGIN_IDENTIFIER_CHECK = 'login_identifier_check';

    public const ACTION_LOGIN_IDENTIFIER_NOT_FOUND = 'login_identifier_not_found';

    public const ACTION_PASSKEY_AUTH_SUCCESS = 'passkey_auth_success';

    public const ACTION_PASSKEY_AUTH_FAILED = 'passkey_auth_failed';

    public const ACTION_NEW_DEVICE_LOGIN = 'new_device_login';

    public const ACTION_PASSWORD_CHANGED = 'password_changed';

    public const ACTION_PASSWORD_RESET = 'password_reset';

    public const ACTION_EMAIL_CHANGED = 'email_changed';

    // ========================================
    // Action（アクション）定数 - Two-FA関連
    // ========================================
    public const ACTION_TWO_FA_ENABLED = 'two_fa_enabled';

    public const ACTION_TWO_FA_DISABLED = 'two_fa_disabled';

    public const ACTION_TWO_FA_CODE_SENT = 'two_fa_code_sent';

    public const ACTION_TWO_FA_CODE_VERIFIED = 'two_fa_code_verified';

    public const ACTION_TWO_FA_CODE_FAILED = 'two_fa_code_failed';

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

    // サプライチェーン攻撃防御用のアクション
    public const ACTION_PLUGIN_SIGNING_KEY_CHANGED = 'plugin_signing_key_changed';

    public const ACTION_PLUGIN_AUTHOR_ID_CHANGED = 'plugin_author_id_changed';

    public const ACTION_PLUGIN_FILE_INTEGRITY_FAILED = 'plugin_file_integrity_failed';

    public const ACTION_PLUGIN_EXTERNAL_CALL_BLOCKED = 'plugin_external_call_blocked';

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
    // Action（アクション）定数 - AI操作関連
    // ========================================
    public const ACTION_AI_CONTENT_GENERATED = 'ai_content_generated';

    public const ACTION_AI_CONTENT_MODIFIED = 'ai_content_modified';

    public const ACTION_AI_SUGGESTION_APPLIED = 'ai_suggestion_applied';

    public const ACTION_AI_BULK_OPERATION = 'ai_bulk_operation';

    // ========================================
    // Action（アクション）定数 - AI/Bot攻撃検知
    // ========================================
    public const ACTION_BOT_LOGIN_DETECTED = 'bot_login_detected';

    public const ACTION_BOT_SCRAPING_DETECTED = 'bot_scraping_detected';

    public const ACTION_AI_RATE_LIMIT_HIT = 'ai_rate_limit_hit';

    // ========================================
    // Actor Source（操作元チャネル）定数
    // ========================================
    public const ACTOR_SOURCE_WEB = 'web';

    public const ACTOR_SOURCE_API = 'api';

    public const ACTOR_SOURCE_CLI = 'cli';

    public const ACTOR_SOURCE_SCHEDULER = 'scheduler';

    public const ACTOR_SOURCE_AI_PLUGIN = 'ai_plugin';

    public const ACTOR_SOURCE_WEBHOOK = 'webhook';

    public const ACTOR_SOURCE_QUEUE = 'queue';

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
     * @param  array  $data  ログデータ
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
            $actorName = $actorName ?? $data['actor']->display_name ?? $data['actor']->account_name ?? $data['actor']->name ?? $data['actor']->email ?? null;
        }

        // targetの処理
        $targetType = null;
        $targetId = null;
        $targetLabel = $data['target_label'] ?? null;

        if (isset($data['target']) && $data['target'] instanceof Model) {
            $targetType = get_class($data['target']);
            $targetId = $data['target']->getKey();
            $targetLabel = $targetLabel ?? $data['target']->display_name ?? $data['target']->account_name ?? $data['target']->name ?? $data['target']->title ?? null;
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
            'actor_source' => $data['actor_source'] ?? null,
            'is_ai_generated' => $data['is_ai_generated'] ?? false,
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

    /**
     * AI generated operations only
     */
    public function scopeAiGenerated($query)
    {
        return $query->where('is_ai_generated', true);
    }

    /**
     * Filter by actor source channel
     */
    public function scopeFromSource($query, string $source)
    {
        return $query->where('actor_source', $source);
    }

    /**
     * Bot/AI attack related operations
     */
    public function scopeBotRelated($query)
    {
        return $query->whereIn('action', [
            self::ACTION_BOT_LOGIN_DETECTED,
            self::ACTION_BOT_SCRAPING_DETECTED,
            self::ACTION_AI_RATE_LIMIT_HIT,
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
            self::ACTION_LOGIN_IDENTIFIER_CHECK, self::ACTION_PASSKEY_AUTH_SUCCESS,
            self::ACTION_PASSKEY_AUTH_FAILED, self::ACTION_NEW_DEVICE_LOGIN,
            self::ACTION_TWO_FA_CODE_SENT, self::ACTION_TWO_FA_CODE_VERIFIED,
            self::ACTION_TWO_FA_CODE_FAILED,
        ];

        $accountActions = [
            self::ACTION_PASSWORD_CHANGED, self::ACTION_PASSWORD_RESET,
            self::ACTION_EMAIL_CHANGED, self::ACTION_TWO_FA_ENABLED,
            self::ACTION_TWO_FA_DISABLED, self::ACTION_RECOVERY_CODE_USED,
            self::ACTION_PASSKEY_REGISTERED, self::ACTION_PASSKEY_REVOKED,
            self::ACTION_MEMBER_CREATED, self::ACTION_MEMBER_UPDATED,
            self::ACTION_MEMBER_DELETED, self::ACTION_ROLE_CHANGED,
        ];

        $deviceActions = [
            self::ACTION_DEVICE_TRUSTED, self::ACTION_DEVICE_BLOCKED,
            self::ACTION_DEVICE_REMOVED,
        ];

        $securityActions = [
            self::ACTION_LOGIN_IDENTIFIER_NOT_FOUND, self::ACTION_IP_BLOCKED,
            self::ACTION_IP_ALLOWED, self::ACTION_LOCKOUT_TRIGGERED,
            self::ACTION_LOCKOUT_RELEASED, self::ACTION_STEP_UP_AUTH_REQUIRED,
            self::ACTION_STEP_UP_AUTH_COMPLETED,
            self::ACTION_BOT_LOGIN_DETECTED, self::ACTION_BOT_SCRAPING_DETECTED,
            self::ACTION_AI_RATE_LIMIT_HIT,
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

        if (in_array($action, $authActions)) {
            return self::CATEGORY_AUTH;
        }
        if (in_array($action, $accountActions)) {
            return self::CATEGORY_ACCOUNT;
        }
        if (in_array($action, $deviceActions)) {
            return self::CATEGORY_DEVICE;
        }
        if (in_array($action, $securityActions)) {
            return self::CATEGORY_SECURITY;
        }
        if (in_array($action, $sessionActions)) {
            return self::CATEGORY_SESSION;
        }
        if (in_array($action, $extensionActions)) {
            return self::CATEGORY_EXTENSION;
        }

        $aiActions = [
            self::ACTION_AI_CONTENT_GENERATED, self::ACTION_AI_CONTENT_MODIFIED,
            self::ACTION_AI_SUGGESTION_APPLIED, self::ACTION_AI_BULK_OPERATION,
        ];

        if (in_array($action, $aiActions)) {
            return self::CATEGORY_AI;
        }

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
            self::ACTION_LOGIN_FAILED, self::ACTION_LOGIN_IDENTIFIER_NOT_FOUND,
            self::ACTION_PASSKEY_AUTH_FAILED, self::ACTION_NEW_DEVICE_LOGIN,
            self::ACTION_PASSWORD_CHANGED, self::ACTION_EMAIL_CHANGED,
            self::ACTION_TWO_FA_DISABLED, self::ACTION_IP_BLOCKED,
            self::ACTION_FORCED_LOGOUT, self::ACTION_DEVICE_BLOCKED,
            self::ACTION_MEMBER_DELETED,
            self::ACTION_BOT_LOGIN_DETECTED, self::ACTION_BOT_SCRAPING_DETECTED,
            self::ACTION_AI_RATE_LIMIT_HIT, self::ACTION_AI_BULK_OPERATION,
        ];

        $noticeActions = [
            self::ACTION_PASSKEY_AUTH_SUCCESS, self::ACTION_TWO_FA_ENABLED,
            self::ACTION_RECOVERY_CODE_USED, self::ACTION_PASSKEY_REGISTERED,
            self::ACTION_PASSKEY_REVOKED, self::ACTION_PLUGIN_INSTALLED,
            self::ACTION_PLUGIN_ENABLED, self::ACTION_PLUGIN_DISABLED,
            self::ACTION_THEME_INSTALLED, self::ACTION_THEME_ENABLED,
            self::ACTION_STEP_UP_AUTH_REQUIRED, self::ACTION_SETTINGS_UPDATED,
            self::ACTION_MEMBER_CREATED, self::ACTION_ROLE_CHANGED,
        ];

        if (in_array($action, $criticalActions)) {
            return self::SEVERITY_CRITICAL;
        }
        if (in_array($action, $warningActions)) {
            return self::SEVERITY_WARNING;
        }
        if (in_array($action, $noticeActions)) {
            return self::SEVERITY_NOTICE;
        }

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

    // ========================================
    // 操作リスクレベル判定（β版 強制再認証の基盤）
    // ========================================

    /**
     * アクションからリスクレベルを取得
     */
    public static function getRiskLevelForAction(string $action): OperationRiskLevel
    {
        // Critical: システム設定・セキュリティ設定・APIキー操作
        $criticalActions = [
            self::ACTION_SETTINGS_UPDATED,      // 設定変更（セキュリティ設定含む）
            self::ACTION_IP_BLOCKED,            // IPブロック
            self::ACTION_IP_ALLOWED,            // IP許可
            self::ACTION_LOCKOUT_RELEASED,      // ロックアウト解除
            self::ACTION_PLUGIN_INSTALLED,      // プラグインインストール
            self::ACTION_PLUGIN_UNINSTALLED,    // プラグインアンインストール
            self::ACTION_THEME_INSTALLED,       // テーマインストール
            self::ACTION_THEME_UNINSTALLED,     // テーマアンインストール
        ];

        // High: 削除・重要設定変更・AI一括操作
        $highActions = [
            self::ACTION_MEMBER_DELETED,        // メンバー削除
            self::ACTION_FORCED_LOGOUT,         // 強制ログアウト
            self::ACTION_TWO_FA_DISABLED,          // 2FA無効化
            self::ACTION_DEVICE_BLOCKED,        // デバイスブロック
            self::ACTION_DEVICE_REMOVED,        // デバイス削除
            self::ACTION_PASSKEY_REVOKED,       // Passkey無効化
            self::ACTION_PLUGIN_DISABLED,       // プラグイン無効化
            self::ACTION_THEME_DISABLED,        // テーマ無効化
            self::ACTION_ROLE_CHANGED,          // 権限変更
            self::ACTION_AI_BULK_OPERATION,     // AI一括操作
        ];

        // Medium: 編集・更新
        $mediumActions = [
            self::ACTION_PASSWORD_CHANGED,      // パスワード変更
            self::ACTION_EMAIL_CHANGED,         // メールアドレス変更
            self::ACTION_TWO_FA_ENABLED,           // 2FA有効化
            self::ACTION_DEVICE_TRUSTED,        // デバイス信頼
            self::ACTION_PASSKEY_REGISTERED,    // Passkey登録
            self::ACTION_MEMBER_CREATED,        // メンバー作成
            self::ACTION_MEMBER_UPDATED,        // メンバー更新
            self::ACTION_PLUGIN_ENABLED,        // プラグイン有効化
            self::ACTION_PLUGIN_UPDATED,        // プラグイン更新
            self::ACTION_THEME_ENABLED,         // テーマ有効化
            self::ACTION_THEME_UPDATED,         // テーマ更新
        ];

        if (in_array($action, $criticalActions)) {
            return OperationRiskLevel::Critical;
        }
        if (in_array($action, $highActions)) {
            return OperationRiskLevel::High;
        }
        if (in_array($action, $mediumActions)) {
            return OperationRiskLevel::Medium;
        }

        return OperationRiskLevel::Low;
    }

    /**
     * このログのリスクレベルを取得
     */
    public function getRiskLevel(): OperationRiskLevel
    {
        return self::getRiskLevelForAction($this->action);
    }

    /**
     * このログが危険な操作かどうか
     */
    public function isDangerousOperation(): bool
    {
        return $this->getRiskLevel()->isDangerous();
    }

    /**
     * このログがクリティカルな操作かどうか
     */
    public function isCriticalOperation(): bool
    {
        return $this->getRiskLevel()->isCritical();
    }

    /**
     * このログが再認証を必要とする操作かどうか（β版で実装予定）
     */
    public function requiresStepUpAuth(): bool
    {
        return $this->getRiskLevel()->requiresStepUpAuth();
    }

    /**
     * リスクレベルのCSSクラスを取得
     */
    public function getRiskLevelColorClass(): string
    {
        return $this->getRiskLevel()->colorClass();
    }

    /**
     * リスクレベルのバッジクラスを取得
     */
    public function getRiskLevelBadgeClass(): string
    {
        return $this->getRiskLevel()->badgeClass();
    }

    // ========================================
    // スコープ（リスクレベル用）
    // ========================================

    /**
     * 危険な操作のみ取得
     */
    public function scopeDangerousOperations($query)
    {
        $dangerousActions = [];
        foreach (self::cases() as $action) {
            if (self::getRiskLevelForAction($action)->isDangerous()) {
                $dangerousActions[] = $action;
            }
        }

        // 定義済みアクションから危険なものを抽出
        $allActions = [
            self::ACTION_LOGIN, self::ACTION_LOGOUT, self::ACTION_LOGIN_FAILED,
            self::ACTION_NEW_DEVICE_LOGIN, self::ACTION_PASSWORD_CHANGED,
            self::ACTION_PASSWORD_RESET, self::ACTION_EMAIL_CHANGED,
            self::ACTION_TWO_FA_ENABLED, self::ACTION_TWO_FA_DISABLED,
            self::ACTION_TWO_FA_CODE_SENT, self::ACTION_TWO_FA_CODE_VERIFIED,
            self::ACTION_TWO_FA_CODE_FAILED, self::ACTION_RECOVERY_CODE_USED,
            self::ACTION_PASSKEY_REGISTERED, self::ACTION_PASSKEY_REVOKED,
            self::ACTION_DEVICE_TRUSTED, self::ACTION_DEVICE_BLOCKED,
            self::ACTION_DEVICE_REMOVED, self::ACTION_IP_BLOCKED,
            self::ACTION_IP_ALLOWED, self::ACTION_LOCKOUT_TRIGGERED,
            self::ACTION_LOCKOUT_RELEASED, self::ACTION_STEP_UP_AUTH_REQUIRED,
            self::ACTION_STEP_UP_AUTH_COMPLETED, self::ACTION_SESSION_CREATED,
            self::ACTION_SESSION_DESTROYED, self::ACTION_FORCED_LOGOUT,
            self::ACTION_PLUGIN_INSTALLED, self::ACTION_PLUGIN_ENABLED,
            self::ACTION_PLUGIN_DISABLED, self::ACTION_PLUGIN_UNINSTALLED,
            self::ACTION_PLUGIN_UPDATED, self::ACTION_THEME_INSTALLED,
            self::ACTION_THEME_ENABLED, self::ACTION_THEME_DISABLED,
            self::ACTION_THEME_UNINSTALLED, self::ACTION_THEME_UPDATED,
            self::ACTION_SETTINGS_UPDATED, self::ACTION_MEMBER_CREATED,
            self::ACTION_MEMBER_UPDATED, self::ACTION_MEMBER_DELETED,
            self::ACTION_ROLE_CHANGED,
        ];

        $dangerous = array_filter($allActions, function ($action) {
            return self::getRiskLevelForAction($action)->isDangerous();
        });

        return $query->whereIn('action', $dangerous);
    }

    /**
     * クリティカルな操作のみ取得
     */
    public function scopeCriticalOperations($query)
    {
        $allActions = [
            self::ACTION_SETTINGS_UPDATED, self::ACTION_IP_BLOCKED,
            self::ACTION_IP_ALLOWED, self::ACTION_LOCKOUT_RELEASED,
            self::ACTION_PLUGIN_INSTALLED, self::ACTION_PLUGIN_UNINSTALLED,
            self::ACTION_THEME_INSTALLED, self::ACTION_THEME_UNINSTALLED,
        ];

        return $query->whereIn('action', $allActions);
    }

    /**
     * 指定リスクレベル以上の操作を取得
     */
    public function scopeWithMinRiskLevel($query, OperationRiskLevel $minLevel)
    {
        $allActions = [
            self::ACTION_LOGIN, self::ACTION_LOGOUT, self::ACTION_LOGIN_FAILED,
            self::ACTION_NEW_DEVICE_LOGIN, self::ACTION_PASSWORD_CHANGED,
            self::ACTION_PASSWORD_RESET, self::ACTION_EMAIL_CHANGED,
            self::ACTION_TWO_FA_ENABLED, self::ACTION_TWO_FA_DISABLED,
            self::ACTION_TWO_FA_CODE_SENT, self::ACTION_TWO_FA_CODE_VERIFIED,
            self::ACTION_TWO_FA_CODE_FAILED, self::ACTION_RECOVERY_CODE_USED,
            self::ACTION_PASSKEY_REGISTERED, self::ACTION_PASSKEY_REVOKED,
            self::ACTION_DEVICE_TRUSTED, self::ACTION_DEVICE_BLOCKED,
            self::ACTION_DEVICE_REMOVED, self::ACTION_IP_BLOCKED,
            self::ACTION_IP_ALLOWED, self::ACTION_LOCKOUT_TRIGGERED,
            self::ACTION_LOCKOUT_RELEASED, self::ACTION_STEP_UP_AUTH_REQUIRED,
            self::ACTION_STEP_UP_AUTH_COMPLETED, self::ACTION_SESSION_CREATED,
            self::ACTION_SESSION_DESTROYED, self::ACTION_FORCED_LOGOUT,
            self::ACTION_PLUGIN_INSTALLED, self::ACTION_PLUGIN_ENABLED,
            self::ACTION_PLUGIN_DISABLED, self::ACTION_PLUGIN_UNINSTALLED,
            self::ACTION_PLUGIN_UPDATED, self::ACTION_THEME_INSTALLED,
            self::ACTION_THEME_ENABLED, self::ACTION_THEME_DISABLED,
            self::ACTION_THEME_UNINSTALLED, self::ACTION_THEME_UPDATED,
            self::ACTION_SETTINGS_UPDATED, self::ACTION_MEMBER_CREATED,
            self::ACTION_MEMBER_UPDATED, self::ACTION_MEMBER_DELETED,
            self::ACTION_ROLE_CHANGED,
        ];

        $filtered = array_filter($allActions, function ($action) use ($minLevel) {
            return self::getRiskLevelForAction($action)->value >= $minLevel->value;
        });

        return $query->whereIn('action', $filtered);
    }

    // ========================================
    // ハッシュチェーン機能
    // ========================================

    /**
     * このレコードのハッシュを計算
     *
     * @param  string|null  $previousHash  前レコードのハッシュ
     * @return string SHA-256ハッシュ（64文字）
     */
    public function calculateHash(?string $previousHash = null): string
    {
        $previousHash = $previousHash ?? $this->previous_hash ?? self::GENESIS_HASH;

        // ハッシュ計算対象のデータを構築
        $data = implode('|', [
            $this->id,
            $this->occurred_at?->toIso8601String() ?? '',
            $this->severity ?? '',
            $this->outcome ?? '',
            $this->category ?? '',
            $this->action ?? '',
            $this->actor_type ?? '',
            $this->actor_id ?? '',
            $this->target_type ?? '',
            $this->target_id ?? '',
            $this->ip_address ?? '',
            json_encode($this->context ?? []),
            $previousHash,
        ]);

        return hash(self::HASH_ALGORITHM, $data);
    }

    /**
     * ハッシュチェーンを設定してレコードを保存
     */
    public function saveWithHashChain(): bool
    {
        // 前のレコードを取得
        $previousLog = self::where('id', '<', $this->id)
            ->whereNotNull('record_hash')
            ->orderBy('id', 'desc')
            ->first();

        // previous_hashを設定
        $this->previous_hash = $previousLog?->record_hash ?? self::GENESIS_HASH;

        // chain_sequenceを設定
        $this->chain_sequence = $previousLog ? ($previousLog->chain_sequence + 1) : 1;

        // record_hashを計算
        $this->record_hash = $this->calculateHash($this->previous_hash);

        // hash_algorithmを設定
        $this->hash_algorithm = self::HASH_ALGORITHM;

        return $this->save();
    }

    /**
     * このレコードのハッシュを検証
     */
    public function verifyHash(): bool
    {
        if (empty($this->record_hash)) {
            return false;
        }

        $expectedHash = $this->calculateHash($this->previous_hash);

        return hash_equals($expectedHash, $this->record_hash);
    }

    /**
     * このレコードのチェーンリンクを検証（前レコードとの整合性）
     */
    public function verifyChainLink(): bool
    {
        // 最初のレコードの場合
        if ($this->previous_hash === self::GENESIS_HASH) {
            return $this->chain_sequence === 1;
        }

        // 前のレコードを取得
        $previousLog = self::where('record_hash', $this->previous_hash)->first();

        if (! $previousLog) {
            return false;
        }

        // シーケンスの連続性を確認
        return $previousLog->chain_sequence === ($this->chain_sequence - 1);
    }

    /**
     * 検証ステータスを更新
     */
    public function markAsVerified(bool $isValid): void
    {
        $this->update([
            'verification_status' => $isValid ? self::VERIFICATION_VALID : self::VERIFICATION_INVALID,
            'last_verified_at' => now(),
        ]);
    }

    /**
     * ハッシュチェーンが有効かどうか
     */
    public function hasValidHashChain(): bool
    {
        return ! empty($this->record_hash) && ! empty($this->previous_hash);
    }

    /**
     * 検証済みかどうか
     */
    public function isVerified(): bool
    {
        return $this->verification_status === self::VERIFICATION_VALID;
    }

    /**
     * 改ざんが検知されたかどうか
     */
    public function isTampered(): bool
    {
        return $this->verification_status === self::VERIFICATION_INVALID;
    }

    // ========================================
    // スコープ（ハッシュチェーン用）
    // ========================================

    /**
     * ハッシュチェーンが設定されているレコード
     */
    public function scopeWithHashChain($query)
    {
        return $query->whereNotNull('record_hash');
    }

    /**
     * ハッシュチェーンが未設定のレコード
     */
    public function scopeWithoutHashChain($query)
    {
        return $query->whereNull('record_hash');
    }

    /**
     * 検証済みレコード
     */
    public function scopeVerified($query)
    {
        return $query->where('verification_status', self::VERIFICATION_VALID);
    }

    /**
     * 改ざん検知されたレコード
     */
    public function scopeTampered($query)
    {
        return $query->where('verification_status', self::VERIFICATION_INVALID);
    }

    /**
     * 未検証レコード
     */
    public function scopeUnverified($query)
    {
        return $query->whereNull('verification_status');
    }

    // ========================================
    // 静的ヘルパー（ハッシュチェーン用）
    // ========================================

    /**
     * 最新のハッシュを取得
     */
    public static function getLatestHash(): ?string
    {
        return self::whereNotNull('record_hash')
            ->orderBy('id', 'desc')
            ->value('record_hash');
    }

    /**
     * 最新のシーケンス番号を取得
     */
    public static function getLatestSequence(): int
    {
        return (int) self::whereNotNull('chain_sequence')
            ->orderBy('id', 'desc')
            ->value('chain_sequence') ?? 0;
    }

    /**
     * ハッシュチェーン付きでログを記録
     */
    public static function logWithHashChain(array $data): self
    {
        $log = self::log($data);
        $log->saveWithHashChain();

        return $log;
    }
}
