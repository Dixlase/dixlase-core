<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    protected $table = 'events';

    public function up()
    {
        Schema::create($this->table, function (Blueprint $table) {
            $table->id();
            $table->date('date')->nullable();
            $table->string('title')->nullable();
            $table->text('complete_message_application')->nullable();
            $table->string('email_from')->nullable();
            $table->text('email_from_appearance')->nullable();
            $table->string('email_bcc')->nullable();
            $table->string('email_cancel')->nullable();
            $table->string('email_title_application')->nullable();
            $table->text('email_header_application')->nullable();
            $table->text('email_footer_application')->nullable();
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
