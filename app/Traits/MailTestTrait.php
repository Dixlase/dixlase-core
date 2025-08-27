<?php

namespace App\Traits;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\BaseSetting;
use DateTime;
use DateTimeZone;

trait MailTestTrait
{
    /**
     * SMTP接続テスト
     */
    protected function testSmtpConnection(array $mailSettings)
    {
        $host = $mailSettings['mail_host'];
        $port = (int) $mailSettings['mail_port'];
        $username = $mailSettings['mail_username'] ?? '';
        $password = $mailSettings['mail_password'] ?? '';
        $encryption = $mailSettings['mail_encryption'] ?? '';

        // デバッグ情報をログに記録
        \Log::info('SMTP接続テスト開始', [
            'host' => $host,
            'port' => $port,
            'encryption' => $encryption,
            'username' => $username ? '***設定済み***' : '未設定',
            'password' => $password ? '***設定済み***' : '未設定'
        ]);

        // ソケット接続でSMTPサーバーに接続テスト
        $context = stream_context_create();
        
        $originalHost = $host;
        if ($encryption === 'ssl') {
            $host = 'ssl://' . $host;
        }

        \Log::info('SMTP接続試行', ['connection_string' => $host . ':' . $port]);

        $socket = @stream_socket_client(
            $host . ':' . $port,
            $errno,
            $errstr,
            10, // 10秒タイムアウト
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$socket) {
            $errorMessage = "接続エラー: {$errstr} (エラーコード: {$errno})";
            \Log::error('SMTP接続失敗', [
                'host' => $originalHost,
                'port' => $port,
                'encryption' => $encryption,
                'errno' => $errno,
                'errstr' => $errstr,
                'connection_string' => $host . ':' . $port
            ]);
            throw new \Exception($errorMessage);
        }

        // SMTPレスポンスを読み取り
        $response = fgets($socket);
        \Log::info('SMTP初期応答', ['response' => trim($response)]);
        
        if (!$response || !str_starts_with($response, '220')) {
            fclose($socket);
            \Log::error('SMTP初期応答エラー', ['response' => trim($response)]);
            throw new \Exception('SMTPサーバーからの応答が不正です: ' . trim($response));
        }

        // STARTTLSが必要な場合
        if ($encryption === 'tls') {
            fwrite($socket, "EHLO localhost\r\n");
            $response = fgets($socket);
            
            fwrite($socket, "STARTTLS\r\n");
            $response = fgets($socket);
            
            if (!str_starts_with($response, '220')) {
                fclose($socket);
                throw new \Exception('STARTTLS の開始に失敗しました: ' . trim($response));
            }

            // TLS暗号化を有効にする
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                fclose($socket);
                throw new \Exception('TLS暗号化の有効化に失敗しました');
            }
        }

        // 認証テスト（ユーザー名とパスワードが設定されている場合）
        if (!empty($username) && !empty($password)) {
            fwrite($socket, "EHLO localhost\r\n");
            $response = fgets($socket);

            fwrite($socket, "AUTH LOGIN\r\n");
            $response = fgets($socket);
            
            if (!str_starts_with($response, '334')) {
                fclose($socket);
                throw new \Exception('AUTH LOGIN コマンドが失敗しました: ' . trim($response));
            }

            // ユーザー名を送信
            fwrite($socket, base64_encode($username) . "\r\n");
            $response = fgets($socket);
            
            if (!str_starts_with($response, '334')) {
                fclose($socket);
                throw new \Exception('ユーザー名認証が失敗しました: ' . trim($response));
            }

            // パスワードを送信
            fwrite($socket, base64_encode($password) . "\r\n");
            $response = fgets($socket);
            
            if (!str_starts_with($response, '235')) {
                fclose($socket);
                throw new \Exception('パスワード認証が失敗しました: ' . trim($response));
            }
        }

        // 接続を閉じる
        fwrite($socket, "QUIT\r\n");
        fclose($socket);
        
