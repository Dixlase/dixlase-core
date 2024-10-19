<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    protected $table = 'users';

    public function up()
    {
        Schema::table($this->table, function (Blueprint $table) {
            $table->string('last_name')->nullable()->after('name');
            $table->string('first_name')->nullable()->after('last_name');
            $table->string('last_name_kana')->nullable()->after('first_name');
            $table->string('first_name_kana')->nullable()->after('last_name_kana');
            $table->string('zip_code')->nullable()->after('first_name_kana');
            $table->string('pref')->nullable()->after('zip_code');
            $table->string('city')->nullable()->after('pref');
            $table->string('address')->nullable()->after('city');
            $table->string('gender')->nullable()->after('address');
            $table->integer('birth_year')->nullable()->after('gender');
            $table->integer('birth_month')->nullable()->after('birth_year');
            $table->integer('birth_day')->nullable()->after('birth_month');
            $table->string('tel')->nullable()->after('birth_day');
            $table->datetime('logged_in_at')->nullable()->after('email_verified_at');
            $table->datetime('previous_logged_in_at')->nullable()->after('logged_in_at');
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
        Schema::table('users', function (Blueprint $table) {
            Schema::dropIfExists('last_name');
            Schema::dropIfExists('first_name');
            Schema::dropIfExists('last_name_kana');
            Schema::dropIfExists('first_name_kana');
            Schema::dropIfExists('zip_code');
            Schema::dropIfExists('pref');
            Schema::dropIfExists('city');
            Schema::dropIfExists('address');
            Schema::dropIfExists('gender');
            Schema::dropIfExists('birth_year');
            Schema::dropIfExists('birth_month');
            Schema::dropIfExists('birth_day');
            Schema::dropIfExists('grade');
            Schema::dropIfExists('school');
            Schema::dropIfExists('school_name');
            Schema::dropIfExists('tel');
            Schema::dropIfExists('departments');
            Schema::dropIfExists('document');
            Schema::dropIfExists('logged_in_at');
            Schema::dropIfExists('previous_logged_in_at');
            Schema::dropIfExists('updated_at');
            Schema::dropIfExists('deleted_at');
        });
    }
}
