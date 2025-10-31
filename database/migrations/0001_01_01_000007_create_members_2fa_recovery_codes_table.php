<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    protected $table = 'members_2fa_recovery_codes';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create($this->table, function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->string('code'); // ハッシュ化された回復コード
            $table->timestamp('used_at')->nullable(); // 使用日時
            $table->boolean('disabled')->default(false); // 無効化フラグ
            $table->timestamps();
            
            // インデックス
            $table->index(['member_id', 'disabled', 'used_at'], 'idx_member_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->table);
    }
};
