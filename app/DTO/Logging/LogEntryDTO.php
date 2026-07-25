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

namespace App\DTO\Logging;

use JsonSerializable;

/**
 * Log Entry DTO
 *
 * Immutable data object that holds entries read from log files
 */
final readonly class LogEntryDTO implements JsonSerializable
{
    public const LEVEL_DEBUG = 'debug';

    public const LEVEL_INFO = 'info';

    public const LEVEL_NOTICE = 'notice';

    public const LEVEL_WARNING = 'warning';

    public const LEVEL_ERROR = 'error';

    public const LEVEL_CRITICAL = 'critical';

    public const LEVEL_ALERT = 'alert';

    public const LEVEL_EMERGENCY = 'emergency';

    /**
     * @param  string  $level  Log level
     * @param  string  $message  Message
     * @param  string  $channel  Channel name
     * @param  string  $timestamp  Timestamp
     * @param  array<string,mixed>  $context  Context
     * @param  string|null  $source  Source (file name, etc.)
     * @param  int|null  $line  Line number
     */
    public function __construct(
        public string $level,
        public string $message,
        public string $channel,
        public string $timestamp,
        public array $context = [],
        public ?string $source = null,
        public ?int $line = null,
    ) {}

    /**
     * Whether it is error level
     */
    public function isError(): bool
    {
        return in_array($this->level, [
            self::LEVEL_ERROR,
            self::LEVEL_CRITICAL,
            self::LEVEL_ALERT,
            self::LEVEL_EMERGENCY,
        ]);
    }

    /**
     * Whether it is warning level
     */
    public function isWarning(): bool
    {
        return $this->level === self::LEVEL_WARNING;
    }

    /**
     * Whether it is info level
     */
    public function isInfo(): bool
    {
        return $this->level === self::LEVEL_INFO;
    }

    /**
     * Whether it is debug level
     */
    public function isDebug(): bool
    {
        return $this->level === self::LEVEL_DEBUG;
    }

    /**
     * Get log level severity (numeric)
     */
    public function getSeverity(): int
    {
        return match ($this->level) {
            self::LEVEL_EMERGENCY => 8,
            self::LEVEL_ALERT => 7,
            self::LEVEL_CRITICAL => 6,
            self::LEVEL_ERROR => 5,
            self::LEVEL_WARNING => 4,
            self::LEVEL_NOTICE => 3,
            self::LEVEL_INFO => 2,
            self::LEVEL_DEBUG => 1,
            default => 0,
        };
    }

    /**
     * Serialize to JSON format
     *
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'level' => $this->level,
            'message' => $this->message,
            'channel' => $this->channel,
            'timestamp' => $this->timestamp,
            'context' => $this->context,
            'source' => $this->source,
            'line' => $this->line,
        ];
    }

    /**
     * Convert to array format
     *
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return $this->jsonSerialize();
    }

    /**
     * Create DTO from array
     *
     * @param  array<string,mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            level: $data['level'] ?? self::LEVEL_INFO,
            message: $data['message'] ?? '',
            channel: $data['channel'] ?? 'default',
            timestamp: $data['timestamp'] ?? now()->toDateTimeString(),
            context: $data['context'] ?? [],
            source: $data['source'] ?? null,
            line: $data['line'] ?? null,
        );
    }

    /**
     * Parse log line and create DTO
     *
     * @param  string  $line  Log line
     * @param  string  $channel  Channel name
     */
    public static function fromLogLine(string $line, string $channel = 'default'): ?self
    {
        // Parse Laravel standard log format
        // [2025-01-15 12:34:56] local.INFO: Message {"context":"value"}
        $pattern = '/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (\w+)\.(\w+): (.+)$/';

        if (! preg_match($pattern, $line, $matches)) {
            return null;
        }

        $timestamp = $matches[1];
        $env = $matches[2];
        $level = strtolower($matches[3]);
        $messageWithContext = $matches[4];

        // Separate message and context
        $context = [];
        $message = $messageWithContext;

        if (preg_match('/^(.+?) (\{.+\}|\[.+\])$/', $messageWithContext, $msgMatches)) {
            $message = $msgMatches[1];
            $contextJson = $msgMatches[2];
            $decoded = json_decode($contextJson, true);
            if (is_array($decoded)) {
                $context = $decoded;
            }
        }

        return new self(
            level: $level,
            message: $message,
            channel: $channel,
            timestamp: $timestamp,
            context: $context,
        );
    }
}
