<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact office@exc-d.com).
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

namespace App\Services;

use App\Contracts\Mail\MailServiceInterface;
use App\DTO\Mail\MailAttachmentDTO;
use App\DTO\Mail\MailConfigDTO;
use App\DTO\Mail\MailMessageDTO;
use App\DTO\Mail\MailResultDTO;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * @internal コア専用。プラグイン/テーマから参照しないこと
 *
 * メール送信サービス
 *
 * Contract対応のメール送信機能を提供します。
 */
class MailService implements MailServiceInterface
{
    /**
     * メールを送信
     */
    public function send(MailMessageDTO $message, ?MailConfigDTO $config = null): MailResultDTO
    {
        try {
            // カスタム設定がある場合は一時的に適用
            if ($config) {
                $this->applyConfig($config);
            }

            $recipients = $message->getRecipients();

            Mail::send([], [], function ($mail) use ($message) {
                // 宛先
                $mail->to($message->getRecipients());

                // 件名
                $mail->subject($message->subject);

                // 本文
                if ($message->isHtml) {
                    $mail->html($message->body);
                } else {
                    $mail->text($message->body);
                }

                // 送信元
                if ($message->from) {
                    $mail->from($message->from, $message->fromName);
                }

                // 返信先
                if ($message->replyTo) {
                    $mail->replyTo($message->replyTo);
                }

                // CC
                if ($message->hasCc()) {
                    $mail->cc($message->cc);
                }

                // BCC
                if ($message->hasBcc()) {
                    $mail->bcc($message->bcc);
                }

                // 添付ファイル
                foreach ($message->attachments as $attachment) {
                    if ($attachment instanceof MailAttachmentDTO && $attachment->exists()) {
                        $mail->attach($attachment->path, [
                            'as' => $attachment->getDisplayName(),
                            'mime' => $attachment->mime,
                        ]);
                    }
                }

                // カスタムヘッダー
                foreach ($message->headers as $name => $value) {
                    $mail->getHeaders()->addTextHeader($name, $value);
                }
            });

            Log::channel('admin_activity')->info('メール送信成功', [
                'to' => $recipients,
                'subject' => $message->subject,
                'meta' => $message->meta,
            ]);

            return MailResultDTO::success($recipients);
        } catch (\Exception $e) {
            Log::channel('admin_error')->error('メール送信失敗', [
                'to' => $message->getRecipients(),
                'subject' => $message->subject,
                'error' => $e->getMessage(),
                'meta' => $message->meta,
            ]);

            return MailResultDTO::failed($e->getMessage());
        }
    }

    /**
     * 複数のメールを一括送信
     */
    public function sendMany(array $messages, ?MailConfigDTO $config = null): array
    {
        $results = [];

        foreach ($messages as $message) {
            $results[] = $this->send($message, $config);
        }

        return $results;
    }

    /**
     * キューにメールを追加（非同期送信）
     */
    public function queue(MailMessageDTO $message, ?MailConfigDTO $config = null, ?string $queue = null): MailResultDTO
    {
        try {
            // カスタム設定がある場合は一時的に適用
            if ($config) {
                $this->applyConfig($config);
            }

            $recipients = $message->getRecipients();

            // キューに追加
            Mail::queue([], [], function ($mail) use ($message) {
                $mail->to($message->getRecipients());
                $mail->subject($message->subject);

                if ($message->isHtml) {
                    $mail->html($message->body);
                } else {
                    $mail->text($message->body);
                }

                if ($message->from) {
                    $mail->from($message->from, $message->fromName);
                }

                if ($message->replyTo) {
                    $mail->replyTo($message->replyTo);
                }

                if ($message->hasCc()) {
                    $mail->cc($message->cc);
                }

                if ($message->hasBcc()) {
                    $mail->bcc($message->bcc);
                }

                foreach ($message->attachments as $attachment) {
                    if ($attachment instanceof MailAttachmentDTO && $attachment->exists()) {
                        $mail->attach($attachment->path, [
                            'as' => $attachment->getDisplayName(),
                            'mime' => $attachment->mime,
                        ]);
                    }
                }
            });

            Log::channel('admin_activity')->info('メールをキューに追加', [
                'to' => $recipients,
                'subject' => $message->subject,
                'queue' => $queue,
            ]);

            return MailResultDTO::queued($recipients);
        } catch (\Exception $e) {
            Log::channel('admin_error')->error('メールキュー追加失敗', [
                'to' => $message->getRecipients(),
                'subject' => $message->subject,
                'error' => $e->getMessage(),
            ]);

            return MailResultDTO::failed($e->getMessage());
        }
    }

