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
 *       (see LICENSE.commercial, or contact office@exc-d.com).
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

namespace App\DTO\Audit;

use App\Events\AuditLogCreated;
use App\Models\AuditLog;
use JsonSerializable;

/**
 * Frozen payload shape for the AuditLogCreated event.
 *
 * This DTO is the canonical contract between the core audit log system and
 * external subscribers (SIEM exporters, plugin event listeners, webhook
 * deliverers). It deliberately exposes a SIEM-relevant subset of the
 * underlying audit_logs table — not the Eloquent model itself — so that
 * column additions, internal integrity fields, and Eloquent relationships
 * cannot leak into the public surface.
 *
 * Compatibility policy:
 *  - Within a major version: fields may only be ADDED. Existing field names,
 *    types, and nullability are frozen. The version property does not change.
 *  - Removing or renaming a field, or changing a non-nullable field to
 *    nullable (or vice versa), bumps {@see AuditLogCreated::SCHEMA_VERSION}.
 *  - Subscribers should branch on the version field if they want to support
 *    multiple major versions in one listener.
 */
final readonly class AuditLogPayload implements JsonSerializable
{
    /**
     * @param  int  $version  Schema version of this payload (mirrors AuditLogCreated::SCHEMA_VERSION at dispatch time)
     * @param  int  $id  Primary key of the audit_logs row
     * @param  string  $occurred_at  ISO 8601 timestamp (e.g. "2026-05-10T12:34:56+00:00")
     * @param  string  $severity  One of AuditLog::SEVERITY_* (debug|info|notice|warning|error|critical|alert|emergency)
     * @param  string  $outcome  One of AuditLog::OUTCOME_* (success|failure|denied|pending|unknown)
     * @param  string  $category  One of AuditLog::CATEGORY_* (auth|account|device|security|session|extension|content|system|plugin|...)
     * @param  string  $action  Audit action key (snake_case, see docs/development/naming.md "Audit log actions")
     * @param  int|null  $site_id  Site scope; null for global / cross-site events
     * @param  string|null  $actor_type  Polymorphic actor class name (e.g. "App\\Models\\Member"); null for system actions
     * @param  int|null  $actor_id  Polymorphic actor primary key; null for system actions
     * @param  string|null  $actor_name  Snapshot of the actor display name at the time of the event
     * @param  int|null  $impersonated_by_id  Real operator ID when impersonation is active
     * @param  string|null  $target_type  Polymorphic target class name
     * @param  int|null  $target_id  Polymorphic target primary key
     * @param  string|null  $target_label  Human-readable target identifier (email, title, slug)
     * @param  string|null  $ip_address  Source IP address (IPv4 or IPv6)
     * @param  string|null  $user_agent  HTTP User-Agent string
     * @param  string|null  $request_id  Request correlation ID
     * @param  string|null  $session_id  Session identifier
     * @param  string|null  $plugin_name  Plugin slug (null for core actions)
     * @param  string|null  $plugin_version  Plugin version (null for core actions)
     * @param  string|null  $actor_source  One of AuditLog::ACTOR_SOURCE_* (web|api|cli|scheduler|ai_plugin|webhook|queue)
     * @param  bool  $is_ai_generated  True when the action originated from an AI plugin
     * @param  array<string,mixed>  $context  Free-form additional context (JSON-serialisable)
     * @param  string|null  $record_hash  SHA-256 of this row in the tamper-evident chain
     * @param  int|null  $chain_sequence  Sequence number within the tamper-evident chain
     */
    public function __construct(
        public int $version,
        public int $id,
        public string $occurred_at,
        public string $severity,
        public string $outcome,
        public string $category,
        public string $action,
        public ?int $site_id,
        public ?string $actor_type,
        public ?int $actor_id,
        public ?string $actor_name,
        public ?int $impersonated_by_id,
        public ?string $target_type,
        public ?int $target_id,
        public ?string $target_label,
        public ?string $ip_address,
        public ?string $user_agent,
        public ?string $request_id,
        public ?string $session_id,
        public ?string $plugin_name,
        public ?string $plugin_version,
        public ?string $actor_source,
        public bool $is_ai_generated,
        public array $context,
        public ?string $record_hash,
        public ?int $chain_sequence,
    ) {}

    /**
     * Build a payload from an AuditLog model instance.
     *
     * Internal columns (previous_hash, hash_algorithm, verification_status,
     * last_verified_at, schema_version) are intentionally NOT exposed.
     * They belong to the integrity-chain implementation, not to subscribers.
     */
    public static function fromAuditLog(AuditLog $auditLog): self
    {
        return new self(
            version: AuditLogCreated::SCHEMA_VERSION,
            id: (int) $auditLog->id,
            occurred_at: $auditLog->occurred_at?->toIso8601String() ?? '',
            severity: (string) $auditLog->severity,
            outcome: (string) $auditLog->outcome,
            category: (string) $auditLog->category,
            action: (string) $auditLog->action,
            site_id: $auditLog->site_id !== null ? (int) $auditLog->site_id : null,
            actor_type: $auditLog->actor_type,
            actor_id: $auditLog->actor_id !== null ? (int) $auditLog->actor_id : null,
            actor_name: $auditLog->actor_name,
            impersonated_by_id: $auditLog->impersonated_by_id !== null ? (int) $auditLog->impersonated_by_id : null,
            target_type: $auditLog->target_type,
            target_id: $auditLog->target_id !== null ? (int) $auditLog->target_id : null,
            target_label: $auditLog->target_label,
            ip_address: $auditLog->ip_address,
            user_agent: $auditLog->user_agent,
            request_id: $auditLog->request_id,
            session_id: $auditLog->session_id,
            plugin_name: $auditLog->plugin_name,
            plugin_version: $auditLog->plugin_version,
            actor_source: $auditLog->actor_source,
            is_ai_generated: (bool) $auditLog->is_ai_generated,
            context: is_array($auditLog->context) ? $auditLog->context : [],
            record_hash: $auditLog->record_hash,
            chain_sequence: $auditLog->chain_sequence !== null ? (int) $auditLog->chain_sequence : null,
        );
    }

    /**
     * Stable array representation for JSON serialisation.
     *
     * Field order is part of the contract: SIEM consumers that hash the
     * canonical JSON form rely on it. New fields added in future minor
     * versions go at the end of the existing keys.
     *
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'version' => $this->version,
            'id' => $this->id,
            'occurred_at' => $this->occurred_at,
            'severity' => $this->severity,
            'outcome' => $this->outcome,
            'category' => $this->category,
            'action' => $this->action,
            'site_id' => $this->site_id,
            'actor_type' => $this->actor_type,
            'actor_id' => $this->actor_id,
            'actor_name' => $this->actor_name,
            'impersonated_by_id' => $this->impersonated_by_id,
            'target_type' => $this->target_type,
            'target_id' => $this->target_id,
            'target_label' => $this->target_label,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'request_id' => $this->request_id,
            'session_id' => $this->session_id,
            'plugin_name' => $this->plugin_name,
            'plugin_version' => $this->plugin_version,
            'actor_source' => $this->actor_source,
            'is_ai_generated' => $this->is_ai_generated,
            'context' => $this->context,
            'record_hash' => $this->record_hash,
            'chain_sequence' => $this->chain_sequence,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
