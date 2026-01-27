<?php

namespace App\Console\Commands;

use App\Models\SecuritySetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CspEnable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dixlase:csp:enable {--mode=development : CSPモード (development/standard/strict)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'CSPを有効化します';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $mode = $this->option('mode');
            
            // モードの検証
            $validModes = ['development', 'standard', 'strict'];
            if (!in_array($mode, $validModes)) {
                $this->error("❌ 無効なモード: {$mode}");
                $this->info("有効なモード: " . implode(', ', $validModes));
                return Command::FAILURE;
            }
            
            // モードを数値に変換
            $modeValue = match($mode) {
                'development' => 0,
                'standard' => 1,
                'strict' => 2,
            };
            
            // CSPを有効化
            SecuritySetting::set('csp_enabled', 1);
            SecuritySetting::set('csp_mode', $modeValue);
            
            // キャッシュをクリア
            Cache::flush();
            
            $this->info("✅ CSPを有効化しました (モード: {$mode})");
            $this->info('📝 ログ: CSPが有効化されました - ' . now());
            
            // ログに記録
            \Log::channel('stack')->warning('CSP有効化コマンド実行', [
                'command' => 'dixlase:csp:enable',
                'mode' => $mode,
                'user' => 'CLI',
                'timestamp' => now(),
            ]);
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ CSPの有効化に失敗しました: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
