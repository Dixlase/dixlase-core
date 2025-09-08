<?php

/**
 * This file is part of MySoftware.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
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

    protected $table = 'members';

    public function up(): void
    {
        Schema::create($this->table, function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('email')->unique();
            $table->integer('role')->default(1);   // 1=admin, 2=super_admin, 3=editor, 4=author, 5=contributor
            $table->integer('appearance')->default(0); // 0= auto, 1 = light, 2 = dark
            $table->string('password'); // Hashed
            $table->integer('login_notification_mode')->default(1); // 1= Disabled, 2 = Always, 3 = OnlyNewDevice
            $table->integer('two_factor_mode')->default(1); // 1= Disabled, 2 = Always, 3 = Smart
            $table->integer('two_factor_method')->nullable();
            $table->string('last_login_ip')->nullable();
            $table->text('last_login_ua')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->integer('status')->default(0);  // 0 = inactive, 1 = active
            $table->rememberToken();

            $table->timestamps();
            $table->softDeletes();
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
