<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('members_login_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('identifier')->index(); // email or username
            $table->string('ip_address', 45)->index(); // IPv4 or IPv6
            $table->string('user_agent')->nullable();
            $table->boolean('successful')->default(false);
            $table->timestamp('attempted_at');
            $table->timestamps();
            
            // インデックスを追加してクエリ性能を向上
            $table->index(['identifier', 'attempted_at']);
            $table->index(['ip_address', 'attempted_at']);
            $table->index(['identifier', 'ip_address', 'attempted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members_login_attempts');
    }
};
