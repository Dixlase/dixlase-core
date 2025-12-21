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
        Schema::table('webhook_deliveries', function (Blueprint $table) {
            // 冪等性のためのイベントID（UUID）
            $table->uuid('event_id')->after('id')->nullable();
            
            // リプレイ防止のためのnonce
            $table->string('nonce', 64)->after('event_id')->nullable();
            
            // デッドレター関連
            $table->boolean('is_dead_letter')->default(false)->after('error_message');
            $table->timestamp('dead_letter_at')->nullable()->after('is_dead_letter');
            $table->boolean('dead_letter_notified')->default(false)->after('dead_letter_at');
            
            // インデックス
            $table->index('event_id');
            $table->index('nonce');
            $table->index('is_dead_letter');
        });

        // デッドレターキュー用テーブル（失敗したWebhookの詳細記録）
        Schema::create('webhook_dead_letters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webhook_id')->constrained()->onDelete('cascade');
            $table->foreignId('delivery_id')->constrained('webhook_deliveries')->onDelete('cascade');
            $table->uuid('event_id');
            $table->string('event', 100);
            $table->json('payload');
            $table->json('request_headers')->nullable();
            $table->text('last_error');
            $table->unsignedTinyInteger('total_attempts');
            $table->json('attempt_log')->nullable(); // 各試行の詳細ログ
            $table->timestamp('first_attempt_at');
            $table->timestamp('last_attempt_at');
            $table->boolean('notified')->default(false);
            $table->timestamp('notified_at')->nullable();
            $table->boolean('manually_retried')->default(false);
            $table->timestamp('manually_retried_at')->nullable();
            $table->unsignedBigInteger('manually_retried_by')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();

            $table->index('event_id');
            $table->index('event');
            $table->index('notified');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_dead_letters');

        Schema::table('webhook_deliveries', function (Blueprint $table) {
            $table->dropIndex(['event_id']);
            $table->dropIndex(['nonce']);
            $table->dropIndex(['is_dead_letter']);
            
            $table->dropColumn([
                'event_id',
                'nonce',
                'is_dead_letter',
                'dead_letter_at',
                'dead_letter_notified',
            ]);
        });
    }
};
