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

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Audit Log table
     *
     * Purpose:
     * - Record who / when / from where / on what / did what
     * - Always record security and settings changes (design oriented toward tamper prevention)
     * - Plugins can also add logs in the same format
     * - Tamper detection via hash chain
     * - Immutability via daily signature
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            // ========================================
            // Occurrence time
            // ========================================
            // Kept separately from created_at (less prone to discrepancy even with batch writes)
            $table->timestamp('occurred_at')->useCurrent()->index();

            // ========================================
            // Level / Result
            // ========================================
            // severity: info, notice, warning, error, critical, alert, emergency
            $table->string('severity', 20)->default('info')->index();
            // outcome: success, failure, denied, pending, unknown
            $table->string('outcome', 20)->default('success')->index();

            // Multisite scope. Site-scoped events carry the site id; system /
            // network-wide events (cron, install, maintenance, etc.) leave it
            // null. Foreign key added in 999999.
            $table->unsignedBigInteger('site_id')->nullable()->index();

            // ========================================
            // Type / Action
            // ========================================
            // category: auth, account, device, security, session, extension, content, system, plugin
            $table->string('category', 50)->index();
            // action: login, logout, update_profile, install_plugin, etc.
            $table->string('action', 100)->index();

            // ========================================
            // Actor - Polymorphic
            // ========================================
            // actor_type: App\Models\Member, App\Models\User, etc.
            // actor_id: ID of the actor
            $table->nullableMorphs('actor');
            // Snapshot of display name (for later reference)
            $table->string('actor_name', 191)->nullable();
            // Actual operator ID during impersonation or proxy operation
            $table->unsignedBigInteger('impersonated_by_id')->nullable();

            // ========================================
            // Target - Polymorphic
            // ========================================
            // target_type: App\Models\Member, App\Models\Plugin, etc.
            // target_id: ID of the target
            $table->nullableMorphs('target');
            // Name or identifier of the target (email address, title, etc.)
            $table->string('target_label', 191)->nullable();

            // ========================================
            // Request context
            // ========================================
            $table->string('ip_address', 45)->nullable()->index(); // IPv6 compatible
            $table->string('user_agent', 500)->nullable();
            // Link related logs within a single request
            $table->string('request_id', 100)->nullable()->index();
            $table->string('session_id', 255)->nullable()->index();

            // ========================================
            // Plugin information (null for Core operations)
            // ========================================
            $table->string('plugin_name', 100)->nullable()->index();
            $table->string('plugin_version', 50)->nullable();

            // ========================================
            // Operation source channel / AI operation flag
            // ========================================
            // actor_source: web, api, cli, scheduler, ai_plugin, webhook, queue
            $table->string('actor_source', 20)->nullable()->index();
            // AI-generated operation flag (denormalized for fast filtering)
            $table->boolean('is_ai_generated')->default(false)->index();

            // ========================================
            // Optional additional information (JSON)
            // ========================================
            // Structure example:
            // {
            //   "message": "description text",
            //   "before": { "email": "old@example.com" },
            //   "after": { "email": "new@example.com" },
            //   "diff": { "email": { "from": "old", "to": "new" } },
            //   "meta": { "http_method": "POST", "url": "/admin/...", "extra": {} }
            // }
            $table->json('context')->nullable();

            // ========================================
            // Format version
            // ========================================
            $table->unsignedSmallInteger('schema_version')->default(1);

            // ========================================
            // Hash chain columns (for tamper detection)
            // ========================================

            // Hash of this record (SHA-256, 64 characters)
            // Calculation target: occurred_at + severity + outcome + category + action + actor_type + actor_id + target_type + target_id + ip_address + context + previous_hash
            $table->string('record_hash', 64)->nullable();

            // Hash of previous record (for chain formation)
            // First record is 'genesis' or null
            $table->string('previous_hash', 64)->nullable();

            // Chain sequence number (serial, used for verification)
            $table->unsignedBigInteger('chain_sequence')->nullable();

            // Hash algorithm (recorded for future changes)
            $table->string('hash_algorithm', 20)->default('sha256');

            // Verification status (last verification result)
            // null: unverified, valid: verified OK, invalid: tampering detected, skipped: skipped
            $table->string('verification_status', 20)->nullable();

            // Last verification timestamp
            $table->timestamp('last_verified_at')->nullable();

            // Standard timestamps
            $table->timestamps();

            // ========================================
            // Composite index for common query patterns
            // ========================================
            $table->index(['actor_type', 'actor_id', 'occurred_at']);
            $table->index(['target_type', 'target_id', 'occurred_at']);
            $table->index(['category', 'action', 'occurred_at']);
            $table->index(['category', 'severity', 'occurred_at']);
            $table->index(['ip_address', 'action', 'occurred_at']);
            $table->index(['plugin_name', 'action', 'occurred_at']);

            // Index for AI operation and source queries
            $table->index(['is_ai_generated', 'category', 'occurred_at']);
            $table->index(['actor_source', 'occurred_at']);

            // Index for hash chain
            $table->index('record_hash');
            $table->index('previous_hash');
            $table->index('chain_sequence');
            $table->index('verification_status');
        });

        // ========================================
        // Daily signature table (daily immutability for audit logs)
        // ========================================
        Schema::create('audit_log_daily_seals', function (Blueprint $table) {
            $table->id();

            // Target date (YYYY-MM-DD)
            $table->date('seal_date')->unique();

            // First and last log IDs of the day
            $table->unsignedBigInteger('first_log_id');
            $table->unsignedBigInteger('last_log_id');

            // Log count for the day
            $table->unsignedInteger('log_count');

            // Final hash of the day (end of chain)
            $table->string('final_hash', 64);

            // Daily signature (HMAC-SHA256)
            // Calculation input: seal_date + first_log_id + last_log_id + log_count + final_hash
            $table->string('daily_signature', 64);

            // Version of key used for signature (for key rotation)
            $table->unsignedSmallInteger('key_version')->default(1);

            // Signature algorithm
            $table->string('signature_algorithm', 30)->default('hmac-sha256');

            // Verification status
            $table->string('verification_status', 20)->default('valid');

            // Last verification timestamp
            $table->timestamp('last_verified_at')->nullable();

            // Metadata (verification history, etc.)
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index('seal_date');
            $table->index('verification_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_log_daily_seals');
        Schema::dropIfExists('audit_logs');
    }
};
