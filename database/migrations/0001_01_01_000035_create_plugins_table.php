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
        Schema::create('plugins', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Human-readable name
            $table->string('package_name')->nullable(); // Package name
            $table->string('directory'); // Plugin directory name
            $table->string('slug')->unique(); // Slug name (unique)
            $table->string('namespace'); // Plugin namespace
            $table->text('description')->nullable(); // Plugin description
            $table->string('license')->nullable(); // License
            $table->string('author')->nullable(); // Author
            $table->string('email')->nullable(); // Author email
            $table->string('url')->nullable(); // Author website
            $table->string('version'); // Version
            $table->unsignedBigInteger('source_id')->nullable()->index(); // Extension source reference
            $table->string('source_repo')->nullable(); // Repository name at source
            // Update detection state
            $table->string('available_version')->nullable(); // Latest available version from source
            $table->string('last_notified_version', 32)->nullable(); // Suppress duplicate update notifications
            $table->timestamp('available_version_published_at')->nullable(); // Release published date (for "released N days ago")
            $table->string('release_url')->nullable(); // GitHub release page URL (notes fetched on demand)
            $table->text('release_notes')->nullable(); // Release notes body (Markdown from GitHub Releases) for inline render on the updates page
            $table->timestamp('last_version_check')->nullable(); // Last update check timestamp
            // Update failure tracking
            $table->timestamp('update_failed_at')->nullable(); // Last update attempt failure timestamp
            $table->text('update_failure_reason')->nullable(); // Last update failure reason
            // Columns for supply chain attack protection
            $table->string('signing_key_id')->nullable()->index(); // Signing key ID at initial installation
            $table->string('author_id')->nullable()->index(); // author_id from plugin.json
            $table->string('authority_key_id')->nullable(); // Authority public key ID (identifies the distributor's Ed25519 key)
            $table->string('installed_from_url')->nullable(); // Installation source URL
            $table->string('installation_method')->nullable(); // upload/marketplace/cli/github
            $table->timestamp('installed_at')->nullable(); // Installation datetime
            $table->timestamp('enabled_at')->nullable(); // Activation datetime
            $table->timestamps(); // Laravel's `created_at` & `updated_at`
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plugins');
    }
};
