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
     *
     * Tags for the media library (#766): flat, per site, many-to-many with
     * media. Deleting a tag or a media row removes its pivot rows only.
     *
     * The foreign keys are declared here rather than in
     * 0001_01_01_999999_add_foreign_key_constraints.php: released sites have
     * already run that file and never run it again, so keys added there
     * would reach fresh installs only.
     *
     * Added as a new migration because released versions have already run
     * the media create migration. Idempotent.
     */
    public function up(): void
    {
        if (! Schema::hasTable('media_tags')) {
            Schema::create('media_tags', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('site_id')->index();
                $name = $table->string('name', 50);
                // The default utf8mb4_unicode_ci compares は, ば and ハ as
                // equal, so the unique key would refuse distinct tags
                if (in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
                    $name->collation('utf8mb4_bin');
                }
                $table->timestamps();

                $table->unique(['site_id', 'name']);
                $table->foreign('site_id')->references('id')->on('sites')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('media_media_tag')) {
            Schema::create('media_media_tag', function (Blueprint $table) {
                $table->unsignedBigInteger('media_id');
                $table->unsignedBigInteger('media_tag_id')->index();

                $table->primary(['media_id', 'media_tag_id']);
                $table->foreign('media_id')->references('id')->on('media')->cascadeOnDelete();
                $table->foreign('media_tag_id')->references('id')->on('media_tags')->cascadeOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_media_tag');
        Schema::dropIfExists('media_tags');
    }
};
