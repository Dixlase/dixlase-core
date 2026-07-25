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
     */
    public function up(): void
    {
        Schema::create('themes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('package_name')->nullable();
            $table->string('directory');
            $table->string('slug')->unique();
            $table->string('namespace')->nullable();
            $table->text('description')->nullable();
            $table->string('license')->nullable();
            $table->string('author')->nullable();
            $table->string('email')->nullable();
            $table->string('url')->nullable();
            $table->string('version')->default('1.0.0');
            $table->boolean('has_settings')->default(false)->comment('Theme settings page availability');
            $table->json('config')->nullable();
            $table->unsignedBigInteger('source_id')->nullable()->index(); // Extension source reference
            $table->string('source_repo')->nullable(); // Repository name at source
            // Update detection state
            $table->string('available_version')->nullable(); // Latest available version from source
            $table->string('last_notified_version', 32)->nullable(); // Suppress duplicate update notifications
            $table->timestamp('available_version_published_at')->nullable(); // Release published date
            $table->string('release_url')->nullable(); // GitHub release page URL
            $table->text('release_notes')->nullable(); // Release notes body (Markdown from GitHub Releases) for inline render on the updates page
            $table->timestamp('last_version_check')->nullable(); // Last update check timestamp
            // Update failure tracking
            $table->timestamp('update_failed_at')->nullable();
            $table->text('update_failure_reason')->nullable();
            // Columns for supply chain attack protection (mirror plugins table)
            $table->string('signing_key_id')->nullable()->index(); // Signing key ID at initial installation
            $table->string('author_id')->nullable()->index(); // author_id from theme.json
            $table->string('authority_key_id')->nullable(); // Authority public key ID
            $table->string('installed_from_url')->nullable(); // Installation source URL
            $table->string('installation_method')->nullable(); // upload/marketplace/cli/github
            $table->timestamp('installed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('themes');
    }
};