        \Log::info('SMTP接続テスト完了', ['status' => 'success']);
    }

    /**
     * メール設定を一時的に適用
     */
    protected function applyMailSettings(array $mailSettings)
    {
        Config::set('mail.default', $mailSettings['mail_mailer']);
        Config::set('mail.mailers.smtp.host', $mailSettings['mail_host']);
        Config::set('mail.mailers.smtp.port', $mailSettings['mail_port']);
        Config::set('mail.mailers.smtp.username', $mailSettings['mail_username'] ?? '');
        Config::set('mail.mailers.smtp.password', $mailSettings['mail_password'] ?? '');
        Config::set('mail.mailers.smtp.encryption', $mailSettings['mail_encryption'] ?? '');
        Config::set('mail.from.address', $mailSettings['mail_from_address'] ?? '');
        Config::set('mail.from.name', env('APP_NAME', 'MySoftware'));
    }

    /**
     * テストメールを送信
     */
    protected function sendTestMail($toEmail, $mailSettings, $context = 'admin')
    {
        \Log::info('=== MailTestTrait sendTestMail 開始 ===', [
            'to_email' => $toEmail,
            'context' => $context,
            'mail_settings' => [
                'mail_mailer' => $mailSettings['mail_mailer'] ?? 'null',
                'mail_host' => $mailSettings['mail_host'] ?? 'null',
                'mail_port' => $mailSettings['mail_port'] ?? 'null',
                'mail_username' => $mailSettings['mail_username'] ?? 'null',
                'mail_encryption' => $mailSettings['mail_encryption'] ?? 'null',
                'mail_from_address' => $mailSettings['mail_from_address'] ?? 'null',
                'mail_from_name' => $mailSettings['mail_from_name'] ?? 'null',
            ]
        ]);

        // 一時的にメール設定を変更
        Config::set('mail.default', $mailSettings['mail_mailer']);
        Config::set('mail.mailers.smtp.host', $mailSettings['mail_host']);
        Config::set('mail.mailers.smtp.port', $mailSettings['mail_port']);
        Config::set('mail.mailers.smtp.username', $mailSettings['mail_username'] ?? '');
        Config::set('mail.mailers.smtp.password', $mailSettings['mail_password'] ?? '');
        Config::set('mail.mailers.smtp.encryption', $mailSettings['mail_encryption'] ?? '');
        Config::set('mail.from.address', $mailSettings['mail_from_address']);
        Config::set('mail.from.name', $mailSettings['mail_from_name'] ?? env('APP_NAME', 'Dixlase'));
        
        \Log::info('メール設定を一時的に変更完了');

        // 検証トークンを生成
        $verificationToken = \Str::random(64);
        \Log::info('検証トークン生成完了', ['token_length' => strlen($verificationToken)]);
        
        // コンテキストに応じて検証URLとセッションキーを設定
        if ($context === 'install') {
            $verificationUrl = route('install.mail.verify-mail', ['token' => $verificationToken]);
            session(['install_mail_verification_token' => $verificationToken]);
            \Log::info('インストール用検証URL生成', ['url' => $verificationUrl]);
        } else {
            $verificationUrl = route('admin.settings.base.verify-mail', ['token' => $verificationToken]);
            session(['mail_verification_token' => $verificationToken]);
            \Log::info('管理画面用検証URL生成', ['url' => $verificationUrl]);
        }

        // MailMessageフォーマットでテストメールを作成（パスワードリセットメールと同じ形式）
        \Log::info('MailMessageフォーマットでメール作成開始...');
        $mailMessage = (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject(__('mail.test_mail.subject'))
            ->greeting(__('mail.test_mail.greeting'))
            ->line(__('mail.test_mail.test_details_title'))
            ->line(__('mail.test_mail.app_name') . ': ' . config('app.name'))
            ->line(__('mail.test_mail.test_datetime') . ': ' . now()->format('Y-m-d H:i:s'))
            ->line(__('mail.test_mail.verification_required'))
            ->action(__('mail.test_mail.verify_button'), $verificationUrl)
            ->line(__('mail.test_mail.manual_verification'))
            ->line($verificationUrl)
            ->salutation(__('mail.test_mail.regards') . ",\n\n" . config('app.name'));

        \Log::info('MailMessage作成完了');

        // テストメールを送信
        \Log::info('メール送信開始...');
        try {
            $fromName = $mailSettings['mail_from_name'] ?? env('APP_NAME', 'Dixlase');
            \Log::info('メール送信パラメータ', [
                'to' => $toEmail,
                'from_address' => $mailSettings['mail_from_address'],
                'from_name' => $fromName,
                'subject' => __('mail.test_mail.subject')
            ]);
            
            Mail::send([], [], function ($message) use ($toEmail, $mailSettings, $fromName, $mailMessage) {
                $message->to($toEmail)
                        ->subject($mailMessage->subject)
                        ->html((string) $mailMessage->render())
                        ->from($mailSettings['mail_from_address'], $fromName);
            });
            \Log::info('メール送信完了');
        } catch (\Exception $e) {
            \Log::error('メール送信エラー', [
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * メール接続テスト（統合版）
     */
    public function performConnectionTest($request, $context = 'admin')
    {
        try {
            \Log::info('=== メール接続テスト開始 ===');
            
            // リクエストからメール設定を取得
            $mailSettings = $request->only([
                'mail_mailer',
                'mail_host',
                'mail_port',
                'mail_username',
                'mail_password',
                'mail_encryption',
                'mail_from_address',
            ]);

            \Log::info('メール設定:', [
                'mail_mailer' => $mailSettings['mail_mailer'],
                'mail_host' => $mailSettings['mail_host'],
                'mail_port' => $mailSettings['mail_port'],
                'mail_username' => $mailSettings['mail_username'],
                'mail_encryption' => $mailSettings['mail_encryption'],
                'mail_from_address' => $mailSettings['mail_from_address'],
            ]);

            // SMTPのみサポート
            if ($mailSettings['mail_mailer'] !== 'smtp') {
                \Log::warning('サポートされていないメーラー', ['mailer' => $mailSettings['mail_mailer']]);
                return response()->json([
                    'success' => false,
                    'message' => __('admin.settings.base.controller_messages.mailer_not_supported', ['mailer' => $mailSettings['mail_mailer']])
                ], 400);
            }

            // SMTP接続テストを実行
            $this->testSmtpConnection($mailSettings);

            // 接続テスト成功時にセッションに保存
            if ($context === 'install') {
                // インストール時はinstall_dataに保存
                $installData = session('install_data', []);
                $installData['mail_connection_tested'] = 1;
                $installData['mail_connection_test_date'] = now()->toDateTimeString();
                session(['install_data' => $installData]);
                session()->save(); // セッションを強制保存
                
                \Log::info('接続テスト成功 - セッションに保存', [
                    'mail_connection_tested' => $installData['mail_connection_tested'],
                    'session_id' => session()->getId()
                ]);
            } else {
                // 管理画面時はmail_test_resultsに保存（フォーム保存時にDBに反映）
                session(['mail_test_results.mail_connection_tested' => 1]);
                session(['mail_test_results.mail_connection_test_date' => now()->toDateTimeString()]);
            }
            
            return response()->json([
                'success' => true,
                'message' => __('admin.settings.base.controller_messages.connection_success')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('admin.settings.base.controller_messages.connection_failed', ['error' => $e->getMessage()])
            ], 400);
        }
    }

    /**
     * メール送信テスト（統合版）
     */
    public function performMailTest($request, $context = 'admin')
    {
        try {
            \Log::info('=== メール送信テスト開始 ===');
            
            // 接続テストが完了しているかチェック
            if ($context === 'install') {
                // インストール時はinstall_dataから確認
                $installData = session('install_data', []);
                $connectionTested = (bool) ($installData['mail_connection_tested'] ?? false);
                
                \Log::info('インストール時の接続テスト確認', [
                    'install_data_keys' => array_keys($installData),
                    'mail_connection_tested' => $installData['mail_connection_tested'] ?? 'not_set',
                    'connection_tested_bool' => $connectionTested
                ]);
            } else {
                // 管理画面時はmail_test_resultsまたはDBから確認
                $sessionTestResults = session('mail_test_results', []);
                $connectionTested = (bool) ($sessionTestResults['mail_connection_tested'] ?? BaseSetting::getValue('mail_connection_tested', false));
                
                \Log::info('管理画面時の接続テスト確認', [
                    'session_test_results' => $sessionTestResults,
                    'connection_tested_bool' => $connectionTested
                ]);
            }
            
            if (!$connectionTested) {
                \Log::warning('接続テストが未完了のためメール送信テストを拒否', [
                    'context' => $context,
                    'connection_tested' => $connectionTested
                ]);
                return response()->json([
                    'success' => false,
                    'message' => __('admin.settings.base.connection_test_required')
                ], 400);
            }
            
            // リクエストからメール設定を取得
            $mailSettings = $request->only([
                'mail_mailer',
                'mail_host',
                'mail_port',
                'mail_username',
                'mail_password',
                'mail_encryption',
                'mail_from_address',
            ]);
            
            \Log::info('メール設定:', [
                'mail_mailer' => $mailSettings['mail_mailer'],
                'mail_host' => $mailSettings['mail_host'],
                'mail_port' => $mailSettings['mail_port'],
                'mail_username' => $mailSettings['mail_username'],
                'mail_encryption' => $mailSettings['mail_encryption'],
                'mail_from_address' => $mailSettings['mail_from_address'],
            ]);

            // 一時的にメール設定を変更
            $this->applyMailSettings($mailSettings);
            \Log::info('メール設定を一時的に変更完了');

            // テストメール送信先を設定
            if ($context === 'install') {
                // インストール時はセッションから管理者メールアドレスを取得
                $testEmail = session('install_data.admin_email');
                if (!$testEmail) {
                    \Log::error('インストール時の管理者メールアドレスが見つかりません');
                    return response()->json([
                        'success' => false,
                        'message' => __('install.admin_email_not_found')
                    ], 400);
                }
            } else {
                // 管理画面では現在ログイン中のアカウントのメールアドレス
                $testEmail = auth()->user()->email;
            }
            \Log::info('テストメール送信先:', ['test_email' => $testEmail, 'context' => $context]);

            // 認証トークンを生成
            $verificationToken = bin2hex(random_bytes(32));
            
            // コンテキストに応じてトークンを保存
            if ($context === 'install') {
                session(['install_mail_verification_token' => $verificationToken]);
            } else {
                BaseSetting::setValue('mail_verification_token', $verificationToken);
            }
            \Log::info('認証トークン生成完了');
            
            // 認証リンクを生成
            if ($context === 'install') {
                $verificationUrl = route('install.mail.verify-mail', ['token' => $verificationToken]);
            } else {
                $verificationUrl = route('admin.settings.base.verify-mail', ['token' => $verificationToken]);
            }
            \Log::info('認証URL生成:', ['verification_url' => $verificationUrl]);
            
            // アプリケーション名を取得
            $appName = env('APP_NAME', 'Dixlase');
            \Log::info('アプリケーション名:', ['app_name' => $appName]);

            // 多言語対応のメール内容を取得
            $subject = __('mail.test_mail.subject');
            \Log::info('メール件名取得:', ['subject' => $subject]);
            
            // MailMessage形式でメールを作成（ログイン通知と同じ形式）
            $message = new MailMessage;
            $message->subject("[{$appName}] {$subject}");
            $message->greeting(__('mail.test_mail.greeting'));
            
            // テスト詳細を追加
            $message->line('**' . __('mail.test_mail.test_details_title') . '**');
            $message->line('**' . __('mail.test_mail.app_name') . '** ' . $appName);
            $message->line('**' . __('mail.test_mail.test_datetime') . '** ' . now()->format('Y-m-d H:i:s'));
            
            // 受信確認の説明
            $message->line(__('mail.test_mail.verification_required'));
            
            // 確認ボタン
            $message->action(__('mail.test_mail.verify_button'), $verificationUrl);
            
            // 手動確認用URL
            $message->line(__('mail.test_mail.manual_verification'));
            $message->line($verificationUrl);
            
            $message->salutation(__('mail.test_mail.regards') . "\n\n" . $appName);
            
            \Log::info('MailMessage作成完了');

            // テストメールを送信
            \Log::info('メール送信開始...');
            Mail::send([], [], function ($mail) use ($testEmail, $message) {
                $mail->to($testEmail)
                     ->subject($message->subject)
                     ->html((string) $message->render());
            });
            
            \Log::info('メール送信完了');

            // 送信テスト成功時にセッションに保存
            if ($context === 'install') {
                // インストール時はinstall_dataに保存
                $installData = session('install_data', []);
                $installData['mail_send_tested'] = 1;
                $installData['mail_send_test_date'] = now()->toDateTimeString();
                session(['install_data' => $installData]);
            } else {
                // 管理画面時はmail_test_resultsに保存（フォーム保存時にDBに反映）
                session(['mail_test_results.mail_send_tested' => 1]);
                session(['mail_test_results.mail_send_test_date' => now()->toDateTimeString()]);
            }

            return response()->json([
                'success' => true,
                'message' => __('admin.settings.base.test_mail_success')
            ]);

        } catch (\Exception $e) {
            \Log::error('メール送信テストエラー', [
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => __('admin.settings.base.test_mail_failed', ['error' => $e->getMessage()])
            ], 400);
        }
    }

    /**
     * メール受信確認テスト（統合版）
     */
    public function performMailVerification($token, $context = 'admin')
    {
        try {
            \Log::info('=== メール受信確認テスト開始 ===', ['token' => $token, 'context' => $context]);
            
            // コンテキストに応じてトークンを検証
            if ($context === 'install') {
                $storedToken = session('install_mail_verification_token');
                $installData = session('install_data', []);
                $alreadyVerified = isset($installData['mail_receive_tested']) && $installData['mail_receive_tested'] == 1;
                \Log::info('インストール時の確認', [
                    'stored_token' => $storedToken,
                    'install_data' => $installData,
                    'already_verified' => $alreadyVerified
                ]);
            } else {
                $storedToken = BaseSetting::getValue('mail_verification_token');
                $testResults = session('mail_test_results', []);
                $dbMailReceiveTested = BaseSetting::getValue('mail_receive_tested');
                
                // セッションまたはDBで認証済みかチェック
                $alreadyVerified = (isset($testResults['mail_receive_tested']) && $testResults['mail_receive_tested'] == 1) 
                                || ($dbMailReceiveTested == 1);
                
                \Log::info('管理画面時の確認', [
                    'stored_token' => $storedToken,
                    'test_results' => $testResults,
                    'db_mail_receive_tested' => $dbMailReceiveTested,
                    'already_verified' => $alreadyVerified
                ]);
            }
            
            // 既に認証済みの場合
            if ($alreadyVerified && !$storedToken) {
                \Log::info('既に認証済み - 専用画面を表示', ['context' => $context]);
                return view('components.mail-verification-success', [
                    'isInstall' => $context === 'install',
                    'alreadyVerified' => true
                ]);
            }
            
            if (!$storedToken || $storedToken !== $token) {
                \Log::warning('無効な認証トークン', [
                    'provided_token' => $token, 
                    'stored_token' => $storedToken,
                    'already_verified' => $alreadyVerified,
                    'context' => $context
                ]);
                
                // 既に認証済みかつトークンがnullの場合のみ（正常に完了済み）
                if ($alreadyVerified && $storedToken === null) {
                    \Log::info('認証済みでトークンもクリア済み - 専用画面を表示');
                    return view('components.mail-verification-success', [
                        'isInstall' => $context === 'install',
                        'alreadyVerified' => true
                    ]);
                }
                
                // それ以外は無効なトークンエラー
                return view('components.mail-verification-error', [
                    'errorType' => 'invalid_token',
                    'errorMessage' => __('mail.controller_messages.verification_token_invalid')
                ]);
            }

            // 受信確認成功時にセッションに保存
            if ($context === 'install') {
                // インストール時はinstall_dataに保存
                $installData = session('install_data', []);
                $installData['mail_receive_tested'] = 1;
                $installData['mail_receive_test_date'] = now()->toDateTimeString();
                session(['install_data' => $installData]);
            } else {
                // 管理画面時はmail_test_resultsに保存（フォーム保存時にDBに反映）
                session(['mail_test_results.mail_receive_tested' => 1]);
                session(['mail_test_results.mail_receive_test_date' => now()->toDateTimeString()]);
            }
            
            // トークンをクリア
            if ($context === 'install') {
                session()->forget('install_mail_verification_token');
            } else {
                BaseSetting::setValue('mail_verification_token', null);
            }
            
            \Log::info('メール受信確認テスト完了');

            // 共有コンポーネントを使用
            return view('components.mail-verification-success', [
                'isInstall' => $context === 'install'
            ]);

        } catch (\Exception $e) {
            \Log::error('メール受信確認テストエラー', [
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
            ]);
            
            return view('components.mail-verification-error', [
                'errorType' => 'verification_error',
                'errorMessage' => __('mail.controller_messages.verification_error', ['error' => $e->getMessage()])
            ]);
        }
    }
}
