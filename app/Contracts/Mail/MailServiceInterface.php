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

namespace App\Contracts\Mail;

use App\DTO\Mail\MailConfigDTO;
use App\DTO\Mail\MailMessageDTO;
use App\DTO\Mail\MailResultDTO;

/**
 * Mail service contract
 *
 * Provides a unified interface for using mail functionality
 * from Core and plugins
 */
interface MailServiceInterface
{
    /**
     * Send an email
     *
     * @param  MailMessageDTO  $message  Mail message
     * @param  MailConfigDTO|null  $config  Custom settings (use system settings if null)
     */
    public function send(MailMessageDTO $message, ?MailConfigDTO $config = null): MailResultDTO;

    /**
     * Send multiple emails in bulk
     *
     * @param  array<MailMessageDTO>  $messages  Array of mail messages
     * @param  MailConfigDTO|null  $config  Custom settings
     * @return array<MailResultDTO>
     */
    public function sendMany(array $messages, ?MailConfigDTO $config = null): array;

    /**
     * Add email to queue (asynchronous sending)
     *
     * @param  MailMessageDTO  $message  Mail message
     * @param  MailConfigDTO|null  $config  Custom settings
     * @param  string|null  $queue  Queue name
     */
    public function queue(MailMessageDTO $message, ?MailConfigDTO $config = null, ?string $queue = null): MailResultDTO;

    /**
     * Test SMTP connection
     *
     * @param  MailConfigDTO  $config  Mail settings
     */
    public function testConnection(MailConfigDTO $config): MailResultDTO;

    /**
     * Get current mail settings
     */
    public function getConfig(): MailConfigDTO;

    /**
     * Whether mail settings are valid
     */
    public function isConfigured(): bool;
}
