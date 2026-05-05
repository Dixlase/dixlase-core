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

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * API request logs table
     * Used as foundation for API rate limiting feature in beta version
     */
    public function up(): void
    {
        Schema::create('api_request_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('site_id')->index();

            // ========================================
            // API key information
            // ========================================

            // API key ID (foreign key)
            $table->unsignedBigInteger('api_key_id')->nullable()->index();

            // API key prefix (retained for identification even after key deletion)
            $table->string('api_key_prefix', 20)->nullable()->index();

            // ========================================
            // Request information
            // ========================================

            // HTTP method (GET, POST, PUT, DELETE, etc.)
            $table->string('method', 10)->index();

            // Endpoint (e.g. /api/v1/events)
            $table->string('endpoint', 255)->index();

            // Request path (excluding query parameters)
            $table->string('path', 500)->nullable();

            // Query parameters (JSON)
            $table->json('query_params')->nullable();

            // Request body size (bytes)
            $table->unsignedInteger('request_size')->nullable();

            // ========================================
            // Response information
            // ========================================

            // HTTP status code
            $table->unsignedSmallInteger('response_code')->index();

            // Response size (bytes)
            $table->unsignedInteger('response_size')->nullable();

            // Processing time (milliseconds)
            $table->unsignedInteger('response_time_ms')->nullable();

            // ========================================
            // Client information
            // ========================================

            // IP address
            $table->string('ip_address', 45)->index();

            // User-Agent
            $table->string('user_agent', 500)->nullable();

            // ========================================
            // Rate limit information
            // ========================================

            // Rate limit exceeded flag
            $table->boolean('rate_limited')->default(false)->index();

            // Current rate (requests per minute)
            $table->unsignedInteger('current_rate')->nullable();

            // ========================================
            // Error information
            // ========================================

            // Error code (application-specific)
            $table->string('error_code', 50)->nullable()->index();

            // Error message
            $table->text('error_message')->nullable();

            // ========================================
            // Timestamp
            // ========================================

            // Request datetime (indexed)
            $table->timestamp('requested_at')->useCurrent()->index();

            // Standard timestamp
            $table->timestamps();

            // ========================================
            // Index
            // ========================================

            // For rate limit calculation (API key + time range)
            $table->index(['api_key_id', 'requested_at'], 'idx_api_rate_limit');

            // For rate limit by IP
            $table->index(['ip_address', 'requested_at'], 'idx_api_ip_rate');

            // For endpoint analysis
            $table->index(['endpoint', 'method', 'requested_at'], 'idx_api_endpoint_analysis');

            // For error analysis
            $table->index(['response_code', 'requested_at'], 'idx_api_error_analysis');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_request_logs');
    }
};
