<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Enums\LogLevel;

class TestErrorNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:test-error-notification 
                            {--level=error : Log level to test (emergency, alert, critical, error, warning, notice, info, debug)}
                            {--message= : Custom error message}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test system error notification functionality by generating test log entries';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $level = $this->option('level');
        $customMessage = $this->option('message');

        // ログレベルの検証
        $validLevels = ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'];
        if (!in_array($level, $validLevels)) {
            $this->error("Invalid log level: {$level}");
            $this->info("Valid levels: " . implode(', ', $validLevels));
            return 1;
        }

        // テストメッセージの生成
        $message = $customMessage ?: "Test {$level} notification from admin:test-error-notification command";
        
        // コンテキスト情報
        $context = [
            'test_command' => true,
            'timestamp' => now()->toISOString(),
            'user_agent' => 'Artisan Command',
            'ip_address' => '127.0.0.1',
            'url' => 'artisan:admin:test-error-notification',
            'method' => 'CLI',
            'additional_info' => [
                'command_options' => [
                    'level' => $level,
                    'custom_message' => $customMessage ? true : false,
                ],
                'system_info' => [
                    'php_version' => PHP_VERSION,
                    'laravel_version' => app()->version(),
                    'environment' => app()->environment(),
                ]
            ]
        ];

        $this->info("Generating test {$level} log entry...");
        $this->info("Message: {$message}");

        // ログレベルに応じてログを出力（notificationチャンネルに直接送信）
        switch ($level) {
            case 'emergency':
                Log::channel('notification')->emergency($message, $context);
                break;
            case 'alert':
                Log::channel('notification')->alert($message, $context);
                break;
            case 'critical':
                Log::channel('notification')->critical($message, $context);
                break;
            case 'error':
                Log::channel('notification')->error($message, $context);
                break;
            case 'warning':
                Log::channel('notification')->warning($message, $context);
                break;
            case 'notice':
                Log::channel('notification')->notice($message, $context);
                break;
            case 'info':
                Log::channel('notification')->info($message, $context);
                break;
            case 'debug':
                Log::channel('notification')->debug($message, $context);
                break;
        }

        $this->info("Test log entry has been generated.");
        
        // 現在の通知設定を表示
        $this->displayNotificationSettings();

        return 0;
    }

    /**
     * Display current notification settings
     */
    private function displayNotificationSettings()
    {
        $this->info("\n--- Current Notification Settings ---");
        
        try {
            $notificationEnabled = \DB::table('security_settings')
                ->where('name', 'notification_enabled')
                ->value('value');
            
            $notificationLevels = \DB::table('security_settings')
                ->where('name', 'notification_log_levels')
                ->value('value');

            $this->info("Notification Enabled: " . ($notificationEnabled ? 'Yes' : 'No'));
            
            if ($notificationLevels) {
                $levels = explode(',', $notificationLevels);
                $levelNames = [];
                foreach ($levels as $levelNum) {
                    try {
                        $levelNames[] = LogLevel::from((int)$levelNum)->toString();
                    } catch (\Exception $e) {
                        $levelNames[] = "Unknown({$levelNum})";
                    }
                }
                $this->info("Notification Levels: " . implode(', ', $levelNames));
            }

            // 通知先メールアドレスを確認
            $notificationEmail = \DB::table('base_settings')
                ->where('name', 'notification_email')
                ->value('value');
            
            if ($notificationEmail) {
                $this->info("Notification Email: {$notificationEmail}");
            } else {
                $this->warn("Warning: notification_email is not set in base_settings");
            }

        } catch (\Exception $e) {
            $this->error("Error retrieving notification settings: " . $e->getMessage());
        }
    }
}
