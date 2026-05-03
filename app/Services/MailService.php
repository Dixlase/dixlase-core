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
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
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
 * @internal For Core use only. Do not reference from plugins/themes
 *
 * Mail sending service
 *
 * Provides Contract-compatible mail sending functionality
 */
class MailService implements MailServiceInterface
{
    /**
     * Send mail
     */
    public function send(MailMessageDTO $message, ?MailConfigDTO $config = null): MailResultDTO
    {
        try {
            // Temporarily apply custom settings if present
            if ($config) {
                $this->applyConfig($config);
            }

            $recipients = $message->getRecipients();

            Mail::send([], [], function ($mail) use ($message) {
                // Recipient
                $mail->to($message->getRecipients());

                // Subject
                $mail->subject($message->subject);

                // Body
                if ($message->isHtml) {
                    $mail->html($message->body);
                } else {
                    $mail->text($message->body);
                }

                // From
                if ($message->from) {
                    $mail->from($message->from, $message->fromName);
                }

                // Reply-to
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

                // Attachments
                foreach ($message->attachments as $attachment) {
                    if ($attachment instanceof MailAttachmentDTO && $attachment->exists()) {
                        $mail->attach($attachment->path, [
                            'as' => $attachment->getDisplayName(),
                            'mime' => $attachment->mime,
                        ]);
                    }
                }

                // Custom headers
                foreach ($message->headers as $name => $value) {
                    $mail->getHeaders()->addTextHeader($name, $value);
                }
            });

            Log::channel('admin_activity')->info(__('services/mail_service.mail_sent_success'), [
                'to' => $recipients,
                'subject' => $message->subject,
                'meta' => $message->meta,
            ]);

            return MailResultDTO::success($recipients);
        } catch (\Exception $e) {
            Log::channel('admin_error')->error(__('services/mail_service.mail_sent_failure'), [
                'to' => $message->getRecipients(),
                'subject' => $message->subject,
                'error' => $e->getMessage(),
                'meta' => $message->meta,
            ]);

            return MailResultDTO::failed($e->getMessage());
        }
    }

    /**
     * Send multiple mails in bulk
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
     * Add mail to queue (asynchronous sending)
     */
    public function queue(MailMessageDTO $message, ?MailConfigDTO $config = null, ?string $queue = null): MailResultDTO
    {
        try {
            // Temporarily apply custom settings if present
            if ($config) {
                $this->applyConfig($config);
            }

            $recipients = $message->getRecipients();

            // Add to queue
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

            Log::channel('admin_activity')->info(__('services/mail_service.add_mail_to_queue'), [
                'to' => $recipients,
                'subject' => $message->subject,
                'queue' => $queue,
            ]);

            return MailResultDTO::queued($recipients);
        } catch (\Exception $e) {
            Log::channel('admin_error')->error(__('services/mail_service.mail_queue_add_failure'), [
                'to' => $message->getRecipients(),
                'subject' => $message->subject,
                'error' => $e->getMessage(),
            ]);

            return MailResultDTO::failed($e->getMessage());
        }
    }

    /**
     * SMTP connection test
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

            // Test connection to SMTP server via socket connection
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

            // Read SMTP response
            $response = fgets($socket);

            if (! $response || ! str_starts_with($response, '220')) {
                fclose($socket);
                throw new \Exception(__('mail-server/test.test_advanced.smtp_response_invalid', ['response' => trim($response)]));
            }

            // If STARTTLS is required
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

            // Authentication test
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

            // Close the connection
            fwrite($socket, "QUIT\r\n");
            fclose($socket);

            return MailResultDTO::success([], null, __('mail-server/test.test_functions.connection_test_success'));
        } catch (\Exception $e) {
            return MailResultDTO::failed($e->getMessage());
        }
    }

    /**
     * Get current mail settings
     */
    public function getConfig(): MailConfigDTO
    {
        // Get settings from DB (if exists)
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
            // Fall back to config settings in case of DB error
        }

        return MailConfigDTO::fromSystemConfig();
    }

    /**
     * Whether mail settings are enabled
     */
    public function isConfigured(): bool
    {
        return $this->getConfig()->isValid();
    }

    /**
     * Apply settings temporarily
     */
    protected function applyConfig(MailConfigDTO $config): void
    {
        foreach ($config->toLaravelConfig() as $key => $value) {
            Config::set($key, $value);
        }
    }
}
