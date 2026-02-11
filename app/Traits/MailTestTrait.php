<?php

namespace App\Traits;

use App\Models\BaseSetting;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

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

        // ソケット接続でSMTPサーバーに接続テスト
        $context = stream_context_create();

        $originalHost = $host;
        if ($encryption === 'ssl') {
            $host = 'ssl://'.$host;
        }

        $socket = @stream_socket_client(
            $host.':'.$port,
            $errno,
            $errstr,
            10, // 10秒タイムアウト
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (! $socket) {
            $errorMessage = __('mail-server/test.test_advanced.smtp_connection_error', [
                'error' => $errstr,
                'errno' => $errno,
            ]);
            throw new \Exception($errorMessage);
        }

        // SMTPレスポンスを読み取り
        $response = fgets($socket);

        if (! $response || ! str_starts_with($response, '220')) {
            fclose($socket);
            throw new \Exception(__('mail-server/test.test_advanced.smtp_response_invalid', ['response' => trim($response)]));
        }

        // STARTTLSが必要な場合
        if ($encryption === 'tls') {
            fwrite($socket, "EHLO localhost\r\n");
            $response = fgets($socket);

            fwrite($socket, "STARTTLS\r\n");
            $response = fgets($socket);

            if (! str_starts_with($response, '220')) {
                fclose($socket);
                throw new \Exception(__('mail-server/test.test_advanced.smtp_starttls_failed', ['response' => trim($response)]));
            }

            // TLS暗号化を有効にする
            if (! stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                fclose($socket);
                throw new \Exception(__('mail-server/test.test_advanced.smtp_tls_crypto_failed'));
            }
        }

        // 認証テスト（ユーザー名とパスワードが設定されている場合）
        if (! empty($username) && ! empty($password)) {
            fwrite($socket, "EHLO localhost\r\n");
            $response = fgets($socket);

            fwrite($socket, "AUTH LOGIN\r\n");
            $response = fgets($socket);

            if (! str_starts_with($response, '334')) {
                fclose($socket);
                throw new \Exception(__('mail-server/test.test_advanced.smtp_auth_login_failed', ['response' => trim($response)]));
            }

            // ユーザー名を送信
            fwrite($socket, base64_encode($username)."\r\n");
            $response = fgets($socket);

            if (! str_starts_with($response, '334')) {
                fclose($socket);
                throw new \Exception(__('mail-server/test.test_advanced.smtp_username_auth_failed', ['response' => trim($response)]));
            }

            // パスワードを送信
            fwrite($socket, base64_encode($password)."\r\n");
            $response = fgets($socket);

            if (! str_starts_with($response, '235')) {
                fclose($socket);
                throw new \Exception(__('mail-server/test.test_advanced.smtp_password_auth_failed', ['response' => trim($response)]));
            }
        }

        // 接続を閉じる
        fwrite($socket, "QUIT\r\n");
        fclose($socket);
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
        // 一時的にメール設定を変更
        Config::set('mail.default', $mailSettings['mail_mailer']);
        Config::set('mail.mailers.smtp.host', $mailSettings['mail_host']);
        Config::set('mail.mailers.smtp.port', $mailSettings['mail_port']);
        Config::set('mail.mailers.smtp.username', $mailSettings['mail_username'] ?? '');
        Config::set('mail.mailers.smtp.password', $mailSettings['mail_password'] ?? '');
        Config::set('mail.mailers.smtp.encryption', $mailSettings['mail_encryption'] ?? '');
        Config::set('mail.from.address', $mailSettings['mail_from_address']);
        Config::set('mail.from.name', $mailSettings['mail_from_name'] ?? env('APP_NAME', 'Dixlase'));

        // 検証トークンを生成
        $verificationToken = \Str::random(64);

        // コンテキストに応じて検証URLとセッションキーを設定
        if ($context === 'install') {
            $verificationUrl = route('install.mail.verify-mail', ['token' => $verificationToken]);
            session(['install_mail_verification_token' => $verificationToken]);
        } else {
            $verificationUrl = route('admin.settings.base.verify-mail', ['token' => $verificationToken]);
            session(['mail_verification_token' => $verificationToken]);
        }

        // MailMessageフォーマットでテストメールを作成（パスワードリセットメールと同じ形式）
        $mailMessage = (new \Illuminate\Notifications\Messages\MailMessage())
            ->subject(__('mail-server/test.test_mail.subject'))
            ->greeting(__('mail-server/test.test_mail.greeting'))
            ->line(__('mail-server/test.test_mail.test_details_title'))
            ->line(__('mail-server/test.test_mail.app_name').': '.config('app.name'))
            ->line(__('mail-server/test.test_mail.test_datetime').': '.now()->format('Y-m-d H:i:s'))
            ->line(__('mail-server/test.test_mail.verification_required'))
            ->action(__('mail-server/test.test_mail.verify_button'), $verificationUrl)
            ->line(__('mail-server/test.test_mail.manual_verification'))
            ->line($verificationUrl)
            ->salutation(__('mail-server/test.test_mail.regards').",\n\n".config('app.name'));

        // テストメールを送信
        try {
            $fromName = $mailSettings['mail_from_name'] ?? env('APP_NAME', 'Dixlase');

            Mail::send([], [], function ($message) use ($toEmail, $mailSettings, $fromName, $mailMessage) {
                $message->to($toEmail)
                    ->subject($mailMessage->subject)
                    ->html((string) $mailMessage->render())
                    ->from($mailSettings['mail_from_address'], $fromName);
            });
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * メール接続テスト（統合版）
     */
    public function performConnectionTest($request, $context = 'admin')
    {
        try {
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

            // SMTPのみサポート
            if ($mailSettings['mail_mailer'] !== 'smtp') {
                return response()->json([
                    'success' => false,
                    'message' => __('mail-server/test.test_functions.mailer_not_supported', ['mailer' => $mailSettings['mail_mailer']]),
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
            } else {
                // 管理画面時はmail_test_resultsに保存（フォーム保存時にDBに反映）
                session(['mail_test_results.mail_connection_tested' => 1]);
                session(['mail_test_results.mail_connection_test_date' => now()->toDateTimeString()]);
                session()->save(); // セッションを強制保存
            }

            return response()->json([
                'success' => true,
                'message' => __('mail-server/test.test_functions.connection_test_success'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('mail-server/test.test_functions.connection_test_failed', ['error' => $e->getMessage()]),
            ], 400);
        }
    }

    /**
     * メール送信テスト（統合版）
     */
    public function performMailTest($request, $context = 'admin')
    {
        try {
            // 接続テストが完了しているかチェック
            if ($context === 'install') {
                // インストール時はinstall_dataから確認
                $installData = session('install_data', []);
                $connectionTested = (bool) ($installData['mail_connection_tested'] ?? false);
            } else {
                // 管理画面時はmail_test_resultsまたはDBから確認
                $sessionTestResults = session('mail_test_results', []);
                $connectionTested = (bool) ($sessionTestResults['mail_connection_tested'] ?? BaseSetting::getValue('mail_connection_tested', false));
            }

            if (! $connectionTested) {
                return response()->json([
                    'success' => false,
                    'message' => __('mail-server/test.test_functions.connection_test_required'),
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

            // 一時的にメール設定を変更
            $this->applyMailSettings($mailSettings);

            // テストメール送信先を設定
            if ($context === 'install') {
                // インストール時はセッションから管理者メールアドレスを取得
                $testEmail = session('install_data.admin_email');
                if (! $testEmail) {
                    return response()->json([
                        'success' => false,
                        'message' => __('install/step4.admin_email_not_found'),
                    ], 400);
                }
            } else {
                // 管理画面では現在ログイン中のアカウントのメールアドレス
                $member = \App\Helpers\AdminHelper::getMember();
                if (! $member) {
                    return response()->json([
                        'success' => false,
                        'message' => __('mail-server/test.test_functions.member_not_found'),
                    ], 400);
                }
                $testEmail = $member->email;
            }

            // 認証トークンを生成
            $verificationToken = bin2hex(random_bytes(32));

            // コンテキストに応じてトークンを保存
            if ($context === 'install') {
                session(['install_mail_verification_token' => $verificationToken]);
            } else {
                BaseSetting::setValue('mail_verification_token', $verificationToken);
            }

            // 認証リンクを生成
            if ($context === 'install') {
                $verificationUrl = route('install.mail.verify-mail', ['token' => $verificationToken]);
            } else {
                $verificationUrl = route('admin.settings.base.mail.verify-mail', ['token' => $verificationToken]);
            }

            // アプリケーション名を取得
            $appName = env('APP_NAME', 'Dixlase');

            // メール内容を取得
            $subject = __('mail-server/test.test_mail.subject');

            // MailMessage形式でメールを作成（ログイン通知と同じ形式）
            $message = new MailMessage();
            $message->subject("[{$appName}] {$subject}");
            $message->greeting(__('mail-server/test.test_mail.greeting'));

            // テスト詳細を追加
            $message->line('**'.__('mail-server/test.test_mail.test_details_title').'**');
            $message->line('**'.__('mail-server/test.test_mail.app_name').'** '.$appName);
            $message->line('**'.__('mail-server/test.test_mail.test_datetime').'** '.now()->format('Y-m-d H:i:s'));

            // 受信確認の説明
            $message->line(__('mail-server/test.test_mail.verification_required'));

            // 確認ボタン
            $message->action(__('mail-server/test.test_mail.verify_button'), $verificationUrl);

            // 手動確認用URL
            $message->line(__('mail-server/test.test_mail.manual_verification'));
            $message->line($verificationUrl);

            $message->salutation(__('mail-server/test.test_mail.regards')."\n\n".$appName);

            // テストメールを送信
            Mail::send([], [], function ($mail) use ($testEmail, $message) {
                $mail->to($testEmail)
                    ->subject($message->subject)
                    ->html((string) $message->render());
            });

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
                session()->save(); // セッションを強制保存
            }

            return response()->json([
                'success' => true,
                'message' => __('mail-server/test.test_functions.test_mail_success'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('mail-server/test.test_functions.test_mail_failed', ['error' => $e->getMessage()]),
            ], 400);
        }
    }

    /**
     * メール受信確認テスト（統合版）
     */
    public function performMailVerification($token, $context = 'admin')
    {
        try {
            // コンテキストに応じてトークンを検証
            if ($context === 'install') {
                $storedToken = session('install_mail_verification_token');
                $installData = session('install_data', []);
                $alreadyVerified = isset($installData['mail_receive_tested']) && $installData['mail_receive_tested'] == 1;
            } else {
                $storedToken = BaseSetting::getValue('mail_verification_token');
                $testResults = session('mail_test_results', []);
                $dbMailReceiveTested = BaseSetting::getValue('mail_receive_tested');

                // セッションまたはDBで認証済みかチェック
                $alreadyVerified = (isset($testResults['mail_receive_tested']) && $testResults['mail_receive_tested'] == 1)
                                || ($dbMailReceiveTested == 1);
            }

            // 既に認証済みの場合
            if ($alreadyVerified && ! $storedToken) {
                return view('components.mail-server.verification-success', [
                    'isInstall' => $context === 'install',
                    'alreadyVerified' => true,
                ]);
            }

            if (! $storedToken || $storedToken !== $token) {
                // 既に認証済みかつトークンがnullの場合のみ（正常に完了済み）
                if ($alreadyVerified && $storedToken === null) {
                    return view('components.mail-server.verification-success', [
                        'isInstall' => $context === 'install',
                        'alreadyVerified' => true,
                    ]);
                }

                // それ以外は無効なトークンエラー
                return view('components.mail-server.verification-error', [
                    'errorType' => 'invalid_token',
                    'errorMessage' => __('mail-server/config.controller_messages.verification_token_invalid'),
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
                session()->save(); // セッションを強制保存
            }

            // トークンをクリア
            if ($context === 'install') {
                session()->forget('install_mail_verification_token');
            } else {
                BaseSetting::setValue('mail_verification_token', null);
            }

            // 共有コンポーネントを使用
            return view('components.mail-server.verification-success', [
                'isInstall' => $context === 'install',
            ]);
        } catch (\Exception $e) {
            return view('components.mail-server.verification-error', [
                'errorType' => 'verification_error',
                'errorMessage' => __('mail-server/config.controller_messages.verification_error', ['error' => $e->getMessage()]),
            ]);
        }
    }
}
