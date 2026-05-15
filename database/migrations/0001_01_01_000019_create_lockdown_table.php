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

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Emergency lockdown feature table
     *
     * Purpose:
     * - Immediate system protection during security incidents
     * - Automatic lockdown upon unauthorized access detection
     * - Lockdown history recording
     */
    public function up(): void
    {
        // Lockdown status table
        Schema::create('lockdown_status', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('site_id')->index();

            // Lockdown type
            // full: Block all access (except SUPER_ADMIN)
            // admin: Lock admin panel only
            // api: Lock API only
            // login: Lock login only
            $table->string('type', 20)->default('full');

            // Enabled/Disabled
            $table->boolean('is_active')->default(false);

            // Lockdown reason
            $table->string('reason', 500)->nullable();

            // Triggered by (null=auto-triggered)
            $table->unsignedBigInteger('triggered_by')->nullable();

            // Triggered at
            $table->timestamp('triggered_at')->nullable();

            // Released by
            $table->unsignedBigInteger('released_by')->nullable();

            // Released at
            $table->timestamp('released_at')->nullable();

            // Auto-release time (when set)
            $table->timestamp('auto_release_at')->nullable();

            // Allowed IP addresses (JSON array)
            $table->json('allowed_ips')->nullable();

            // Allowed member IDs (JSON array)
            $table->json('allowed_members')->nullable();

            // Meta information (trigger details, etc.)
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index('is_active');
            $table->index('type');
            $table->index('triggered_at');
        });

        // Lockdown history table
        Schema::create('lockdown_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('site_id')->index();

            // Action: activated, deactivated, extended, modified
            $table->string('action', 20);

            // Lockdown type
            $table->string('type', 20);

            // Reason
            $table->string('reason', 500)->nullable();

            // Executor (null=automatic/system)
            $table->unsignedBigInteger('performed_by')->nullable();

            // IP address
            $table->string('ip_address', 45)->nullable();

            // Details
            $table->json('details')->nullable();

            $table->timestamp('performed_at')->useCurrent();

            $table->index('action');
            $table->index('type');
            $table->index('performed_at');
        });

        // Lockdown trigger settings table
        Schema::create('lockdown_triggers', function (Blueprint $table) {
            $table->id();

            // Trigger name
            $table->string('name', 100);

            // Trigger type
            // failed_logins: failed login attempts
            // suspicious_activity: suspicious activity
            // file_integrity: file integrity violation
            // manual: manual only
            $table->string('trigger_type', 50);

            // Enabled/Disabled
            $table->boolean('is_enabled')->default(false);

            // Threshold (meaning varies by trigger type)
            $table->unsignedInteger('threshold')->default(0);

            // Time window (minutes)
            $table->unsignedInteger('time_window_minutes')->default(60);

            // Lockdown type to trigger
            $table->string('lockdown_type', 20)->default('login');

            // Time until auto-release (minutes, 0=manual release only)
            $table->unsignedInteger('auto_release_minutes')->default(0);

            // Whether to send notification
            $table->boolean('notify')->default(true);

            $table->timestamps();

            $table->unique('trigger_type');
            $table->index('is_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lockdown_triggers');
        Schema::dropIfExists('lockdown_history');
        Schema::dropIfExists('lockdown_status');
    }
};
