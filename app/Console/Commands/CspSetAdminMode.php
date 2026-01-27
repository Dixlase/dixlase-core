<?php

namespace App\Console\Commands;

use App\Models\SecuritySetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CspSetAdminMode extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dixlase:csp:set-admin {mode? : CSPモード (development/standard/strict/same)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '管理画面専用のCSPモードを設定します（sameでフロントと同じモードを使用）';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $mode = $this->argument('mode');
            
            // モードが指定されていない場合は対話的に選択
            if (!$mode) {
                $mode = $this->choice(
                    '管理画面のCSPモードを選択してください',
                    ['same', 'development', 'standard', 'strict'],
                    0
                );
            }
            
            // モードの検証
            $validModes = ['same', 'development', 'standard', 'strict'];
            if (!in_array($mode, $validModes)) {
                $this->error("❌ 無効なモード: {$mode}");
                $this->info("有効なモード: " . implode(', ', $validModes));
                return Command::FAILURE;
            }
            
            // 'same'の場合はnullを設定（フロントと同じモードを使用）
            if ($mode === 'same') {
                SecuritySetting::set('csp_admin_mode', null);
                
                // キャッシュをクリア
                Cache::flush();
                
                $this->info("✅ 管理画面のCSPモードをフロントエンドと同じに設定しました");
                $this->info('📝 ログ: 管理画面CSPモードが変更されました（フロントと同じ） - ' . now());
                
                // ログに記録
                \Log::channel('stack')->warning('管理画面CSPモード変更コマンド実行', [
                    'command' => 'dixlase:csp:set-admin',
                    'mode' => 'same (null)',
                    'user' => 'CLI',
                    'timestamp' => now(),
                ]);
                
                return Command::SUCCESS;
            }
            
            // モードを数値に変換
            $modeValue = match($mode) {
                'development' => 0,
                'standard' => 1,
                'strict' => 2,
            };
            
            // 管理画面CSPモードを変更
            SecuritySetting::set('csp_admin_mode', $modeValue);
            
            // キャッシュをクリア
            Cache::flush();
            
            $this->info("✅ 管理画面のCSPモードを {$mode} に変更しました");
            $this->info('📝 ログ: 管理画面CSPモードが変更されました - ' . now());
            
            // 現在のフロントモードを表示
            $frontMode = SecuritySetting::get('csp_mode', 0);
            $frontModeName = match((int)$frontMode) {
                0 => 'development',
                1 => 'standard',
                2 => 'strict',
                default => 'unknown',
            };
            
            $this->line('');
            $this->info("📊 現在の設定:");
            $this->info("  - フロントエンド: {$frontModeName}");
            $this->info("  - 管理画面: {$mode}");
            
            // ログに記録
            \Log::channel('stack')->warning('管理画面CSPモード変更コマンド実行', [
                'command' => 'dixlase:csp:set-admin',
                'mode' => $mode,
                'front_mode' => $frontModeName,
                'user' => 'CLI',
                'timestamp' => now(),
            ]);
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ 管理画面CSPモードの変更に失敗しました: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
