<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEventTimetablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    protected $table = 'event_timetables';

    public function up()
    {
        Schema::create($this->table, function (Blueprint $table) {
            $table->id();
            $table->string("title")->nullable();
            $table->smallInteger("category")->nullable();
            $table->time("start")->nullable();
            $table->time("end")->nullable();
            $table->smallInteger("created_by")->nullable();
            $table->smallInteger("edited_by")->nullable();
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
        Schema::dropIfExists($this->table);
    }
}
