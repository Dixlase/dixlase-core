<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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

namespace App\DTO\Mail;

use JsonSerializable;

/**
 * Mail settings DTO
 *
 * Immutable data object that holds mail server settings
 */
final readonly class MailConfigDTO implements JsonSerializable
{
    public const MAILER_SMTP = 'smtp';

    public const MAILER_SENDMAIL = 'sendmail';

    public const MAILER_LOG = 'log';

    public const ENCRYPTION_NONE = '';

    public const ENCRYPTION_TLS = 'tls';

    public const ENCRYPTION_SSL = 'ssl';

    /**
     * @param  string  $mailer  Mailer (smtp, sendmail, log)
     * @param  string  $host  SMTP host
     * @param  int  $port  SMTP port
     * @param  string|null  $username  SMTP authentication username
     * @param  string|null  $password  SMTP authentication password
     * @param  string  $encryption  Encryption method ('', 'tls', 'ssl')
     * @param  string  $fromAddress  Default sender address
     * @param  string|null  $fromName  Default sender name
     * @param  int  $timeout  Connection timeout (seconds)
     */
    public function __construct(
        public string $mailer = self::MAILER_SMTP,
        public string $host = '',
        public int $port = 587,
        public ?string $username = null,
        public ?string $password = null,
        public string $encryption = self::ENCRYPTION_TLS,
        public string $fromAddress = '',
        public ?string $fromName = null,
        public int $timeout = 30,
    ) {}

    /**
     * Whether SMTP
     */
    public function isSmtp(): bool
    {
        return $this->mailer === self::MAILER_SMTP;
    }

    /**
     * Whether authentication is required
     */
    public function requiresAuth(): bool
    {
        return ! empty($this->username) && ! empty($this->password);
    }

    /**
     * Whether encryption is enabled
     */
    public function hasEncryption(): bool
    {
        return ! empty($this->encryption);
    }

    /**
     * Whether settings are valid (minimum settings exist)
     */
    public function isValid(): bool
    {
        if ($this->mailer === self::MAILER_SMTP) {
            return ! empty($this->host) && $this->port > 0 && ! empty($this->fromAddress);
        }

        return ! empty($this->fromAddress);
    }

    /**
     * Serialize to JSON format (excluding password)
     *
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'mailer' => $this->mailer,
            'host' => $this->host,
            'port' => $this->port,
            'username' => $this->username,
            'encryption' => $this->encryption,
            'from_address' => $this->fromAddress,
            'from_name' => $this->fromName,
            'timeout' => $this->timeout,
            // password is intentionally excluded
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
     * Generate DTO from array
     *
     * @param  array<string,mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            mailer: $data['mailer'] ?? $data['mail_mailer'] ?? self::MAILER_SMTP,
            host: $data['host'] ?? $data['mail_host'] ?? '',
            port: (int) ($data['port'] ?? $data['mail_port'] ?? 587),
            username: $data['username'] ?? $data['mail_username'] ?? null,
            password: $data['password'] ?? $data['mail_password'] ?? null,
            encryption: $data['encryption'] ?? $data['mail_encryption'] ?? self::ENCRYPTION_TLS,
            fromAddress: $data['from_address'] ?? $data['mail_from_address'] ?? '',
            fromName: $data['from_name'] ?? $data['mail_from_name'] ?? null,
            timeout: (int) ($data['timeout'] ?? 30),
        );
    }

    /**
     * Generate DTO from system settings
     */
    public static function fromSystemConfig(): self
    {
        return new self(
            mailer: config('mail.default', self::MAILER_SMTP),
            host: config('mail.mailers.smtp.host', ''),
            port: (int) config('mail.mailers.smtp.port', 587),
            username: config('mail.mailers.smtp.username'),
            password: config('mail.mailers.smtp.password'),
            encryption: config('mail.mailers.smtp.encryption', self::ENCRYPTION_TLS),
            fromAddress: config('mail.from.address', ''),
            fromName: config('mail.from.name'),
            timeout: (int) config('mail.mailers.smtp.timeout', 30),
        );
    }

    /**
     * Convert to Laravel mail settings array
     *
     * @return array<string,mixed>
     */
    public function toLaravelConfig(): array
    {
        return [
            'mail.default' => $this->mailer,
            'mail.mailers.smtp.host' => $this->host,
            'mail.mailers.smtp.port' => $this->port,
            'mail.mailers.smtp.username' => $this->username,
            'mail.mailers.smtp.password' => $this->password,
            'mail.mailers.smtp.encryption' => $this->encryption,
            'mail.mailers.smtp.timeout' => $this->timeout,
            'mail.from.address' => $this->fromAddress,
            'mail.from.name' => $this->fromName ?? config('app.name'),
        ];
    }
}
