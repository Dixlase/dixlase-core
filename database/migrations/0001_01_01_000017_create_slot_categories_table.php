<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSlotCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    protected $table = 'slot_categories';

    public function up()
    {
        Schema::create($this->table, function (Blueprint $table) {
            $table->id();
            $table->integer('slot_id');
            $table->integer("option_id")->nullable();
            $table->integer("option_category_id")->nullable();
            $table->integer("capacity")->nullable();
            $table->integer("capacity_infinity")->nullable();
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
