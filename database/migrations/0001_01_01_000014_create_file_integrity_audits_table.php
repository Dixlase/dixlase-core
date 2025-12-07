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
        Schema::create('file_integrity_audits', function (Blueprint $table) {
            $table->id();
            $table->uuid('scan_uuid')->unique();

            // スキャン対象範囲
            $table->string('scope', 32)->index(); // core / plugin / theme / all
            $table->string('scope_identifier', 191)->nullable()->index(); // プラグイン名、テーマ名など

            // トリガー情報
            $table->string('trigger', 32)->index(); // manual / schedule / install / update
            $table->string('initiated_by_type', 32)->default('system'); // user / cli / system
            $table->unsignedBigInteger('initiated_by_id')->nullable()->index();

            // 結果ステータス
            $table->string('status', 16)->index(); // ok / warning / critical

            // スキャン設定
            $table->string('hash_algo', 16)->default('sha256');
            $table->string('baseline_version', 32)->nullable(); // 比較に使用したbaselineのバージョン

            // 集計結果
            $table->unsignedInteger('total_files_scanned')->default(0);
            $table->unsignedInteger('changed_files_count')->default(0);
            $table->unsignedInteger('added_files_count')->default(0);
            $table->unsignedInteger('removed_files_count')->default(0);
            $table->unsignedInteger('suspicious_files_count')->default(0);

            // 実行時間
            $table->dateTime('started_at')->index();
            $table->dateTime('finished_at')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();

            // 詳細情報
            $table->text('summary')->nullable();
            $table->json('result_payload')->nullable();

            $table->timestamps();

            // 外部キー
            $table->foreign('initiated_by_id')
                ->references('id')
                ->on('members')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_integrity_audits');
    }
};
