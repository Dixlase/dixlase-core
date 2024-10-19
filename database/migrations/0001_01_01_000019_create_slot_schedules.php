<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSlotSchedules extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    protected $table = 'slot_schedules';

    public function up()
    {
        Schema::create($this->table, function (Blueprint $table) {
            $table->id();
            $table->integer('slot_id')->comment('予約枠ID');
            $table->date("start_date")->nullable()->comment('開始日');
            $table->date("end_date")->nullable()->comment('終了日');
            $table->time("strart_time")->nullable()->comment('開始時間');
            $table->time("end_time")->nullable()->comment('終了時間');
            $table->integer("all_days")->nullable()->comment('終日');
            $table->integer("repeat")->nullable()->comment('繰り返し');
            $table->string("repeat_days")->nullable()->comment('繰り返しの曜日');
            $table->string("repeat_weeks")->nullable()->comment('繰り返しの週間');
            $table->integer("repeat_count")->nullable()->comment('繰り返しの回数');
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
