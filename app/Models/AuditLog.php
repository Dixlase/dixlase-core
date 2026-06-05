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

namespace App\Models;

use App\Enums\OperationRiskLevel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

/**
 * Audit log model
 *
 * Records who / when / from where / on what / did what
 */
class AuditLog extends Model
{
    protected $table = 'audit_logs';

    protected $fillable = [
        'occurred_at',
        'severity',
        'outcome',
        'site_id',
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
    // Hash chain verification status constants
    // ========================================
    public const VERIFICATION_VALID = 'valid';

    public const VERIFICATION_INVALID = 'invalid';

    public const VERIFICATION_SKIPPED = 'skipped';

    // Hash algorithm
    public const HASH_ALGORITHM = 'sha256';

    // previous_hash for the first record
    public const GENESIS_HASH = 'genesis';

    // ========================================
    // Severity constants
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
    // Outcome constants
    // ========================================
    public const OUTCOME_SUCCESS = 'success';

    public const OUTCOME_FAILURE = 'failure';

    public const OUTCOME_DENIED = 'denied';

    public const OUTCOME_PENDING = 'pending';

    public const OUTCOME_UNKNOWN = 'unknown';

    // ========================================
    // Category constants
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
    // Action constants - Authentication related
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
    // Action constants - Two-FA related
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
    // Action constants - Device related
    // ========================================
    public const ACTION_DEVICE_TRUSTED = 'device_trusted';

    public const ACTION_DEVICE_BLOCKED = 'device_blocked';

    public const ACTION_DEVICE_REMOVED = 'device_removed';

    // ========================================
    // Action constants - Security related
    // ========================================
    public const ACTION_IP_BLOCKED = 'ip_blocked';

    public const ACTION_IP_ALLOWED = 'ip_allowed';

    public const ACTION_LOCKOUT_TRIGGERED = 'lockout_triggered';

    public const ACTION_LOCKOUT_RELEASED = 'lockout_released';

    public const ACTION_STEP_UP_AUTH_REQUIRED = 'step_up_auth_required';

    public const ACTION_STEP_UP_AUTH_COMPLETED = 'step_up_auth_completed';

    // ========================================
    // Action constants - Session related
    // ========================================
    public const ACTION_SESSION_CREATED = 'session_created';

    public const ACTION_SESSION_DESTROYED = 'session_destroyed';

    public const ACTION_FORCED_LOGOUT = 'forced_logout';

    // ========================================
    // Action constants - Extension related
    // ========================================
    public const ACTION_PLUGIN_INSTALLED = 'plugin_installed';

    public const ACTION_PLUGIN_ENABLED = 'plugin_enabled';

    public const ACTION_PLUGIN_DISABLED = 'plugin_disabled';

    public const ACTION_PLUGIN_UNINSTALLED = 'plugin_uninstalled';

    public const ACTION_PLUGIN_UPDATED = 'plugin_updated';

    // Actions for supply chain attack defense
    public const ACTION_PLUGIN_SIGNING_KEY_CHANGED = 'plugin_signing_key_changed';

    public const ACTION_PLUGIN_AUTHOR_ID_CHANGED = 'plugin_author_id_changed';

    public const ACTION_PLUGIN_FILE_INTEGRITY_FAILED = 'plugin_file_integrity_failed';

    public const ACTION_PLUGIN_EXTERNAL_CALL_BLOCKED = 'plugin_external_call_blocked';

    // Signature trust-management actions (plugin / theme / core)
    public const ACTION_SIGNATURE_REMOVED = 'signature_removed';

    public const ACTION_SIGNATURE_WAIVED = 'signature_waived';

    public const ACTION_SIGNATURE_WAIVER_REVOKED = 'signature_waiver_revoked';

    public const ACTION_THEME_INSTALLED = 'theme_installed';

    public const ACTION_THEME_ENABLED = 'theme_enabled';

    public const ACTION_THEME_DISABLED = 'theme_disabled';

    public const ACTION_THEME_UNINSTALLED = 'theme_uninstalled';

    public const ACTION_THEME_UPDATED = 'theme_updated';

    // Theme supply-chain attack defense actions (mirror plugin equivalents)
    public const ACTION_THEME_SIGNING_KEY_CHANGED = 'theme_signing_key_changed';

    public const ACTION_THEME_AUTHOR_ID_CHANGED = 'theme_author_id_changed';

    public const ACTION_THEME_FILE_INTEGRITY_FAILED = 'theme_file_integrity_failed';

    // ========================================
    // Action constants - Backup related
    // ========================================
    public const ACTION_BACKUP_CREATED = 'backup_created';

    public const ACTION_BACKUP_FAILED = 'backup_failed';

    public const ACTION_BACKUP_DELETED = 'backup_deleted';

    public const ACTION_BACKUP_RESTORED = 'backup_restored';

    public const ACTION_BACKUP_RESTORE_FAILED = 'backup_restore_failed';

    public const ACTION_BACKUP_ROLLED_BACK = 'backup_rolled_back';

    // ========================================
    // Action constants - settings related
    // ========================================
    public const ACTION_SETTINGS_UPDATED = 'settings_updated';

    public const ACTION_MEMBER_CREATED = 'member_created';

    public const ACTION_MEMBER_UPDATED = 'member_updated';

    public const ACTION_MEMBER_DELETED = 'member_deleted';

    public const ACTION_ROLE_CHANGED = 'role_changed';

    // ========================================
    // Action constants - AI operation related
    // ========================================
    public const ACTION_AI_CONTENT_GENERATED = 'ai_content_generated';

    public const ACTION_AI_CONTENT_MODIFIED = 'ai_content_modified';

    public const ACTION_AI_SUGGESTION_APPLIED = 'ai_suggestion_applied';

    public const ACTION_AI_BULK_OPERATION = 'ai_bulk_operation';

    // ========================================
    // Action constants - AI/Bot attack detection
    // ========================================
    public const ACTION_BOT_LOGIN_DETECTED = 'bot_login_detected';

    public const ACTION_BOT_SCRAPING_DETECTED = 'bot_scraping_detected';

    public const ACTION_AI_RATE_LIMIT_HIT = 'ai_rate_limit_hit';

    // ========================================
    // Actor Source constants
    // ========================================
    public const ACTOR_SOURCE_WEB = 'web';

    public const ACTOR_SOURCE_API = 'api';

    public const ACTOR_SOURCE_CLI = 'cli';

    public const ACTOR_SOURCE_SCHEDULER = 'scheduler';

    public const ACTOR_SOURCE_AI_PLUGIN = 'ai_plugin';

    public const ACTOR_SOURCE_WEBHOOK = 'webhook';

    public const ACTOR_SOURCE_QUEUE = 'queue';

    // ========================================
    // Relations
    // ========================================

    /**
     * Actor (Polymorphic)
     */
    public function actor(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Target (Polymorphic)
     */
    public function target(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Site this event belongs to.
     *
     * Nullable: system / network-wide events leave site_id null. The
     * BelongsToSite global scope is intentionally not applied so admin
     * dashboards can show network-wide and site-scoped events together.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    // ========================================
    // Unified Log API
    // ========================================

    /**
     * Record audit log (Main API)
     *
     * @param  array  $data  Log data
     */
    public static function log(array $data): self
    {
        // Auto-retrieve request context
        $request = request();

        // Generate request_id if not present
        $requestId = $data['request_id'] ?? $request->header('X-Request-ID') ?? (string) Str::uuid();

        // Process actor
        $actorType = null;
        $actorId = null;
        $actorName = $data['actor_name'] ?? null;

        if (isset($data['actor']) && $data['actor'] instanceof Model) {
            $actorType = get_class($data['actor']);
            $actorId = $data['actor']->getKey();
            $actorName = $actorName ?? $data['actor']->display_name ?? $data['actor']->account_name ?? $data['actor']->name ?? $data['actor']->email ?? null;
        }

        // Process target
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
     * Record authentication log
     */
    public static function logAuth(string $action, array $data = []): self
    {
        return self::log(array_merge($data, [
            'category' => self::CATEGORY_AUTH,
            'action' => $action,
        ]));
    }

    /**
     * Record security log
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
     * Record extension log
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
     * Record account log
     */
    public static function logAccount(string $action, array $data = []): self
    {
        return self::log(array_merge($data, [
            'category' => self::CATEGORY_ACCOUNT,
            'action' => $action,
        ]));
    }

    /**
     * Record system log
     */
    public static function logSystem(string $action, array $data = []): self
    {
        return self::log(array_merge($data, [
            'category' => self::CATEGORY_SYSTEM,
            'action' => $action,
        ]));
    }

    // ========================================
    // Scopes
    // ========================================

    /**
     * Filter by actor
     */
    public function scopeForActor($query, Model $actor)
    {
        return $query->where('actor_type', get_class($actor))
            ->where('actor_id', $actor->getKey());
    }

    /**
     * Filter by target
     */
    public function scopeForTarget($query, Model $target)
    {
        return $query->where('target_type', get_class($target))
            ->where('target_id', $target->getKey());
    }

    /**
     * Filter by category
     */
    public function scopeInCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Filter by action
     */
    public function scopeWithAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Filter by severity
     */
    public function scopeWithSeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Filter by result
     */
    public function scopeWithOutcome($query, string $outcome)
    {
        return $query->where('outcome', $outcome);
    }

    /**
     * Filter by plugin
     */
    public function scopeForPlugin($query, string $pluginName)
    {
        return $query->where('plugin_name', $pluginName);
    }

    /**
     * Filter by request ID (get related logs within a single request)
     */
    public function scopeForRequest($query, string $requestId)
    {
        return $query->where('request_id', $requestId);
    }

    /**
     * Filter by period
     */
    public function scopeOccurredBetween($query, $start, $end)
    {
        return $query->whereBetween('occurred_at', [$start, $end]);
    }

    /**
     * Get recent logs
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('occurred_at', '>=', now()->subHours($hours));
    }

    /**
     * Severity of warning or higher
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
     * Failed events
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
    // Helper methods
    // ========================================

    /**
     * Infer default category from action
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
     * Get default severity from action
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
            self::ACTION_BACKUP_FAILED, self::ACTION_BACKUP_RESTORED,
            self::ACTION_BACKUP_RESTORE_FAILED, self::ACTION_BACKUP_ROLLED_BACK,
        ];

        $noticeActions = [
            self::ACTION_PASSKEY_AUTH_SUCCESS, self::ACTION_TWO_FA_ENABLED,
            self::ACTION_RECOVERY_CODE_USED, self::ACTION_PASSKEY_REGISTERED,
            self::ACTION_PASSKEY_REVOKED, self::ACTION_PLUGIN_INSTALLED,
            self::ACTION_PLUGIN_ENABLED, self::ACTION_PLUGIN_DISABLED,
            self::ACTION_THEME_INSTALLED, self::ACTION_THEME_ENABLED,
            self::ACTION_STEP_UP_AUTH_REQUIRED, self::ACTION_SETTINGS_UPDATED,
            self::ACTION_MEMBER_CREATED, self::ACTION_ROLE_CHANGED,
            self::ACTION_BACKUP_CREATED, self::ACTION_BACKUP_DELETED,
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
     * Get CSS class for severity
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
     * Get CSS class for result
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
    // Operation risk level determination (beta version, foundation for forced re-authentication)
    // ========================================

    /**
     * Get risk level from action
     */
    public static function getRiskLevelForAction(string $action): OperationRiskLevel
    {
        // Critical: system settings, security settings, API key operations
        $criticalActions = [
            self::ACTION_SETTINGS_UPDATED,      // Settings changes (including security settings)
            self::ACTION_IP_BLOCKED,            // IP block
            self::ACTION_IP_ALLOWED,            // IP allow
            self::ACTION_LOCKOUT_RELEASED,      // Unlock lockout
            self::ACTION_PLUGIN_INSTALLED,      // Plugin installation
            self::ACTION_PLUGIN_UNINSTALLED,    // Plugin uninstallation
            self::ACTION_THEME_INSTALLED,       // Theme installation
            self::ACTION_THEME_UNINSTALLED,     // Theme uninstallation
        ];

        // High: Deletion, critical settings changes, AI batch operations
        $highActions = [
            self::ACTION_MEMBER_DELETED,        // Member deletion
            self::ACTION_FORCED_LOGOUT,         // Force logout
            self::ACTION_TWO_FA_DISABLED,          // 2FA disabled
            self::ACTION_DEVICE_BLOCKED,        // Device blocked
            self::ACTION_DEVICE_REMOVED,        // Device deleted
            self::ACTION_PASSKEY_REVOKED,       // Passkey disabled
            self::ACTION_PLUGIN_DISABLED,       // Plugin disabled
            self::ACTION_THEME_DISABLED,        // Theme disabled
            self::ACTION_ROLE_CHANGED,          // Permission changed
            self::ACTION_AI_BULK_OPERATION,     // AI bulk operation
        ];

        // Medium: Edit, update
        $mediumActions = [
            self::ACTION_PASSWORD_CHANGED,      // Password changed
            self::ACTION_EMAIL_CHANGED,         // Email address changed
            self::ACTION_TWO_FA_ENABLED,           // 2FA enabled
            self::ACTION_DEVICE_TRUSTED,        // Device trusted
            self::ACTION_PASSKEY_REGISTERED,    // Passkey registered
            self::ACTION_MEMBER_CREATED,        // Member created
            self::ACTION_MEMBER_UPDATED,        // Member update
            self::ACTION_PLUGIN_ENABLED,        // Plugin activation
            self::ACTION_PLUGIN_UPDATED,        // Plugin update
            self::ACTION_THEME_ENABLED,         // Theme activation
            self::ACTION_THEME_UPDATED,         // Theme update
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
     * Get the risk level of this log
     */
    public function getRiskLevel(): OperationRiskLevel
    {
        return self::getRiskLevelForAction($this->action);
    }

    /**
     * Whether this log is a dangerous operation
     */
    public function isDangerousOperation(): bool
    {
        return $this->getRiskLevel()->isDangerous();
    }

    /**
     * Whether this log is a critical operation
     */
    public function isCriticalOperation(): bool
    {
        return $this->getRiskLevel()->isCritical();
    }

    /**
     * Whether this log requires re-authentication (planned for beta)
     */
    public function requiresStepUpAuth(): bool
    {
        return $this->getRiskLevel()->requiresStepUpAuth();
    }

    /**
     * Get CSS class for risk level
     */
    public function getRiskLevelColorClass(): string
    {
        return $this->getRiskLevel()->colorClass();
    }

    /**
     * Get badge class for risk level
     */
    public function getRiskLevelBadgeClass(): string
    {
        return $this->getRiskLevel()->badgeClass();
    }

    // ========================================
    // Scope (for risk level)
    // ========================================

    /**
     * Get only dangerous operations
     */
    public function scopeDangerousOperations($query)
    {
        $dangerousActions = [];
        foreach (self::cases() as $action) {
            if (self::getRiskLevelForAction($action)->isDangerous()) {
                $dangerousActions[] = $action;
            }
        }

        // Extract dangerous operations from defined actions
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
     * Get only critical operations
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
     * Get operations above specified risk level
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
    // Hash chain functionality
    // ========================================

    /**
     * Calculate hash of this record
     *
     * @param  string|null  $previousHash  Hash of previous record
     * @return string SHA-256 hash (64 characters)
     */
    public function calculateHash(?string $previousHash = null): string
    {
        $previousHash = $previousHash ?? $this->previous_hash ?? self::GENESIS_HASH;

        // Build data for hash calculation
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
     * Set up hash chain and save record
     */
    public function saveWithHashChain(): bool
    {
        // Get previous record
        $previousLog = self::where('id', '<', $this->id)
            ->whereNotNull('record_hash')
            ->orderBy('id', 'desc')
            ->first();

        // Set previous_hash
        $this->previous_hash = $previousLog?->record_hash ?? self::GENESIS_HASH;

        // Set chain_sequence
        $this->chain_sequence = $previousLog ? ($previousLog->chain_sequence + 1) : 1;

        // Calculate record_hash
        $this->record_hash = $this->calculateHash($this->previous_hash);

        // Set hash_algorithm
        $this->hash_algorithm = self::HASH_ALGORITHM;

        return $this->save();
    }

    /**
     * Verify hash of this record
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
     * Verify chain link of this record (consistency with previous record)
     */
    public function verifyChainLink(): bool
    {
        // If this is the first record
        if ($this->previous_hash === self::GENESIS_HASH) {
            return $this->chain_sequence === 1;
        }

        // Get previous record
        $previousLog = self::where('record_hash', $this->previous_hash)->first();

        if (! $previousLog) {
            return false;
        }

        // Check sequence continuity
        return $previousLog->chain_sequence === ($this->chain_sequence - 1);
    }

    /**
     * Update verification status
     */
    public function markAsVerified(bool $isValid): void
    {
        $this->update([
            'verification_status' => $isValid ? self::VERIFICATION_VALID : self::VERIFICATION_INVALID,
            'last_verified_at' => now(),
        ]);
    }

    /**
     * Whether hash chain is valid
     */
    public function hasValidHashChain(): bool
    {
        return ! empty($this->record_hash) && ! empty($this->previous_hash);
    }

    /**
     * Whether verified
     */
    public function isVerified(): bool
    {
        return $this->verification_status === self::VERIFICATION_VALID;
    }

    /**
     * Whether tampering was detected
     */
    public function isTampered(): bool
    {
        return $this->verification_status === self::VERIFICATION_INVALID;
    }

    // ========================================
    // Scope (for hash chain)
    // ========================================

    /**
     * Records with hash chain set
     */
    public function scopeWithHashChain($query)
    {
        return $query->whereNotNull('record_hash');
    }

    /**
     * Records without hash chain set
     */
    public function scopeWithoutHashChain($query)
    {
        return $query->whereNull('record_hash');
    }

    /**
     * Verified records
     */
    public function scopeVerified($query)
    {
        return $query->where('verification_status', self::VERIFICATION_VALID);
    }

    /**
     * Records with tampering detected
     */
    public function scopeTampered($query)
    {
        return $query->where('verification_status', self::VERIFICATION_INVALID);
    }

    /**
     * Unverified records
     */
    public function scopeUnverified($query)
    {
        return $query->whereNull('verification_status');
    }

    // ========================================
    // Static helper (for hash chain)
    // ========================================

    /**
     * Get the latest hash
     */
    public static function getLatestHash(): ?string
    {
        return self::whereNotNull('record_hash')
            ->orderBy('id', 'desc')
            ->value('record_hash');
    }

    /**
     * Get the latest sequence number
     */
    public static function getLatestSequence(): int
    {
        return (int) self::whereNotNull('chain_sequence')
            ->orderBy('id', 'desc')
            ->value('chain_sequence') ?? 0;
    }

    /**
     * Record log with hash chain
     */
    public static function logWithHashChain(array $data): self
    {
        $log = self::log($data);
        $log->saveWithHashChain();

        return $log;
    }
}
