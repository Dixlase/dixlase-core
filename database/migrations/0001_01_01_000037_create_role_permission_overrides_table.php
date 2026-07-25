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
    protected $table = 'role_permission_overrides';

    /**
     * Run the migrations.
     *
     * Permission settings override table
     * Only stores settings that differ from default permissions (config/roles.php)
     */
    public function up(): void
    {
        Schema::create($this->table, function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('site_id')->index();

            // Source type: core / plugin
            $table->string('source_type', 20)->default('core');

            // Source ID: null for core, slug for plugin
            $table->string('source_id', 100)->nullable();

            // Menu key (e.g., settings.base, pages.index)
            $table->string('menu_key', 255);

            // Edit permission (users with this permission level or higher can access)
            $table->unsignedTinyInteger('access_roles')->nullable();

            // View permission (users with this permission level or higher can view)
            $table->unsignedTinyInteger('view_roles')->nullable();

            // Updater (foreign key constraint added in add_foreign_key_constraints)
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();

            // Unique on site + source_type + source_id + menu_key
            $table->unique(['site_id', 'source_type', 'source_id', 'menu_key'], 'role_override_unique');

            // Index
            $table->index(['source_type', 'source_id'], 'role_override_source_idx');
            $table->index('menu_key', 'role_override_menu_key_idx');
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
