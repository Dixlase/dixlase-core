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
     * Webhook registration table
     *
     * Purpose:
     * - Event notification settings to external services
     * - Sending signed HTTP requests
     * - Retry and timeout settings
     */
    public function up(): void
    {
        Schema::create('webhooks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('site_id')->index();
            $table->string('name', 100);
            $table->string('url', 2048);
            $table->string('secret', 128);
            $table->json('events')->nullable(); // List of subscribed events
            $table->boolean('is_active')->default(true);
            $table->string('environment', 16)->default('live'); // live / test
            $table->unsignedInteger('timeout')->default(30); // Timeout seconds
            $table->unsignedInteger('retry_count')->default(3); // Retry count
            $table->json('headers')->nullable(); // Custom headers
            $table->text('description')->nullable();
            $table->timestamp('last_triggered_at')->nullable();
            $table->unsignedBigInteger('success_count')->default(0);
            $table->unsignedBigInteger('failure_count')->default(0);
            $table->timestamps();

            $table->index('is_active');
            $table->index('environment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhooks');
    }
};
