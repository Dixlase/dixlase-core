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

namespace App\Logging;

use App\Services\Site\SettingResolver;
use App\Services\SystemNotificationService;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;

class SystemNotificationLogHandler extends AbstractProcessingHandler
{
    protected SystemNotificationService $notificationService;

    public function __construct()
    {
        parent::__construct();
        $this->notificationService = app(SystemNotificationService::class);
    }

    /**
     * Process log record
     */
    protected function write(LogRecord $record): void
    {
        try {
            // Check if notification feature is enabled
            if (! $this->shouldSendNotification($record)) {
                return;
            }

            // Generate subject
            $subject = $this->generateSubject($record);

            // Generate message
            $message = $this->generateMessage($record);

            // Prepare context information
            $context = $this->prepareContext($record);

            // Send notification
            $this->notificationService->sendErrorNotification($subject, $message, $context);
        } catch (\Exception $e) {
            // Log notification send errors separately (to avoid infinite loop)
            error_log('SystemNotificationLogHandler error: '.$e->getMessage());
        }
    }

    /**
     * Check if notification should be sent
     */
    private function shouldSendNotification(LogRecord $record): bool
    {
        // Check if notification feature is enabled
        if (! $this->notificationService->isNotificationEnabled()) {
            return false;
        }

        // Get target log levels for notification
        $notificationLevels = $this->getNotificationLevels();
        if (empty($notificationLevels)) {
            return false;
        }

        // Convert Monolog level to Dixlase LogLevel
        $dixlaseLevel = $this->convertMonologLevelToDixlaseLevel($record->level->value);
        if ($dixlaseLevel === null) {
            return false;
        }

        // Check if included in target notification levels
        return in_array($dixlaseLevel, $notificationLevels);
    }

    /**
     * Get target log levels for notification
     */
    private function getNotificationLevels(): array
    {
        try {
            // notification_log_levels is Global scope; SettingResolver
            // routes through global_settings.
            $levels = app(SettingResolver::class)->get('notification_log_levels');

            if (empty($levels)) {
                return [];
            }

            return array_map('intval', explode(',', (string) $levels));
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Convert Monolog level to Dixlase LogLevel
     */
    private function convertMonologLevelToDixlaseLevel(int $monologLevel): ?int
    {
        // Mapping from Monolog level value to Dixlase LogLevel value
        $mapping = [
            600 => 8, // EMERGENCY
            550 => 7, // ALERT
            500 => 6, // CRITICAL
            400 => 5, // ERROR
            300 => 4, // WARNING
            250 => 3, // NOTICE
            200 => 2, // INFO
            100 => 1, // DEBUG
        ];

        return $mapping[$monologLevel] ?? null;
    }

    /**
     * Generate subject
     */
    private function generateSubject(LogRecord $record): string
    {
        $levelName = $record->level->name;
        $appName = env('APP_NAME', 'Dixlase');

        return __('logging/system_notification_log_handler.system_error_occurred_app_name', ['levelName' => $levelName, 'appName' => $appName]);
    }

    /**
     * Generate message
     */
    private function generateMessage(LogRecord $record): string
    {
        return $record->message;
    }

    /**
     * Prepare context information
     */
    private function prepareContext(LogRecord $record): array
    {
        $context = [
            'log_level' => $record->level->name,
            'log_level_value' => $record->level->value,
            'timestamp' => $record->datetime->format('Y-m-d H:i:s'),
            'channel' => $record->channel,
        ];

        // Merge existing context information
        if (! empty($record->context)) {
            $context = array_merge($context, $record->context);
        }

        // Extract additional information
        if (isset($record->context['exception'])) {
            $exception = $record->context['exception'];
            if ($exception instanceof \Exception) {
                $context['file'] = $exception->getFile();
                $context['line'] = $exception->getLine();
                $context['type'] = get_class($exception);
            }
        }

        // Add request information (if available)
        try {
            if (app()->bound('request')) {
                $request = request();
                $context['url'] = $request->fullUrl();
                $context['method'] = $request->method();
                $context['ip'] = $request->ip();
                $context['user_agent'] = $request->userAgent();
            }
        } catch (\Exception $e) {
            // Ignore if request information retrieval fails
        }

        return $context;
    }
}
