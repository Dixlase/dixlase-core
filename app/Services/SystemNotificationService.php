<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

use App\Models\SiteSetting;
use App\Notifications\SystemErrorNotification;
use Exception;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class SystemNotificationService
{
    /**
     * Send system error notification
     *
     * @param  string  $subject  Subject
     * @param  string  $message  Error message
     * @param  array  $context  Additional context information
     * @return bool True if sent successfully
     */
    public function sendErrorNotification(string $subject, string $message, array $context = []): bool
    {
        try {
            // Check if notification feature is enabled
            if (! $this->isNotificationEnabled()) {
                return false;
            }

            // Get notification recipient email address
            $notificationEmail = $this->getNotificationEmail();
            if (empty($notificationEmail)) {
                Log::warning('System notification email address is not configured');

                return false;
            }

            // Check if mail server is configured
            if (! MailServerValidatorService::isMailServerTested()) {
                Log::warning('Mail server is not properly configured for system notifications');

                return false;
            }

            // Send email using Notification
            Notification::route('mail', $notificationEmail)
                ->notify(new SystemErrorNotification($subject, $message, $context));

            Log::info('System error notification sent successfully', [
                'to' => $notificationEmail,
                'subject' => $subject,
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Failed to send system error notification', [
                'error' => $e->getMessage(),
                'subject' => $subject,
                'message' => $message,
            ]);

            return false;
        }
    }

    /**
     * Send notification to administrator (generic)
     *
     * @param  string  $subject  Subject
     * @param  string  $message  Message
     * @param  array  $context  Additional context information
     * @return bool True if sent successfully
     */
    public function sendAdminNotification(string $subject, string $message, array $context = []): bool
    {
        try {
            // Check if notification feature is enabled
            if (! $this->isNotificationEnabled()) {
                return false;
            }

            // Get notification recipient email address
            $notificationEmail = $this->getNotificationEmail();
            if (empty($notificationEmail)) {
                Log::warning('System notification email address is not configured');

                return false;
            }

            // Check if mail server is configured
            if (! MailServerValidatorService::isMailServerTested()) {
                Log::warning('Mail server is not properly configured for system notifications');

                return false;
            }

            // Create email content
            $mailMessage = $this->buildNotificationMail($subject, $message, $context);

            // Send email
            Mail::send([], [], function ($mail) use ($notificationEmail, $mailMessage) {
                $mail->to($notificationEmail)
                    ->subject($mailMessage->subject)
                    ->html((string) $mailMessage->render());
            });

            Log::info('Admin notification sent successfully', [
                'to' => $notificationEmail,
                'subject' => $subject,
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Failed to send admin notification', [
                'error' => $e->getMessage(),
                'subject' => $subject,
                'message' => $message,
            ]);

            return false;
        }
    }

    /**
     * Send database error notification
     */
    public function sendDatabaseErrorNotification(Exception $exception): bool
    {
        return $this->sendErrorNotification(
            __('services/system_notification_service.database_error_occurred'),
            $exception->getMessage(),
            [
                'type' => 'database_error',
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ]
        );
    }

    /**
     * Send application error notification
     */
    public function sendApplicationErrorNotification(Exception $exception): bool
    {
        return $this->sendErrorNotification(
            __('services/system_notification_service.application_error_occurred'),
            $exception->getMessage(),
            [
                'type' => 'application_error',
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ]
        );
    }

    /**
     * Check if notification feature is enabled
     */
    public function isNotificationEnabled(): bool
    {
        try {
            // notification_enabled is Global scope; SettingResolver routes
            // through global_settings.
            return (bool) app(\App\Services\Site\SettingResolver::class)->get('notification_enabled');
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Get notification recipient email address
     */
    public function getNotificationEmail(): string
    {
        // Prioritize system administrator email address, otherwise use notification_email
        $adminEmail = SiteSetting::getValue('system_admin_email', '');
        if (! empty($adminEmail)) {
            return $adminEmail;
        }

        return SiteSetting::getValue('notification_email', '');
    }

    /**
     * Build administrator notification email (generic)
     */
    private function buildNotificationMail(string $subject, string $message, array $context = []): MailMessage
    {
        $appName = config('app.name');

        $mailMessage = new MailMessage();
        $mailMessage->subject("[{$appName}] {$subject}");
        $mailMessage->greeting(__('services/system_notification_service.dear_system_admin'));

        $mailMessage->line("**{$subject}**");
        $mailMessage->line($message);

        // Display additional information
        if (! empty($context)) {
            $mailMessage->line(__('services/system_notification_service.detailed_information'));

            foreach ($context as $key => $value) {
                if (is_string($value) || is_numeric($value)) {
                    $mailMessage->line("**{$key}:** {$value}");
                }
            }
        }

        // Add occurrence date and time
        $mailMessage->line(__('services/system_notification_service.notification_datetime').now()->format('Y-m-d H:i:s'));

        $mailMessage->salutation(__('services/system_notification_service.system_signature', ['appName' => $appName]));

        return $mailMessage;
    }

    /**
     * Build error notification email
     */
    private function buildErrorNotificationMail(string $subject, string $message, array $context = []): MailMessage
    {
        $appName = config('app.name');

        // Get color based on log level
        $logLevel = $context['log_level'] ?? 'Error';
        $levelColor = $this->getLogLevelColor($logLevel);
        $levelBgColor = $this->getLogLevelBgColor($logLevel);

        $mailMessage = new MailMessage();
        $mailMessage->subject("[{$appName}] {$subject}");
        $mailMessage->greeting(__('services/system_notification_service.dear_system_admin'));

        // Level display (colored) - add directly as HTML
        $levelHtml = '<div style="padding: 12px; background-color: '.$levelBgColor.'; border-left: 4px solid '.$levelColor.'; margin: 16px 0; border-radius: 4px;">';
        $levelHtml .= '<span style="color: '.$levelColor.'; font-weight: bold; font-size: 18px;">【'.$logLevel.'】</span>';
        $levelHtml .= '<span style="color: #333; font-weight: bold; font-size: 16px; margin-left: 8px;">'.htmlspecialchars($subject).'</span>';
        $levelHtml .= '</div>';

        $mailMessage->line(new \Illuminate\Support\HtmlString($levelHtml));

        $mailMessage->line('');
        $mailMessage->line('**Error message:**');
        $mailMessage->line($message);

        // Add error detail information
        if (! empty($context)) {
            $mailMessage->line('');
            $mailMessage->line(__('services/system_notification_service.error_details'));

            if (isset($context['type'])) {
                $mailMessage->line(__('services/system_notification_service.error_type', ['type' => $context['type']]));
            }

            if (isset($context['file'])) {
                $mailMessage->line(__('services/system_notification_service.error_file', ['file' => $context['file']]));
            }

            if (isset($context['line'])) {
                $mailMessage->line(__('services/system_notification_service.error_line', ['line' => $context['line']]));
            }

            if (isset($context['url'])) {
                $mailMessage->line("**URL:** {$context['url']}");
            }

            if (isset($context['user_agent'])) {
                $mailMessage->line("**User Agent:** {$context['user_agent']}");
            }

            if (isset($context['ip'])) {
                $mailMessage->line(__('services/system_notification_service.error_ip', ['ip' => $context['ip']]));
            }

            // Add stack trace (first 10 lines only)
            if (isset($context['trace'])) {
                $traceLines = explode("\n", $context['trace']);
                $limitedTrace = array_slice($traceLines, 0, 10);
                $mailMessage->line(__('services/system_notification_service.stack_trace_excerpt'));
                $mailMessage->line('```');
                foreach ($limitedTrace as $traceLine) {
                    $mailMessage->line($traceLine);
                }
                $mailMessage->line('```');
            }

            // Add other context information (limited to reasonable length)
            $excludeKeys = ['type', 'file', 'line', 'url', 'user_agent', 'ip', 'trace', 'exception', 'log_level', 'log_level_value', 'timestamp', 'channel', 'method'];
            $maxContextLength = 200; // Maximum 200 characters
            $contextCount = 0;
            $maxContextItems = 10; // Maximum 10 items

            foreach ($context as $key => $value) {
                if ($contextCount >= $maxContextItems) {
                    $mailMessage->line(__('services/system_notification_service.context_info_omitted'));
                    break;
                }

                if (! in_array($key, $excludeKeys)) {
                    if (is_string($value) || is_numeric($value)) {
                        // Truncate long values
                        $displayValue = is_string($value) && strlen($value) > $maxContextLength
                            ? substr($value, 0, $maxContextLength).'...'
                            : $value;
                        $mailMessage->line("**{$key}:** {$displayValue}");
                        $contextCount++;
                    } elseif (is_array($value) || is_object($value)) {
                        // Display arrays or objects in JSON format (with limits)
                        $jsonValue = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        if (strlen($jsonValue) > $maxContextLength) {
                            $jsonValue = substr($jsonValue, 0, $maxContextLength).'...';
                        }
                        $mailMessage->line("**{$key}:** {$jsonValue}");
                        $contextCount++;
                    }
                }
            }
        }

        // Add occurrence date and time
        $mailMessage->line(__('services/system_notification_service.occurrence_datetime').now()->format('Y-m-d H:i:s'));

        $mailMessage->line(__('services/system_notification_service.please_investigate_and_act'));

        $mailMessage->salutation(__('services/system_notification_service.system_signature', ['appName' => $appName]));

        return $mailMessage;
    }

    /**
     * Get color based on log level
     */
    private function getLogLevelColor(string $logLevel): string
    {
        return match (strtoupper($logLevel)) {
            'EMERGENCY' => '#DC2626', // Red (dark)
            'ALERT' => '#EF4444',     // Red
            'CRITICAL' => '#F97316',  // Orange
            'ERROR' => '#F59E0B',     // Yellow (dark)
            'WARNING' => '#EAB308',   // Yellow
            default => '#3B82F6',     // blue (other)
        };
    }

    /**
     * Get background color based on log level
     */
    private function getLogLevelBgColor(string $logLevel): string
    {
        return match (strtoupper($logLevel)) {
            'EMERGENCY' => '#FEE2E2', // Red (light)
            'ALERT' => '#FEE2E2',     // Red (light)
            'CRITICAL' => '#FFEDD5',  // Orange (light)
            'ERROR' => '#FEF3C7',     // Yellow (light)
            'WARNING' => '#FEF9C3',   // Yellow (light)
            default => '#DBEAFE',     // blue (light)
        };
    }
}
