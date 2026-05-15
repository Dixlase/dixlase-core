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

declare(strict_types=1);

namespace App\Events;

use App\DTO\Audit\AuditLogPayload;
use App\Models\AuditLog;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired after every audit log record is created.
 *
 * Designed for SIEM integration, external monitoring, and plugin hooks.
 *
 * Subscribers receive an immutable {@see AuditLogPayload} DTO that exposes a
 * SIEM-relevant subset of the audit_logs row. The DTO shape is frozen under
 * {@see self::SCHEMA_VERSION}; see docs/development/api-reference/events.md
 * for the field-by-field schema and the compatibility policy.
 *
 * Example:
 * ```php
 * Event::listen(AuditLogCreated::class, function (AuditLogCreated $event) {
 *     if ($event->payload->severity === AuditLog::SEVERITY_CRITICAL) {
 *         // forward to SIEM
 *         Http::post($siem, $event->payload->toArray());
 *     }
 * });
 * ```
 */
class AuditLogCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Schema version of the {@see AuditLogPayload} DTO carried by this event.
     *
     * Subscribers may branch on `$event->payload->version` (which mirrors this
     * constant at dispatch time) to support multiple major versions in one
     * listener. See docs/development/api-reference/events.md "AuditLogCreated
     * payload schema" for the compatibility policy.
     */
    public const SCHEMA_VERSION = 1;

    public function __construct(
        public readonly AuditLogPayload $payload,
    ) {}

    /**
     * Convenience factory: build the event directly from an AuditLog model.
     *
     * Core call sites should prefer this over constructing an
     * {@see AuditLogPayload} by hand.
     */
    public static function fromAuditLog(AuditLog $auditLog): self
    {
        return new self(AuditLogPayload::fromAuditLog($auditLog));
    }
}
