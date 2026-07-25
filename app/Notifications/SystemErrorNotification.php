<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

/**
 * System error notification
 */
class SystemErrorNotification extends Notification
{
    use Queueable;

    /**
     * Error subject
     *
     * @var string
     */
    protected $subject;

    /**
     * Error message
     *
     * @var string
     */
    protected $message;

    /**
     * Context information
     *
     * @var array
     */
    protected $context;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $subject, string $message, array $context = [])
    {
        $this->subject = $subject;
        $this->message = $message;
        $this->context = $context;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $appName = env('APP_NAME', 'Dixlase');

        // Get color based on log level
        $logLevel = $this->context['log_level'] ?? 'Error';
        $levelColor = $this->getLogLevelColor($logLevel);
        $levelBgColor = $this->getLogLevelBgColor($logLevel);

        $mailMessage = (new MailMessage())
            ->subject("[{$appName}] {$this->subject}")
            ->greeting(__('notifications/system_error_notification.system_administrator'));

        // Level display (colored)
        $levelHtml = '<div style="padding: 12px; background-color: '.$levelBgColor.'; border-left: 4px solid '.$levelColor.'; margin: 16px 0; border-radius: 4px;">';
        $levelHtml .= '<span style="color: '.$levelColor.'; font-weight: bold; font-size: 18px;">【'.$logLevel.'】</span>';
        $levelHtml .= '<span style="color: #333; font-weight: bold; font-size: 16px; margin-left: 8px;">'.htmlspecialchars($this->subject).'</span>';
        $levelHtml .= '</div>';

        $mailMessage->line(new HtmlString($levelHtml));

        $mailMessage->line('');
        $mailMessage->line('**Error message:**');
        $mailMessage->line($this->message);

        // Add error details
        if (! empty($this->context)) {
            $mailMessage->line('');
            $mailMessage->line(__('notifications/system_error_notification.error_details'));

            if (isset($this->context['type'])) {
                $mailMessage->line(__('notifications/system_error_notification.error_type_label', ['type' => $this->context['type']]));
            }

            if (isset($this->context['file'])) {
                $mailMessage->line(__('notifications/system_error_notification.file_label', ['file' => $this->context['file']]));
            }

            if (isset($this->context['line'])) {
                $mailMessage->line(__('notifications/system_error_notification.line_number_label', ['line' => $this->context['line']]));
            }

            if (isset($this->context['url'])) {
                $mailMessage->line("**URL:** {$this->context['url']}");
            }

            if (isset($this->context['user_agent'])) {
                $mailMessage->line("**User Agent:** {$this->context['user_agent']}");
            }

            if (isset($this->context['ip'])) {
                $mailMessage->line(__('notifications/system_error_notification.ip_address_label', ['ip' => $this->context['ip']]));
            }

            // Add stack trace (first 10 lines only)
            if (isset($this->context['trace'])) {
                $traceLines = explode("\n", $this->context['trace']);
                $limitedTrace = array_slice($traceLines, 0, 10);
                $mailMessage->line(__('notifications/system_error_notification.stack_trace_excerpt'));
                $mailMessage->line('```');
                foreach ($limitedTrace as $traceLine) {
                    $mailMessage->line($traceLine);
                }
                $mailMessage->line('```');
            }

            // Add other context information (limited to reasonable length)
            $excludeKeys = ['type', 'file', 'line', 'url', 'user_agent', 'ip', 'trace', 'exception', 'log_level', 'log_level_value', 'timestamp', 'channel', 'method'];
            $maxContextLength = 200;
            $contextCount = 0;
            $maxContextItems = 10;

            foreach ($this->context as $key => $value) {
                if ($contextCount >= $maxContextItems) {
                    $mailMessage->line(__('notifications/system_error_notification.other_context_info_omitted'));
                    break;
                }

                if (! in_array($key, $excludeKeys)) {
                    if (is_string($value) || is_numeric($value)) {
                        $displayValue = is_string($value) && strlen($value) > $maxContextLength
                            ? substr($value, 0, $maxContextLength).'...'
                            : $value;
                        $mailMessage->line("**{$key}:** {$displayValue}");
                        $contextCount++;
                    } elseif (is_array($value) || is_object($value)) {
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
        $mailMessage->line(__('notifications/system_error_notification.occurrence_date_time').now()->format('Y-m-d H:i:s'));

        $mailMessage->line(__('notifications/system_error_notification.please_investigate_and_take_action'));

        $mailMessage->salutation(__('notifications/system_error_notification.system_signature', ['appName' => $appName]));

        return $mailMessage;
    }

    /**
     * Get color based on log level
     */
    private function getLogLevelColor(string $logLevel): string
    {
        return match (strtoupper($logLevel)) {
            'EMERGENCY' => '#DC2626',
            'ALERT' => '#EF4444',
            'CRITICAL' => '#F97316',
            'ERROR' => '#F59E0B',
            'WARNING' => '#EAB308',
            default => '#3B82F6',
        };
    }

    /**
     * Get background color based on log level
     */
    private function getLogLevelBgColor(string $logLevel): string
    {
        return match (strtoupper($logLevel)) {
            'EMERGENCY' => '#FEE2E2',
            'ALERT' => '#FEE2E2',
            'CRITICAL' => '#FFEDD5',
            'ERROR' => '#FEF3C7',
            'WARNING' => '#FEF9C3',
            default => '#DBEAFE',
        };
    }
}
