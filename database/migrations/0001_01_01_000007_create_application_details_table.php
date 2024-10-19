<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    protected $table = 'application_details';

    public function up()
    {
        Schema::create($this->table, function (Blueprint $table) {
            $table->id();
            $table->integer("user_id")->nullable()->default(0);
            $table->integer("application_id")->nullable()->default(0);
            $table->integer("slot_id")->nullable()->default(0);
            $table->integer("slot_category_id")->nullable()->default(0);
            $table->integer("option_category_id")->nullable()->default(0);
            $table->integer("attendance")->nullable()->default(0);
            $table->datetime("canceled_at")->nullable();
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
};
