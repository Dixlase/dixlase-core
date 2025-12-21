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
        // Webhook登録テーブル
        Schema::create('webhooks', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('url', 2048);
            $table->string('secret', 128);
            $table->json('events')->nullable(); // 購読するイベント一覧
            $table->boolean('is_active')->default(true);
            $table->string('environment', 16)->default('live'); // live / test
            $table->unsignedInteger('timeout')->default(30); // タイムアウト秒数
            $table->unsignedInteger('retry_count')->default(3); // リトライ回数
            $table->json('headers')->nullable(); // カスタムヘッダー
            $table->text('description')->nullable();
            $table->timestamp('last_triggered_at')->nullable();
            $table->unsignedBigInteger('success_count')->default(0);
            $table->unsignedBigInteger('failure_count')->default(0);
            $table->timestamps();

            $table->index('is_active');
            $table->index('environment');
        });

        // Webhook配信ログテーブル
        Schema::create('webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webhook_id')->constrained()->onDelete('cascade');
            $table->string('event', 100);
            $table->json('payload');
            $table->string('status', 16); // pending / success / failed / retrying
            $table->unsignedSmallInteger('response_code')->nullable();
            $table->text('response_body')->nullable();
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->unsignedTinyInteger('attempt')->default(1);
            $table->unsignedTinyInteger('max_attempts')->default(3);
            $table->text('error_message')->nullable();
            $table->timestamp('next_retry_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('event');
            $table->index(['webhook_id', 'created_at']);
            $table->index('next_retry_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_deliveries');
        Schema::dropIfExists('webhooks');
    }
};
