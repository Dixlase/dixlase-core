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
    protected $table = 'custom_roles';

    /**
     * Run the migrations.
     *
     * Custom roles table
     * Inherits preset roles (MemberRole enum) and
     * defines custom roles with additional or restricted permissions
     * AI-specific roles and service roles are also managed here
     */
    public function up(): void
    {
        Schema::create($this->table, function (Blueprint $table) {
            $table->id();

            // Slug (unique identifier) e.g. ai_content_writer, store_manager
            $table->string('slug', 100)->unique();

            // Multilingual name {"en": "AI Content Writer", "ja": "AI Content Writer"}
            $table->json('name');

            // Multilingual description
            $table->json('description')->nullable();

            // MemberRole enum value of the inherited preset role
            // e.g. MemberRole::EDITOR->value (8)
            // Custom role defines differences based on this preset's permissions
            $table->unsignedTinyInteger('base_role');

            // Actor type: human / ai / service
            $table->string('actor_type', 20)->default('human');

            // UI display order
            $table->unsignedSmallInteger('sort_order')->default(0);

            // Badge color (e.g. #3B82F6)
            $table->string('color', 7)->nullable();

            // Maximum number of members assignable to this role (null=unlimited)
            $table->unsignedInteger('max_members')->nullable();

            // Active/inactive flag
            $table->boolean('is_active')->default(true);

            // Creator (foreign key constraint added in add_foreign_key_constraints)
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('actor_type');
            $table->index('base_role');
            $table->index('is_active');
            $table->index('sort_order');
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
