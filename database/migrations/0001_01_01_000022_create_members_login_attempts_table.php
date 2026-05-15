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
    protected $table = 'members_login_attempts';

    /**
     * Run the migrations.
     *
     * Login attempts table
     * Used as foundation for behavioral analysis feature in beta version
     */
    public function up(): void
    {
        Schema::create($this->table, function (Blueprint $table) {
            $table->id();
            $table->string('identifier')->index(); // email or username
            $table->string('ip_address', 45)->index(); // IPv4 or IPv6
            $table->string('user_agent')->nullable();
            $table->boolean('successful')->default(false);
            $table->timestamp('attempted_at');
            $table->timestamps();

            // ========================================
            // Columns for behavioral analysis (foundation for beta behavioral analysis)
            // ========================================

            // Login hour of day (0-23) - learn typical login hours
            $table->unsignedTinyInteger('login_hour')->nullable();

            // Login day of week (0=Sunday, 6=Saturday) - learn typical login days
            $table->unsignedTinyInteger('login_day_of_week')->nullable();

            // Device fingerprint (hash of browser information)
            $table->string('device_fingerprint', 64)->nullable()->index();

            // Country code (GeoIP) - learn typical login regions
            $table->string('country_code', 2)->nullable()->index();

            // Time elapsed since last login (seconds) - detect abnormal intervals
            $table->unsignedInteger('seconds_since_last_login')->nullable();

            // Login failure reason (for detailed analysis)
            // invalid_password, account_locked, two_fa_failed, etc.
            $table->string('failure_reason', 50)->nullable();

            // 2FA usage flag
            $table->boolean('used_two_fa')->default(false);

            // 2FA method (email, passkey, recovery_code)
            $table->string('two_fa_method', 20)->nullable();

            // Is login from trusted device
            $table->boolean('from_trusted_device')->default(false);

            // Risk score (0-100) - to be calculated in beta version
            $table->unsignedTinyInteger('risk_score')->nullable();

            // Additional context (JSON) - for future extension
            $table->json('context')->nullable();

            // Member ID (resolved from identifier, null if unknown)
            $table->unsignedBigInteger('member_id')->nullable()->index();

            // Suspected bot/AI scraper flag
            $table->boolean('is_bot_suspected')->default(false)->index();

            // Add indexes to improve query performance (shortened with custom names)
            $table->index(['identifier', 'attempted_at'], 'idx_login_identifier_time');
            $table->index(['ip_address', 'attempted_at'], 'idx_login_ip_time');
            $table->index(['identifier', 'ip_address', 'attempted_at'], 'idx_login_composite');

            // Index for behavioral analysis
            $table->index(['identifier', 'successful', 'attempted_at'], 'idx_login_behavior');
            $table->index(['identifier', 'login_hour'], 'idx_login_hour_pattern');

            // Index for member/bot detection
            $table->index(['member_id', 'attempted_at'], 'idx_login_member_time');
            $table->index(['is_bot_suspected', 'attempted_at'], 'idx_login_bot_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->table);
    }
};
