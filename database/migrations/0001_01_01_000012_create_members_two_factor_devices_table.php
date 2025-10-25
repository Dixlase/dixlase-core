<?php

/**
 * This file is part of Dixlase.
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
    protected $table = 'members_two_factor_devices';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $prefix = config('database.connections.' . config('database.default') . '.prefix', '');
        $tableName = $prefix . $this->table;

        Schema::create($tableName, function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->string('token', 255)->unique();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->boolean('approved')->default(false);
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();
            
            $table->index(['token', 'expires_at']);
            $table->index(['member_id', 'approved']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $prefix = config('database.connections.' . config('database.default') . '.prefix', '');
        $tableName = $prefix . $this->table;
        
        Schema::dropIfExists($tableName);
    }
};
