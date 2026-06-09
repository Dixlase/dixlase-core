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
     * Trusted devices management table
     * As part of zero trust infrastructure, identifies devices and determines known/unknown status
     */
    public function up(): void
    {
        Schema::create('members_trusted_devices', function (Blueprint $table) {
            $table->id();

            // Owner
            $table->unsignedBigInteger('member_id')->index();

            // Device identification token (stored as hash)
            $table->string('token', 255)->nullable()->index();

            // Device name (user-friendly identifier)
            $table->string('device_name', 100)->nullable();

            // Access source information
            $table->string('ip_address', 45)->nullable(); // IPv6 compatible
            $table->text('user_agent')->nullable();
            $table->string('user_agent_hash', 64)->nullable(); // Hash for indexing

            // Trust level
            // trusted: trusted (registered after 2FA completion)
            // unknown: unknown (first access)
            // blocked: blocked (explicitly blocked by user)
            $table->string('trust_level', 20)->default('trusted');

            // IP at first access (for change detection)
            $table->string('first_ip', 45)->nullable();

            // IP at last access
            $table->string('last_ip', 45)->nullable();

            // Last used at
            $table->timestamp('last_used_at')->nullable();

            // Standard timestamps
            $table->timestamps();

            // Indexes
            $table->index(['member_id', 'trust_level'], 'mtd_member_trust_idx');
            $table->index(['member_id', 'ip_address', 'user_agent_hash'], 'mtd_member_ip_ua_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members_trusted_devices');
    }
};
