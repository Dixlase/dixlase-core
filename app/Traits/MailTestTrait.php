<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Traits;

use App\Models\SiteSetting;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

trait MailTestTrait
{
    /**
     * Read a complete SMTP reply from a stream socket, draining all
     * continuation lines before returning.
     *
     * Per RFC 5321 §4.2, a multi-line SMTP reply has the format
     * `XYZ-...` (digit-digit-digit + HYPHEN) on every continuation line
     * and `XYZ ...` (digit-digit-digit + SPACE) on the final line. The
     * earlier implementation used a bare `fgets($socket)` which only
     * read the first line — subsequent reads then picked up the
     * leftover continuation lines as if they were responses to later
     * commands, which is what caused the SMTP connection test to throw
     * a spurious `smtp_auth_login_failed: 250-ENHANCEDSTATUSCODES`
     * against any server (Sakura, Postfix, Gmail, …) that returns a
     * multi-line EHLO advertising extensions.
     *
     * Single-line replies still terminate on the first iteration
     * because their only line already matches the `^\d{3} ` pattern,
     * so callers that previously worked against simpler servers keep
     * working unchanged.
     *
     * @param  resource  $socket
     * @return string The full reply with continuation lines joined.
     *                Callers that test only the leading 3-byte status
     *                code keep working because the first three bytes
     *                are still the SMTP reply code.
     */
    private function readSmtpReply($socket): string
    {
        $full = '';
        while (($line = fgets($socket, 4096)) !== false) {
            $full .= $line;
            if (preg_match('/^\d{3} /', $line)) {
                break;
            }
        }

        return $full;
    }

