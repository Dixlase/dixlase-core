<?php

/**
 * This file is part of Your Software Name.
 *
 * Copyright (C) 2024 exc-D inc.
 * Website: https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSlotsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    protected $table = 'slots';

    public function up()
    {


        Schema::create($this->table, function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->text("content")->nullable();
            $table->smallInteger('status')->nullable()->default(0);
            $table->smallInteger("stop")->nullable()->default(0);
            $table->integer('schedule')->nullable()->default(0);
            $table->integer('option_id')->nullable()->default(0);
            $table->string("image")->nullable();
            $table->smallInteger("created_by")->nullable();
            $table->smallInteger("edited_by")->nullable();
            $table->datetime("release_date")->nullable();
            $table->datetime("close_date")->nullable();
            $table->datetime("start_date")->nullable();
            $table->datetime("end_date")->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('classes');
    }
}
