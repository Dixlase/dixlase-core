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
     * API key management table
     * Manages API keys for external system integration
     */
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            // Bound site for this key. NULL = network key (cross-site,
            // CLI-issued only). Site keys are scoped via BelongsToSite on
            // the ApiKey model.
            $table->unsignedBigInteger('site_id')->nullable()->index();

            // Key name (for identification)
            $table->string('name', 100);

            // API key (stored hashed)
            // Plain text shown only at generation, thereafter only hash is stored
            $table->string('key_hash', 255)->unique();

            // Key prefix (for identification, e.g. dxl_live_, dxl_test_)
            $table->string('key_prefix', 20);

            // Creator
            $table->unsignedBigInteger('created_by')->nullable()->index();

            // Enabled/Disabled
            $table->boolean('is_active')->default(true);

            // Environment (live/test)
            $table->string('environment', 10)->default('live');

            // Permission scope (JSON array)
            // Example: ["read:events", "write:events", "read:translations"]
            $table->json('scopes')->nullable();

            // Rate limit (requests per minute, null for unlimited)
            $table->unsignedInteger('rate_limit')->nullable();

            // Allowed IP list (JSON array, null allows all IPs)
            $table->json('allowed_ips')->nullable();

            // Expiration date (null for no expiration)
            $table->timestamp('expires_at')->nullable();

            // Last used at
            $table->timestamp('last_used_at')->nullable();

            // Usage count
            $table->unsignedBigInteger('usage_count')->default(0);

            // Notes
            $table->text('description')->nullable();

            // Standard timestamps
            $table->timestamps();

            // Indexes
            $table->index(['is_active', 'environment']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
