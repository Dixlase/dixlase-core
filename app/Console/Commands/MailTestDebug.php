<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Traits\MailTestTrait;

class MailTestDebug extends Command
{
    use MailTestTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:test-debug {--host=} {--port=} {--username=} {--password=} {--encryption=} {--from=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'メールサーバー接続テストのデバッグコマンド';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== メールサーバー接続テストデバッグ ===');
        
        // オプションから設定を取得
        $mailSettings = [
            'mail_mailer' => 'smtp',
            'mail_host' => $this->option('host') ?: $this->ask('SMTPホスト名を入力してください'),
            'mail_port' => $this->option('port') ?: $this->ask('ポート番号を入力してください (通常: 587, 465, 25)'),
            'mail_username' => $this->option('username') ?: $this->ask('ユーザー名を入力してください (空の場合はEnter)'),
            'mail_password' => $this->option('password') ?: $this->secret('パスワードを入力してください (空の場合はEnter)'),
            'mail_encryption' => $this->option('encryption') ?: $this->choice('暗号化方式を選択してください', ['', 'tls', 'ssl'], 1),
            'mail_from_address' => $this->option('from') ?: $this->ask('送信元メールアドレスを入力してください'),
        ];

        // 設定内容を表示
        $this->info("\n=== 設定内容 ===");
        $this->table(
            ['設定項目', '値'],
            [
                ['ホスト', $mailSettings['mail_host']],
                ['ポート', $mailSettings['mail_port']],
                ['暗号化', $mailSettings['mail_encryption'] ?: 'なし'],
                ['ユーザー名', $mailSettings['mail_username'] ?: '未設定'],
                ['パスワード', $mailSettings['mail_password'] ? '***設定済み***' : '未設定'],
                ['送信元', $mailSettings['mail_from_address']],
            ]
        );

        $this->info("\n=== 接続テスト開始 ===");

        try {
            // 基本的なネットワーク接続テスト
            $this->info('1. 基本ネットワーク接続テスト...');
            $this->testBasicConnection($mailSettings['mail_host'], $mailSettings['mail_port']);
            $this->info('✓ 基本接続: 成功');

            // SMTP接続テスト
            $this->info('2. SMTP接続テスト...');
            $this->testSmtpConnection($mailSettings);
            $this->info('✓ SMTP接続: 成功');

            $this->info("\n🎉 すべてのテストが成功しました！");

        } catch (\Exception $e) {
            $this->error("\n❌ エラーが発生しました:");
            $this->error($e->getMessage());
            
            $this->info("\n=== トラブルシューティング ===");
            $this->suggestTroubleshooting($mailSettings, $e);
            
            return 1;
        }

        return 0;
    }

    /**
     * 基本的なネットワーク接続テスト
     */
    private function testBasicConnection($host, $port)
    {
        $connection = @fsockopen($host, $port, $errno, $errstr, 5);
        
        if (!$connection) {
            throw new \Exception("基本接続エラー: {$errstr} (エラーコード: {$errno})");
        }
        
        fclose($connection);
    }

    /**
     * トラブルシューティングの提案
     */
    private function suggestTroubleshooting($mailSettings, $exception)
    {
        $message = $exception->getMessage();
        
        $this->info("エラーメッセージ: " . $message);
        $this->info("");
        
        // エラーパターンに応じた対処法を提案
        if (strpos($message, '接続エラー') !== false || strpos($message, 'Connection refused') !== false) {
            $this->warn("🔍 接続拒否エラーの可能性:");
            $this->line("- ホスト名が正しいか確認してください");
            $this->line("- ポート番号が正しいか確認してください");
            $this->line("- ファイアウォールでポートがブロックされていないか確認してください");
            $this->line("- SMTPサーバーが稼働しているか確認してください");
            
        } elseif (strpos($message, 'timeout') !== false || strpos($message, 'timed out') !== false) {
            $this->warn("🔍 タイムアウトエラーの可能性:");
            $this->line("- ネットワーク接続が遅い可能性があります");
            $this->line("- SMTPサーバーが応答していない可能性があります");
            $this->line("- プロキシ設定を確認してください");
            
        } elseif (strpos($message, '認証') !== false || strpos($message, 'AUTH') !== false) {
            $this->warn("🔍 認証エラーの可能性:");
            $this->line("- ユーザー名とパスワードが正しいか確認してください");
            $this->line("- SMTPサーバーで認証が有効になっているか確認してください");
            $this->line("- 2段階認証が有効な場合はアプリパスワードを使用してください");
            
        } elseif (strpos($message, 'TLS') !== false || strpos($message, 'SSL') !== false) {
            $this->warn("🔍 暗号化エラーの可能性:");
            $this->line("- 暗号化方式(TLS/SSL)が正しいか確認してください");
            $this->line("- ポート番号と暗号化方式の組み合わせを確認してください");
            $this->line("  - ポート587: TLS");
            $this->line("  - ポート465: SSL");
            $this->line("  - ポート25: 暗号化なし または TLS");
        }
        
        $this->info("\n=== よくある設定例 ===");
        $this->info("Gmail:");
        $this->line("  ホスト: smtp.gmail.com");
        $this->line("  ポート: 587");
        $this->line("  暗号化: TLS");
        $this->line("");
        $this->info("Outlook/Hotmail:");
        $this->line("  ホスト: smtp-mail.outlook.com");
        $this->line("  ポート: 587");
        $this->line("  暗号化: TLS");
        $this->line("");
        $this->info("Yahoo Mail:");
        $this->line("  ホスト: smtp.mail.yahoo.com");
        $this->line("  ポート: 587 または 465");
        $this->line("  暗号化: TLS または SSL");
    }
}
