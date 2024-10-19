<?php

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
