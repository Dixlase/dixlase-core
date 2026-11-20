<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
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
     * Signs backup archives (#635).
     *
     * - `signature`: HMAC-SHA256 of the archive's hash and record fields,
     *   keyed with the integrity key; null for a backup taken without a key
     * - `signature_algorithm`: `hmac-sha256`
     * - `signature_key_version`: version of the integrity key that signed it
     *
     * Added as a new migration because released versions have already run
     * the create migration of `backup_records`. Idempotent.
     */
    public function up(): void
    {
        Schema::table('backup_records', function (Blueprint $table) {
            if (! Schema::hasColumn('backup_records', 'signature')) {
                $table->string('signature', 64)->nullable()->after('hash_algorithm');
            }
            if (! Schema::hasColumn('backup_records', 'signature_algorithm')) {
                $table->string('signature_algorithm', 20)->nullable()->after('signature');
            }
            if (! Schema::hasColumn('backup_records', 'signature_key_version')) {
                $table->unsignedSmallInteger('signature_key_version')->nullable()->after('signature_algorithm');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $columns = array_values(array_filter(
            ['signature', 'signature_algorithm', 'signature_key_version'],
            fn (string $column) => Schema::hasColumn('backup_records', $column),
        ));

        if ($columns !== []) {
            Schema::table('backup_records', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
};
