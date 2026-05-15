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

namespace App\Services;

use App\Enums\PluginHealthStatus;
use App\Facades\Audit;
use App\Helpers\AdminHelper;
use App\Mail\ExtensionOperationNotificationMail;
use App\Models\AuditLog;
use App\Models\SecuritySetting;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * @internal For Core use only. Do not reference from plugins/themes
 */
class ExtensionOperationService
{
    /**
     * Operation type constants
     */
    public const OPERATION_INSTALLED = 'installed';

    public const OPERATION_UNINSTALLED = 'uninstalled';

    public const OPERATION_ENABLED = 'enabled';

    public const OPERATION_DISABLED = 'disabled';

    /**
     * Extension type constants
     */
    public const TYPE_PLUGIN = 'plugin';

    public const TYPE_THEME = 'theme';

    /**
     * Record and notify extension operations
     *
     * @param  string  $type  Extension type (plugin/theme)
     * @param  string  $operation  Operation type (installed/uninstalled/enabled/disabled)
     * @param  array  $extensionData  Extension detail data
     */
    public function recordOperation(string $type, string $operation, array $extensionData): void
    {
        $details = $this->buildOperationDetails($type, $operation, $extensionData);

        // Log the operation
        if ($this->shouldLogOperation()) {
            $this->logOperation($details, $operation);
        }

        // Email notification
        $this->sendNotificationIfNeeded($details, $operation);
    }

    /**
     * Build operation detail data
     */
    protected function buildOperationDetails(string $type, string $operation, array $extensionData): array
    {
        $member = $this->getCurrentMember();

        return [
            'type' => $type,
            'name' => $extensionData['name'] ?? 'Unknown',
            'slug' => $extensionData['slug'] ?? null,
            'version' => $extensionData['version'] ?? null,
            'health_status' => $extensionData['health_status'] ?? $extensionData['risk_level'] ?? 'unknown',
            'operated_by' => $member ? ($member->display_name ?? $member->account_name) : 'System',
            'operated_by_id' => $member ? $member->id : null,
            'operated_at' => now()->format('Y-m-d H:i:s'),
            'operation' => $operation,
        ];
    }

    /**
     * Safely get the current logged-in member
     * Return null when auth guard is unavailable, such as during Artisan command execution
     */
    protected function getCurrentMember(): ?object
    {
        try {
            // Check if admin guard is defined
            if (! config('auth.guards.admin')) {
                return null;
            }

            return Auth::guard('admin')->user();
        } catch (\Exception $e) {
            // When guard is unavailable (e.g., CLI)
            return null;
        }
    }

    /**
     * Log the operation (integrated with audit log)
     */
    protected function logOperation(array $details, string $operation): void
    {
        // Determine audit log action
        $action = $this->getAuditAction($details['type'], $operation);

        // Warning level if health status is not good
        $isUnhealthy = ! $this->isHealthStatusHealthy($details['health_status']) &&
            in_array($operation, [self::OPERATION_INSTALLED, self::OPERATION_ENABLED]);

        $severity = $isUnhealthy ? AuditLog::SEVERITY_WARNING : AuditLog::SEVERITY_NOTICE;

        // Get the operator
        $actor = AdminHelper::getMember();

        // Record to audit log
        Audit::logExtension($action, [
            'actor' => $actor,
            'outcome' => AuditLog::OUTCOME_SUCCESS,
            'severity' => $severity,
            'context' => [
                'message' => $this->getOperationMessage($details, $operation),
                'extension_type' => $details['type'],
                'extension_name' => $details['name'],
                'extension_slug' => $details['slug'],
                'extension_version' => $details['version'],
                'health_status' => $details['health_status'],
                'operation' => $operation,
            ],
        ]);
    }

    /**
     * Get audit log action
     */
    protected function getAuditAction(string $type, string $operation): string
    {
        $actionMap = [
            self::TYPE_PLUGIN => [
                self::OPERATION_INSTALLED => AuditLog::ACTION_PLUGIN_INSTALLED,
                self::OPERATION_UNINSTALLED => AuditLog::ACTION_PLUGIN_UNINSTALLED,
                self::OPERATION_ENABLED => AuditLog::ACTION_PLUGIN_ENABLED,
                self::OPERATION_DISABLED => AuditLog::ACTION_PLUGIN_DISABLED,
            ],
            self::TYPE_THEME => [
                self::OPERATION_INSTALLED => AuditLog::ACTION_THEME_INSTALLED,
                self::OPERATION_UNINSTALLED => AuditLog::ACTION_THEME_UNINSTALLED,
                self::OPERATION_ENABLED => AuditLog::ACTION_THEME_ENABLED,
                self::OPERATION_DISABLED => AuditLog::ACTION_THEME_DISABLED,
            ],
        ];

        return $actionMap[$type][$operation] ?? "extension_{$operation}";
    }

