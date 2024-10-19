<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSlotEvents extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    protected $table = 'slot_events';

    public function up()
    {
        Schema::create($this->table, function (Blueprint $table) {
            $table->id();
            $table->integer('slot_id')->comment('予約枠ID');
            $table->integer("event_id")->nullable()->comment('イベントID');
            $table->integer("timetable_id")->nullable()->comment('イベントID');
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