    /**
     * SMTP connection test
     */
    protected function testSmtpConnection(array $mailSettings)
    {
        $host = $mailSettings['mail_host'];
        $port = (int) $mailSettings['mail_port'];
        $username = $mailSettings['mail_username'] ?? '';
        $password = $mailSettings['mail_password'] ?? '';
        $encryption = $mailSettings['mail_encryption'] ?? '';

        // Test connection to SMTP server via socket
        $context = stream_context_create();

        $originalHost = $host;
        if ($encryption === 'ssl') {
            $host = 'ssl://'.$host;
        }

        $socket = @stream_socket_client(
            $host.':'.$port,
            $errno,
            $errstr,
            10, // 10 second timeout
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

        // Read SMTP banner. The 220 greeting is typically a single
        // line but readSmtpReply() handles both single and multi-line
        // forms safely.
        $response = $this->readSmtpReply($socket);

        if (! $response || ! str_starts_with($response, '220')) {
            fclose($socket);
            throw new \Exception(__('mail-server/test.test_advanced.smtp_response_invalid', ['response' => trim($response)]));
        }

        // If STARTTLS is required
        if ($encryption === 'tls') {
            fwrite($socket, "EHLO localhost\r\n");
            // EHLO returns a multi-line reply listing extensions; the
            // helper drains all of them so the next command's response
            // is read cleanly.
            $this->readSmtpReply($socket);

            fwrite($socket, "STARTTLS\r\n");
            $response = $this->readSmtpReply($socket);

            if (! str_starts_with($response, '220')) {
                fclose($socket);
                throw new \Exception(__('mail-server/test.test_advanced.smtp_starttls_failed', ['response' => trim($response)]));
            }

            // Enable TLS encryption
            if (! stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                fclose($socket);
                throw new \Exception(__('mail-server/test.test_advanced.smtp_tls_crypto_failed'));
            }
        }

        // Authentication test (if username and password are set)
        if (! empty($username) && ! empty($password)) {
            fwrite($socket, "EHLO localhost\r\n");
            // Drain the multi-line EHLO reply before the next command.
            // Skipping this is what caused the historic
            // `smtp_auth_login_failed: 250-...` bug.
            $this->readSmtpReply($socket);

            fwrite($socket, "AUTH LOGIN\r\n");
            $response = $this->readSmtpReply($socket);

            if (! str_starts_with($response, '334')) {
                fclose($socket);
                throw new \Exception(__('mail-server/test.test_advanced.smtp_auth_login_failed', ['response' => trim($response)]));
            }

            // Send username
            fwrite($socket, base64_encode($username)."\r\n");
            $response = $this->readSmtpReply($socket);

            if (! str_starts_with($response, '334')) {
                fclose($socket);
                throw new \Exception(__('mail-server/test.test_advanced.smtp_username_auth_failed', ['response' => trim($response)]));
            }

            // Send password
            fwrite($socket, base64_encode($password)."\r\n");
            $response = $this->readSmtpReply($socket);

            if (! str_starts_with($response, '235')) {
                fclose($socket);
                throw new \Exception(__('mail-server/test.test_advanced.smtp_password_auth_failed', ['response' => trim($response)]));
            }
        }

        // Close connection
        fwrite($socket, "QUIT\r\n");
        fclose($socket);
    }

    /**
     * Apply mail settings temporarily
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
     * Send test email
     */
    protected function sendTestMail($toEmail, $mailSettings, $context = 'admin')
    {
        // Temporarily change mail settings
        Config::set('mail.default', $mailSettings['mail_mailer']);
        Config::set('mail.mailers.smtp.host', $mailSettings['mail_host']);
        Config::set('mail.mailers.smtp.port', $mailSettings['mail_port']);
        Config::set('mail.mailers.smtp.username', $mailSettings['mail_username'] ?? '');
        Config::set('mail.mailers.smtp.password', $mailSettings['mail_password'] ?? '');
        Config::set('mail.mailers.smtp.encryption', $mailSettings['mail_encryption'] ?? '');
        Config::set('mail.from.address', $mailSettings['mail_from_address']);
        Config::set('mail.from.name', $mailSettings['mail_from_name'] ?? env('APP_NAME', 'Dixlase'));

        // Generate verification token
        $verificationToken = \Str::random(64);

        // Set verification URL and session key based on context
        if ($context === 'install') {
            $verificationUrl = route('install.mail.verify-mail', ['token' => $verificationToken]);
            session(['install_mail_verification_token' => $verificationToken]);
        } else {
            $verificationUrl = route('admin.settings.base.verify-mail', ['token' => $verificationToken]);
            session(['mail_verification_token' => $verificationToken]);
        }

        // Create test email in MailMessage format (same format as password reset email)
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

        // Send test email
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
     * Mail connection test (integrated version)
     */
    public function performConnectionTest($request, $context = 'admin')
    {
        try {
            // Get mail settings from request
            $mailSettings = $request->only([
                'mail_mailer',
                'mail_host',
                'mail_port',
                'mail_username',
                'mail_password',
                'mail_encryption',
                'mail_from_address',
            ]);

            // Only SMTP is supported
            if ($mailSettings['mail_mailer'] !== 'smtp') {
                return response()->json([
                    'success' => false,
                    'message' => __('mail-server/test.test_functions.mailer_not_supported', ['mailer' => $mailSettings['mail_mailer']]),
                ], 400);
            }

            // Execute SMTP connection test
            $this->testSmtpConnection($mailSettings);

            // Save to session when connection test succeeds
            if ($context === 'install') {
                // Save to install_data during installation
                $installData = session('install_data', []);
                $installData['mail_connection_tested'] = 1;
                $installData['mail_connection_test_date'] = now()->toDateTimeString();
                session(['install_data' => $installData]);
                session()->save(); // Force save session
            } else {
                // Save to mail_test_results in admin panel (reflected to DB when form is saved)
                session(['mail_test_results.mail_connection_tested' => 1]);
                session(['mail_test_results.mail_connection_test_date' => now()->toDateTimeString()]);
                session()->save(); // Force save session
            }

            return response()->json([
                'success' => true,
                'message' => __('mail-server/test.test_functions.connection_test_success'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                // `connection_test_failed` is a bare message with no
                // `:error` placeholder; `mail_connection_test_failed`
                // is the variant that actually surfaces the underlying
                // exception. Without this swap the operator only sees
                // "メールサーバーへの接続に失敗しました" and never the
                // protocol-level reason (auth failure, TLS failure,
                // etc.) — both lang keys already exist in
                // lang/{ja,en}/mail-server/test.php.
                'message' => __('mail-server/test.test_functions.mail_connection_test_failed', ['error' => $e->getMessage()]),
            ], 400);
        }
    }

    /**
     * Mail send test (integrated version)
     */
    public function performMailTest($request, $context = 'admin')
    {
        try {
            // Check if connection test is completed
            if ($context === 'install') {
                // Check from install_data during installation
                $installData = session('install_data', []);
                $connectionTested = (bool) ($installData['mail_connection_tested'] ?? false);
            } else {
                // Check from mail_test_results or DB in admin panel
                $sessionTestResults = session('mail_test_results', []);
                $connectionTested = (bool) ($sessionTestResults['mail_connection_tested'] ?? SiteSetting::getValue('mail_connection_tested', false));
            }

            if (! $connectionTested) {
                return response()->json([
                    'success' => false,
                    'message' => __('mail-server/test.test_functions.connection_test_required'),
                ], 400);
            }

            // Get mail settings from request
            $mailSettings = $request->only([
                'mail_mailer',
                'mail_host',
                'mail_port',
                'mail_username',
                'mail_password',
                'mail_encryption',
                'mail_from_address',
            ]);

            // Temporarily change mail settings
            $this->applyMailSettings($mailSettings);

            // Set test mail recipient
            if ($context === 'install') {
                // Get administrator email address from session during installation
                $testEmail = session('install_data.admin_email');
                if (! $testEmail) {
                    return response()->json([
                        'success' => false,
                        'message' => __('install/step4.admin_email_not_found'),
                    ], 400);
                }
            } else {
                // Send the test mail to the logged-in admin's own email address.
                // The operator running the 3-stage test must open the mail and
                // click the verification link, and they can always access their
                // own account's mailbox (unlike the system admin address, which
                // a given operator may not be able to read).
                $member = \App\Helpers\AdminHelper::getMember();
                if (! $member) {
                    return response()->json([
                        'success' => false,
                        'message' => __('mail-server/test.test_functions.member_not_found'),
                    ], 400);
                }
                $testEmail = $member->email;
            }

            // Generate authentication token
            $verificationToken = bin2hex(random_bytes(32));

            // Save token according to context
            if ($context === 'install') {
                session(['install_mail_verification_token' => $verificationToken]);
            } else {
                SiteSetting::setValue('mail_verification_token', $verificationToken);
            }

            // Generate authentication link
            if ($context === 'install') {
                $verificationUrl = route('install.mail.verify-mail', ['token' => $verificationToken]);
            } else {
                $verificationUrl = route('admin.settings.base.mail.verify-mail', ['token' => $verificationToken]);
            }

            // Get application name
            $appName = env('APP_NAME', 'Dixlase');

            // Get mail content
            $subject = __('mail-server/test.test_mail.subject');

            // Create mail in MailMessage format (same format as login notification)
            $message = new MailMessage();
            $message->subject("[{$appName}] {$subject}");
            $message->greeting(__('mail-server/test.test_mail.greeting'));

            // Add test details
            $message->line('**'.__('mail-server/test.test_mail.test_details_title').'**');
            $message->line('**'.__('mail-server/test.test_mail.app_name').'** '.$appName);
            $message->line('**'.__('mail-server/test.test_mail.test_datetime').'** '.now()->format('Y-m-d H:i:s'));

            // Explanation for receipt confirmation
            $message->line(__('mail-server/test.test_mail.verification_required'));

            // Confirmation button
            $message->action(__('mail-server/test.test_mail.verify_button'), $verificationUrl);

            // URL for manual confirmation
            $message->line(__('mail-server/test.test_mail.manual_verification'));
            $message->line($verificationUrl);

            $message->salutation(__('mail-server/test.test_mail.regards')."\n\n".$appName);

            // Send test email
            Mail::send([], [], function ($mail) use ($testEmail, $message) {
                $mail->to($testEmail)
                    ->subject($message->subject)
                    ->html((string) $message->render());
            });

            // Save to session on successful send test
            if ($context === 'install') {
                // Save to install_data during installation
                $installData = session('install_data', []);
                $installData['mail_send_tested'] = 1;
                $installData['mail_send_test_date'] = now()->toDateTimeString();
                session(['install_data' => $installData]);
            } else {
                // Save to mail_test_results in admin panel (reflected to DB when form is saved)
                session(['mail_test_results.mail_send_tested' => 1]);
                session(['mail_test_results.mail_send_test_date' => now()->toDateTimeString()]);
                session()->save(); // Force save session
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
     * Mail receipt confirmation test (integrated version)
     */
    public function performMailVerification($token, $context = 'admin')
    {
        try {
            // Validate token based on context
            if ($context === 'install') {
                $storedToken = session('install_mail_verification_token');
                $installData = session('install_data', []);
                $alreadyVerified = isset($installData['mail_receive_tested']) && $installData['mail_receive_tested'] == 1;
            } else {
                $storedToken = SiteSetting::getValue('mail_verification_token');
                $testResults = session('mail_test_results', []);
                $dbMailReceiveTested = SiteSetting::getValue('mail_receive_tested');

                // Check if already authenticated in session or DB
                $alreadyVerified = (isset($testResults['mail_receive_tested']) && $testResults['mail_receive_tested'] == 1)
                                || ($dbMailReceiveTested == 1);
            }

            // If already authenticated
            if ($alreadyVerified && ! $storedToken) {
                return view('components.mail-server.verification-success', [
                    'isInstall' => $context === 'install',
                    'alreadyVerified' => true,
                ]);
            }

            if (! $storedToken || $storedToken !== $token) {
                // Only when already authenticated and token is null (successfully completed)
                if ($alreadyVerified && $storedToken === null) {
                    return view('components.mail-server.verification-success', [
                        'isInstall' => $context === 'install',
                        'alreadyVerified' => true,
                    ]);
                }

                // Otherwise invalid token error
                return view('components.mail-server.verification-error', [
                    'errorType' => 'invalid_token',
                    'errorMessage' => __('mail-server/config.controller_messages.verification_token_invalid'),
                ]);
            }

            // Save to session on successful receipt confirmation
            if ($context === 'install') {
                // Save to install_data during installation
                $installData = session('install_data', []);
                $installData['mail_receive_tested'] = 1;
                $installData['mail_receive_test_date'] = now()->toDateTimeString();
                session(['install_data' => $installData]);
            } else {
                // Save to mail_test_results in admin panel (reflected to DB when form is saved)
                session(['mail_test_results.mail_receive_tested' => 1]);
                session(['mail_test_results.mail_receive_test_date' => now()->toDateTimeString()]);
                session()->save(); // Force save session
            }

            // Clear token
            if ($context === 'install') {
                session()->forget('install_mail_verification_token');
            } else {
                SiteSetting::setValue('mail_verification_token', null);
            }

            // Use shared component
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
