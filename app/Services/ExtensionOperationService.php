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

use App\Facades\Audit;
use App\Helpers\AdminHelper;
use App\Mail\ExtensionOperationNotificationMail;
use App\Models\AuditLog;
use App\Models\BaseSetting;
use App\Models\SecuritySetting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ExtensionOperationService
{
    /**
     * 操作種別定数
     */
    public const OPERATION_INSTALLED = 'installed';
    public const OPERATION_UNINSTALLED = 'uninstalled';
    public const OPERATION_ENABLED = 'enabled';
    public const OPERATION_DISABLED = 'disabled';

    /**
     * 拡張機能種別定数
     */
    public const TYPE_PLUGIN = 'plugin';
    public const TYPE_THEME = 'theme';

    /**
     * 拡張機能の操作を記録・通知する
     *
     * @param string $type 拡張機能種別 (plugin/theme)
     * @param string $operation 操作種別 (installed/uninstalled/enabled/disabled)
     * @param array $extensionData 拡張機能の詳細データ
     * @return void
     */
    public function recordOperation(string $type, string $operation, array $extensionData): void
    {
        $details = $this->buildOperationDetails($type, $operation, $extensionData);

        // ログに記録
        if ($this->shouldLogOperation()) {
            $this->logOperation($details, $operation);
        }

        // メール通知
        $this->sendNotificationIfNeeded($details, $operation);
    }

    /**
     * 操作詳細データを構築
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
            'operated_by' => $member ? $member->name : 'System',
            'operated_by_id' => $member ? $member->id : null,
            'operated_at' => now()->format('Y-m-d H:i:s'),
            'operation' => $operation,
        ];
    }

    /**
     * 現在のログインメンバーを安全に取得
     * Artisanコマンド実行時など、認証ガードが利用できない場合はnullを返す
     */
    protected function getCurrentMember(): ?object
    {
        try {
            // adminガードが定義されているか確認
            if (!config('auth.guards.admin')) {
                return null;
            }
            return Auth::guard('admin')->user();
        } catch (\Exception $e) {
            // ガードが利用できない場合（CLIなど）
            return null;
        }
    }

    /**
     * 操作をログに記録（監査ログに統合）
     */
    protected function logOperation(array $details, string $operation): void
    {
        // 監査ログのアクションを決定
        $action = $this->getAuditAction($details['type'], $operation);
        
        // 健全性が良好以外の場合は警告レベル
        $isUnhealthy = $details['health_status'] !== 'low' && 
            in_array($operation, [self::OPERATION_INSTALLED, self::OPERATION_ENABLED]);
        
        $severity = $isUnhealthy ? AuditLog::SEVERITY_WARNING : AuditLog::SEVERITY_NOTICE;

        // 操作者を取得
        $actor = AdminHelper::getMember();

        // 監査ログに記録
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
     * 監査ログのアクションを取得
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
     * 操作メッセージを生成
     */
    protected function getOperationMessage(array $details, string $operation): string
    {
        $typeLabel = $details['type'] === self::TYPE_PLUGIN ? 'プラグイン' : 'テーマ';
        $operationLabels = [
            self::OPERATION_INSTALLED => 'インストール',
            self::OPERATION_UNINSTALLED => 'アンインストール',
            self::OPERATION_ENABLED => '有効化',
            self::OPERATION_DISABLED => '無効化',
        ];
        $operationLabel = $operationLabels[$operation] ?? $operation;
        
        return sprintf(
            '%s「%s」(v%s)を%sしました',
            $typeLabel,
            $details['name'],
            $details['version'] ?? 'unknown',
            $operationLabel
        );
    }

    /**
     * 必要に応じてメール通知を送信
     */
    protected function sendNotificationIfNeeded(array $details, string $operation): void
    {
        // メールサーバーが設定されているか確認
        if (!$this->isMailServerConfigured()) {
            return;
        }

        $adminEmail = BaseSetting::getValue('admin_email');
        if (empty($adminEmail)) {
            return;
        }

        // 操作種別に応じた通知設定を確認
        $shouldNotify = $this->shouldNotifyForOperation($operation);
        
        if ($shouldNotify) {
            $this->sendOperationNotification($adminEmail, $details, $operation);
        }

        // 健全性警告の通知
        if ($this->shouldNotifyUnhealthy() && $this->isUnhealthyOperation($details, $operation)) {
            $this->sendUnhealthyWarningNotification($adminEmail, $details, $operation);
        }
    }

    /**
     * 操作通知メールを送信
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
     * 健全性警告メールを送信
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
     * 操作種別に応じた通知が有効か確認
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
     * 健全性警告通知が有効か確認
     */
    protected function shouldNotifyUnhealthy(): bool
    {
        return (bool) SecuritySetting::getValue('extension_notify_on_unhealthy', true);
    }

    /**
     * 操作ログが有効か確認
     */
    protected function shouldLogOperation(): bool
    {
        return (bool) SecuritySetting::getValue('extension_log_operations', true);
    }

    /**
     * 健全性が良好以外の操作か確認
     */
    protected function isUnhealthyOperation(array $details, string $operation): bool
    {
        // インストール・有効化時のみ健全性警告を送信
        if (!in_array($operation, [self::OPERATION_INSTALLED, self::OPERATION_ENABLED])) {
            return false;
        }

        // 健全性が「良好（low）」以外の場合
        return $details['health_status'] !== 'low';
    }

    /**
     * メールサーバーが設定されているか確認
     */
    protected function isMailServerConfigured(): bool
    {
        return (bool) BaseSetting::getValue('mail_connection_tested', false)
            && (bool) BaseSetting::getValue('mail_send_tested', false)
            && (bool) BaseSetting::getValue('mail_receive_tested', false);
    }
}
