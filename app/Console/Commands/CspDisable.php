<?php

namespace App\Console\Commands;

use App\Models\SecuritySetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CspDisable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dixlase:csp:disable';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'CSPを無効化します（緊急復旧用）';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            // CSPを無効化
            SecuritySetting::set('csp_enabled', 0);
            
            // キャッシュをクリア
            Cache::flush();
            
            $this->info('✅ CSPを無効化しました');
            $this->info('📝 ログ: CSPが無効化されました - ' . now());
            
            // ログに記録
            \Log::channel('stack')->warning('CSP無効化コマンド実行', [
                'command' => 'dixlase:csp:disable',
                'user' => 'CLI',
                'timestamp' => now(),
            ]);
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ CSPの無効化に失敗しました: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