    /**
     * Generate operation message
     */
    protected function getOperationMessage(array $details, string $operation): string
    {
        $typeLabel = $details['type'] === self::TYPE_PLUGIN ? __('services/extension_operation_service.plugin') : __('services/extension_operation_service.theme');
        $operationLabels = [
            self::OPERATION_INSTALLED => __('services/extension_operation_service.install'),
            self::OPERATION_UNINSTALLED => __('services/extension_operation_service.uninstall'),
            self::OPERATION_ENABLED => __('services/extension_operation_service.enable'),
            self::OPERATION_DISABLED => __('services/extension_operation_service.disable'),
        ];
        $operationLabel = $operationLabels[$operation] ?? $operation;

        return sprintf(
            __('services/extension_operation_service.operation_success_message'),
            $typeLabel,
            $details['name'],
            $details['version'] ?? 'unknown',
            $operationLabel
        );
    }

    /**
     * Send email notification if needed
     */
    protected function sendNotificationIfNeeded(array $details, string $operation): void
    {
        // Check if mail server is configured
        if (! MailServerValidatorService::isMailServerTested()) {
            return;
        }

        $adminEmail = SiteSetting::getValue('admin_email');
        if (empty($adminEmail)) {
            return;
        }

        // Check notification settings according to operation type
        $shouldNotify = $this->shouldNotifyForOperation($operation);

        if ($shouldNotify) {
            $this->sendOperationNotification($adminEmail, $details, $operation);
        }

        // Health warning notification
        if ($this->shouldNotifyUnhealthy() && $this->isUnhealthyOperation($details, $operation)) {
            $this->sendUnhealthyWarningNotification($adminEmail, $details, $operation);
        }
    }

    /**
     * Send operation notification email
     */
    protected function sendOperationNotification(string $email, array $details, string $operation): void
    {
        try {
            Mail::to($email)->send(new ExtensionOperationNotificationMail($details, $operation, false));
        } catch (\Exception $e) {
            Log::error('Failed to send extension operation notification email', [
                'error' => $e->getMessage(),
                'details' => $details,
            ]);
        }
    }

    /**
     * Send health warning email
     */
    protected function sendUnhealthyWarningNotification(string $email, array $details, string $operation): void
    {
        try {
            Mail::to($email)->send(new ExtensionOperationNotificationMail($details, $operation, true));
        } catch (\Exception $e) {
            Log::error('Failed to send extension unhealthy warning email', [
                'error' => $e->getMessage(),
                'details' => $details,
            ]);
        }
    }

    /**
     * Check if notification is enabled according to operation type
     */
    protected function shouldNotifyForOperation(string $operation): bool
    {
        $settingKey = match ($operation) {
            self::OPERATION_INSTALLED => 'extension_notify_on_install',
            self::OPERATION_UNINSTALLED => 'extension_notify_on_uninstall',
            self::OPERATION_ENABLED => 'extension_notify_on_enable',
            self::OPERATION_DISABLED => 'extension_notify_on_disable',
            default => null,
        };

        if ($settingKey === null) {
            return false;
        }

        return (bool) SecuritySetting::getValue($settingKey, true);
    }

    /**
     * Check if health warning notification is enabled
     */
    protected function shouldNotifyUnhealthy(): bool
    {
        return (bool) SecuritySetting::getValue('extension_notify_on_unhealthy', true);
    }

    /**
     * Check if operation log is enabled
     */
    protected function shouldLogOperation(): bool
    {
        return (bool) SecuritySetting::getValue('extension_log_operations', true);
    }

    /**
     * Check if operation has non-healthy status
     */
    protected function isUnhealthyOperation(array $details, string $operation): bool
    {
        // Send health warning only during install/enable operations
        if (! in_array($operation, [self::OPERATION_INSTALLED, self::OPERATION_ENABLED])) {
            return false;
        }

        return ! $this->isHealthStatusHealthy($details['health_status']);
    }

    /**
     * Determine if health status is "healthy"
     *
     * Support both PluginHealthStatus enum value (healthy) and legacy risk level value (low)
     */
    protected function isHealthStatusHealthy(string $healthStatus): bool
    {
        return $healthStatus === PluginHealthStatus::Healthy->value
            || $healthStatus === 'low';
    }
}