    /**
     * SMTP接続テスト
     */
    public function testConnection(MailConfigDTO $config): MailResultDTO
    {
        if (! $config->isSmtp()) {
            return MailResultDTO::failed(
                __('mail-server/test.test_functions.mailer_not_supported', ['mailer' => $config->mailer])
            );
        }

        try {
            $host = $config->host;
            $port = $config->port;
            $encryption = $config->encryption;

            // ソケット接続でSMTPサーバーに接続テスト
            $context = stream_context_create();

            if ($encryption === 'ssl') {
                $host = 'ssl://'.$host;
            }

            $socket = @stream_socket_client(
                $host.':'.$port,
                $errno,
                $errstr,
                $config->timeout,
                STREAM_CLIENT_CONNECT,
                $context
            );

            if (! $socket) {
                throw new \Exception(__('mail-server/test.test_advanced.smtp_connection_error', [
                    'error' => $errstr,
                    'errno' => $errno,
                ]));
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

                if (! stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    fclose($socket);
                    throw new \Exception(__('mail-server/test.test_advanced.smtp_tls_crypto_failed'));
                }
            }

            // 認証テスト
            if ($config->requiresAuth()) {
                fwrite($socket, "EHLO localhost\r\n");
                $response = fgets($socket);

                fwrite($socket, "AUTH LOGIN\r\n");
                $response = fgets($socket);

                if (! str_starts_with($response, '334')) {
                    fclose($socket);
                    throw new \Exception(__('mail-server/test.test_advanced.smtp_auth_login_failed', ['response' => trim($response)]));
                }

                fwrite($socket, base64_encode($config->username)."\r\n");
                $response = fgets($socket);

                if (! str_starts_with($response, '334')) {
                    fclose($socket);
                    throw new \Exception(__('mail-server/test.test_advanced.smtp_username_auth_failed', ['response' => trim($response)]));
                }

                fwrite($socket, base64_encode($config->password)."\r\n");
                $response = fgets($socket);

                if (! str_starts_with($response, '235')) {
                    fclose($socket);
                    throw new \Exception(__('mail-server/test.test_advanced.smtp_password_auth_failed', ['response' => trim($response)]));
                }
            }

            // 接続を閉じる
            fwrite($socket, "QUIT\r\n");
            fclose($socket);

            return MailResultDTO::success([], null, __('mail-server/test.test_functions.connection_test_success'));
        } catch (\Exception $e) {
            return MailResultDTO::failed($e->getMessage());
        }
    }

    /**
     * 現在のメール設定を取得
     */
    public function getConfig(): MailConfigDTO
    {
        // DBから設定を取得（存在する場合）
        try {
            if (class_exists(SiteSetting::class)) {
                return new MailConfigDTO(
                    mailer: SiteSetting::getValue('mail_mailer', config('mail.default', 'smtp')),
                    host: SiteSetting::getValue('mail_host', config('mail.mailers.smtp.host', '')),
                    port: (int) SiteSetting::getValue('mail_port', config('mail.mailers.smtp.port', 587)),
                    username: SiteSetting::getValue('mail_username', config('mail.mailers.smtp.username')),
                    password: SiteSetting::getValue('mail_password', config('mail.mailers.smtp.password')),
                    encryption: SiteSetting::getValue('mail_encryption', config('mail.mailers.smtp.encryption', 'tls')),
                    fromAddress: SiteSetting::getValue('mail_from_address', config('mail.from.address', '')),
                    fromName: SiteSetting::getValue('mail_from_name', config('mail.from.name')),
                );
            }
        } catch (\Exception $e) {
            // DBエラーの場合はconfig設定にフォールバック
        }

        return MailConfigDTO::fromSystemConfig();
    }

    /**
     * メール設定が有効かどうか
     */
    public function isConfigured(): bool
    {
        return $this->getConfig()->isValid();
    }

    /**
     * 設定を一時的に適用
     */
    protected function applyConfig(MailConfigDTO $config): void
    {
        foreach ($config->toLaravelConfig() as $key => $value) {
            Config::set($key, $value);
        }
    }
}
