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
     * Structured storage table for security events
     * Stores critical security events in DB as part of zero trust infrastructure
     */
    public function up(): void
    {
        Schema::create('security_events', function (Blueprint $table) {
            $table->id();

            // Multisite scope. Site-scoped events carry the site id;
            // network-wide / system events leave it null.
            $table->unsignedBigInteger('site_id')->nullable()->index();

            // Event originator (nullable: null for system events)
            $table->unsignedBigInteger('member_id')->nullable()->index();

            // Event type
            $table->string('event_type', 50)->index();

            // Event category (for filtering)
            $table->string('category', 20)->index();

            // Risk level
            $table->string('risk_level', 10)->default('low')->index();

            // Access source information
            $table->string('ip_address', 45)->nullable()->index();
            $table->text('user_agent')->nullable();

            // Device information
            $table->unsignedBigInteger('device_id')->nullable()->index();

            // Session information
            $table->string('session_id', 255)->nullable()->index();

            // Event details (JSON)
            $table->json('meta')->nullable();

            // Event occurrence timestamp
            $table->timestamp('occurred_at')->useCurrent()->index();

            // Standard timestamps
            $table->timestamps();

            // Composite index
            $table->index(['member_id', 'event_type', 'occurred_at']);
            $table->index(['ip_address', 'event_type', 'occurred_at']);
            $table->index(['category', 'risk_level', 'occurred_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_events');
    }
};
