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

namespace App\Services;

use App\Contracts\Logging\LogServiceInterface;
use App\DTO\Logging\LogContextDTO;
use Illuminate\Support\Facades\Log;

/**
 * @internal Core only. Do not reference from plugins/themes
 *
 * Log output service
 *
 * Provides unified log output functionality compatible with Contract
 */
class LogService implements LogServiceInterface
{
    /**
     * Default channel
     */
    protected string $defaultChannel = 'dixlase';

    /**
     * Admin panel operation log channel
     */
    protected string $activityChannel = 'admin_activity';

    /**
     * Login log channel
     */
    protected string $loginChannel = 'admin_login';

    /**
     * Front operation log channel
     */
    protected string $frontActivityChannel = 'front_activity';

    /**
     * Front error log channel
     */
    protected string $frontErrorChannel = 'front_error';

    /**
     * Output info log
     */
    public function info(string $message, LogContextDTO|array $context = [], ?string $channel = null): void
    {
        $this->log($channel ?? $this->defaultChannel, 'info', $message, $context);
    }

    /**
     * Output warning log
     */
    public function warning(string $message, LogContextDTO|array $context = [], ?string $channel = null): void
    {
        $this->log($channel ?? $this->defaultChannel, 'warning', $message, $context);
    }

    /**
     * Output error log
     */
    public function error(string $message, LogContextDTO|array $context = [], ?string $channel = null): void
    {
        $this->log($channel ?? 'admin_error', 'error', $message, $context);
    }

    /**
     * Output debug log
     */
    public function debug(string $message, LogContextDTO|array $context = [], ?string $channel = null): void
    {
        $this->log($channel ?? $this->defaultChannel, 'debug', $message, $context);
    }

    /**
     * Output critical error log
     */
    public function critical(string $message, LogContextDTO|array $context = [], ?string $channel = null): void
    {
        $this->log($channel ?? 'admin_error', 'critical', $message, $context);
    }

    /**
     * Output operation log (admin panel operations, etc.)
     */
    public function activity(string $action, LogContextDTO|array $context = []): void
    {
        $contextArray = $this->normalizeContext($context);
        $contextArray['action'] = $action;

        Log::channel($this->activityChannel)->info(__('services/log_service.admin_panel_operation'), $contextArray);
    }

    /**
     * Output login log
     */
    public function login(string $action, LogContextDTO|array $context = []): void
    {
        $contextArray = $this->normalizeContext($context);
        $contextArray['action'] = $action;

        Log::channel($this->loginChannel)->info($action, $contextArray);
    }

    /**
     * Output front operation log
     */
    public function frontActivity(string $action, LogContextDTO|array $context = []): void
    {
        $contextArray = $this->normalizeContext($context);
        $contextArray['action'] = $action;

        Log::channel($this->frontActivityChannel)->info(__('services/log_service.front_end_operation'), $contextArray);
    }

    /**
     * Output front error log
     */
    public function frontError(string $error, LogContextDTO|array $context = []): void
    {
        $contextArray = $this->normalizeContext($context);
        $contextArray['error'] = $error;

        Log::channel($this->frontErrorChannel)->error(__('services/log_service.front_end_error'), $contextArray);
    }

    /**
     * Output log to custom channel
     */
    public function log(string $channel, string $level, string $message, LogContextDTO|array $context = []): void
    {
        $contextArray = $this->normalizeContext($context);

        try {
            Log::channel($channel)->log($level, $message, $contextArray);
        } catch (\Exception $e) {
            // Falls back to default channel if channel does not exist
            Log::channel($this->defaultChannel)->log($level, $message, $contextArray);
        }
    }

    /**
     * Get list of available log channels
     */
    public function getAvailableChannels(): array
    {
        return array_keys(config('logging.channels', []));
    }

    /**
     * Normalize context to array
     *
     * @return array<string,mixed>
     */
    protected function normalizeContext(LogContextDTO|array $context): array
    {
        if ($context instanceof LogContextDTO) {
            return $context->toArray();
        }

        return $context;
    }

    /**
     * Output log for plugin
     *
     * @param  string  $pluginSlug  Plugin slug
     * @param  string  $level  Log level
     * @param  string  $message  Message
     * @param  array<string,mixed>  $context  Context
     */
    public function pluginLog(string $pluginSlug, string $level, string $message, array $context = []): void
    {
        $context['source'] = $pluginSlug;
        $context['plugin'] = $pluginSlug;

        $this->log($this->defaultChannel, $level, "[{$pluginSlug}] {$message}", $context);
    }

    /**
     * Output info log for plugin
     *
     * @param  string  $pluginSlug  Plugin slug
     * @param  string  $message  Message
     * @param  array<string,mixed>  $context  Context
     */
    public function pluginInfo(string $pluginSlug, string $message, array $context = []): void
    {
        $this->pluginLog($pluginSlug, 'info', $message, $context);
    }

    /**
     * Output error log for plugin
     *
     * @param  string  $pluginSlug  Plugin slug
     * @param  string  $message  Message
     * @param  array<string,mixed>  $context  Context
     */
    public function pluginError(string $pluginSlug, string $message, array $context = []): void
    {
        $context['source'] = $pluginSlug;
        $context['plugin'] = $pluginSlug;

        $this->log('admin_error', 'error', "[{$pluginSlug}] {$message}", $context);
    }

    /**
     * Log exception
     *
     * @param  \Throwable  $exception  Exception
     * @param  LogContextDTO|array  $context  Additional context
     * @param  string|null  $channel  Channel name
     */
    public function exception(\Throwable $exception, LogContextDTO|array $context = [], ?string $channel = null): void
    {
        $contextArray = $this->normalizeContext($context);
        $contextArray['exception'] = [
            'class' => get_class($exception),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ];

        $this->error($exception->getMessage(), $contextArray, $channel);
    }
}
