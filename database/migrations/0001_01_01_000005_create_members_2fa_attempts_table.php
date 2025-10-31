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
        Schema::create('members_2fa_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->string('attempt_type', 20); // 'email', 'passkey', 'recovery_code'
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->boolean('success')->default(false);
            $table->timestamp('created_at');
            
            // インデックス
            $table->index(['member_id', 'created_at'], 'idx_member_created');
            $table->index(['ip_address', 'created_at'], 'idx_ip_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members_2fa_attempts');
    }
};
