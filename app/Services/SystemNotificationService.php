<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * https://exc-d.com
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

use App\Models\BaseSetting;
use App\Notifications\SystemErrorNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Exception;

class SystemNotificationService
{
    /**
     * システムエラー通知を送信
     *
     * @param string $subject 件名
     * @param string $message エラーメッセージ
     * @param array $context 追加のコンテキスト情報
     * @return bool 送信成功の場合true
     */
    public function sendErrorNotification(string $subject, string $message, array $context = []): bool
    {
        try {
            // 通知機能が有効かチェック
            if (!$this->isNotificationEnabled()) {
                return false;
            }

            // 通知先メールアドレスを取得
            $notificationEmail = $this->getNotificationEmail();
            if (empty($notificationEmail)) {
                Log::warning('System notification email address is not configured');
                return false;
            }

            // メールサーバーが設定済みかチェック
            if (!MailServerValidatorService::isMailServerTested()) {
                Log::warning('Mail server is not properly configured for system notifications');
                return false;
            }

            // Notificationを使用してメール送信
            Notification::route('mail', $notificationEmail)
                ->notify(new SystemErrorNotification($subject, $message, $context));

            Log::info('System error notification sent successfully', [
                'to' => $notificationEmail,
                'subject' => $subject
            ]);

            return true;

        } catch (Exception $e) {
            Log::error('Failed to send system error notification', [
                'error' => $e->getMessage(),
                'subject' => $subject,
                'message' => $message
            ]);
            return false;
        }
    }

    /**
     * 管理者に通知を送信（汎用）
     *
     * @param string $subject 件名
     * @param string $message メッセージ
     * @param array $context 追加のコンテキスト情報
     * @return bool 送信成功の場合true
     */
    public function sendAdminNotification(string $subject, string $message, array $context = []): bool
    {
        try {
            // 通知機能が有効かチェック
            if (!$this->isNotificationEnabled()) {
                return false;
            }

            // 通知先メールアドレスを取得
            $notificationEmail = $this->getNotificationEmail();
            if (empty($notificationEmail)) {
                Log::warning('System notification email address is not configured');
                return false;
            }

            // メールサーバーが設定済みかチェック
            if (!MailServerValidatorService::isMailServerTested()) {
                Log::warning('Mail server is not properly configured for system notifications');
                return false;
            }

            // メール内容を作成
            $mailMessage = $this->buildNotificationMail($subject, $message, $context);

            // メール送信
            Mail::send([], [], function ($mail) use ($notificationEmail, $mailMessage) {
                $mail->to($notificationEmail)
                     ->subject($mailMessage->subject)
                     ->html((string) $mailMessage->render());
            });

            Log::info('Admin notification sent successfully', [
                'to' => $notificationEmail,
                'subject' => $subject
            ]);

            return true;

        } catch (Exception $e) {
            Log::error('Failed to send admin notification', [
                'error' => $e->getMessage(),
                'subject' => $subject,
                'message' => $message
            ]);
            return false;
        }
    }

    /**
     * データベースエラー通知を送信
     *
     * @param Exception $exception
     * @return bool
     */
    public function sendDatabaseErrorNotification(Exception $exception): bool
    {
        return $this->sendErrorNotification(
            'データベースエラーが発生しました',
            $exception->getMessage(),
            [
                'type' => 'database_error',
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString()
            ]
        );
    }

    /**
     * アプリケーションエラー通知を送信
     *
     * @param Exception $exception
     * @return bool
     */
    public function sendApplicationErrorNotification(Exception $exception): bool
    {
        return $this->sendErrorNotification(
            'アプリケーションエラーが発生しました',
            $exception->getMessage(),
            [
                'type' => 'application_error',
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString()
            ]
        );
    }

    /**
     * 通知機能が有効かチェック
     *
     * @return bool
     */
    public function isNotificationEnabled(): bool
    {
        try {
            // SecuritySettingから通知設定を取得
            $enabled = \DB::table('security_settings')
                ->where('name', 'notification_enabled')
                ->value('value');
            
            return (bool) $enabled;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 通知先メールアドレスを取得
     *
     * @return string
     */
    public function getNotificationEmail(): string
    {
        // システム管理者メールアドレスを優先、なければnotification_emailを使用
        $adminEmail = BaseSetting::getValue('system_admin_email', '');
        if (!empty($adminEmail)) {
            return $adminEmail;
        }
        return BaseSetting::getValue('notification_email', '');
    }


    /**
     * 管理者通知メールを構築（汎用）
     *
     * @param string $subject
     * @param string $message
     * @param array $context
     * @return MailMessage
     */
    private function buildNotificationMail(string $subject, string $message, array $context = []): MailMessage
    {
        $appName = env('APP_NAME', 'Dixlase');
        
        $mailMessage = new MailMessage;
        $mailMessage->subject("[{$appName}] {$subject}");
        $mailMessage->greeting('システム管理者様');
        
        $mailMessage->line("**{$subject}**");
        $mailMessage->line($message);
        
        // 追加情報を表示
        if (!empty($context)) {
            $mailMessage->line('**詳細情報:**');
            
            foreach ($context as $key => $value) {
                if (is_string($value) || is_numeric($value)) {
                    $mailMessage->line("**{$key}:** {$value}");
                }
            }
        }
        
        // 発生日時を追加
        $mailMessage->line("**通知日時:** " . now()->format('Y-m-d H:i:s'));
        
        $mailMessage->salutation("よろしくお願いします。\n\n{$appName} システム");
        
        return $mailMessage;
    }

    /**
     * エラー通知メールを構築
     *
     * @param string $subject
     * @param string $message
     * @param array $context
     * @return MailMessage
     */
    private function buildErrorNotificationMail(string $subject, string $message, array $context = []): MailMessage
    {
        $appName = env('APP_NAME', 'Dixlase');
        
        // ログレベルに応じた色を取得
        $logLevel = $context['log_level'] ?? 'Error';
        $levelColor = $this->getLogLevelColor($logLevel);
        $levelBgColor = $this->getLogLevelBgColor($logLevel);
        
        $mailMessage = new MailMessage;
        $mailMessage->subject("[{$appName}] {$subject}");
        $mailMessage->greeting('システム管理者様');
        
        // レベル表示（色付き）- HTMLとして直接追加
        $levelHtml = '<div style="padding: 12px; background-color: ' . $levelBgColor . '; border-left: 4px solid ' . $levelColor . '; margin: 16px 0; border-radius: 4px;">';
        $levelHtml .= '<span style="color: ' . $levelColor . '; font-weight: bold; font-size: 18px;">【' . $logLevel . '】</span>';
        $levelHtml .= '<span style="color: #333; font-weight: bold; font-size: 16px; margin-left: 8px;">' . htmlspecialchars($subject) . '</span>';
        $levelHtml .= '</div>';
        
        $mailMessage->line(new \Illuminate\Support\HtmlString($levelHtml));
        
        $mailMessage->line('');
        $mailMessage->line("**エラーメッセージ:**");
        $mailMessage->line($message);
        
        // エラー詳細情報を追加
        if (!empty($context)) {
            $mailMessage->line('');
            $mailMessage->line('**エラー詳細:**');
            
            if (isset($context['type'])) {
                $mailMessage->line("**エラータイプ:** {$context['type']}");
            }
            
            if (isset($context['file'])) {
                $mailMessage->line("**ファイル:** {$context['file']}");
            }
            
            if (isset($context['line'])) {
                $mailMessage->line("**行番号:** {$context['line']}");
            }
            
            if (isset($context['url'])) {
                $mailMessage->line("**URL:** {$context['url']}");
            }
            
            if (isset($context['user_agent'])) {
                $mailMessage->line("**User Agent:** {$context['user_agent']}");
            }
            
            if (isset($context['ip'])) {
                $mailMessage->line("**IPアドレス:** {$context['ip']}");
            }
            
            // スタックトレースを追加（最初の10行のみ）
            if (isset($context['trace'])) {
                $traceLines = explode("\n", $context['trace']);
                $limitedTrace = array_slice($traceLines, 0, 10);
                $mailMessage->line("**スタックトレース（抜粋）:**");
                $mailMessage->line('```');
                foreach ($limitedTrace as $traceLine) {
                    $mailMessage->line($traceLine);
                }
                $mailMessage->line('```');
            }
            
            // その他のコンテキスト情報を追加（適度な長さに制限）
            $excludeKeys = ['type', 'file', 'line', 'url', 'user_agent', 'ip', 'trace', 'exception', 'log_level', 'log_level_value', 'timestamp', 'channel', 'method'];
            $maxContextLength = 200; // 最大200文字
            $contextCount = 0;
            $maxContextItems = 10; // 最大10項目
            
            foreach ($context as $key => $value) {
                if ($contextCount >= $maxContextItems) {
                    $mailMessage->line('_（その他のコンテキスト情報は省略されました）_');
                    break;
                }
                
                if (!in_array($key, $excludeKeys)) {
                    if (is_string($value) || is_numeric($value)) {
                        // 長い値は切り詰める
                        $displayValue = is_string($value) && strlen($value) > $maxContextLength 
                            ? substr($value, 0, $maxContextLength) . '...' 
                            : $value;
                        $mailMessage->line("**{$key}:** {$displayValue}");
                        $contextCount++;
                    } elseif (is_array($value) || is_object($value)) {
                        // 配列やオブジェクトはJSON形式で表示（制限付き）
                        $jsonValue = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        if (strlen($jsonValue) > $maxContextLength) {
                            $jsonValue = substr($jsonValue, 0, $maxContextLength) . '...';
                        }
                        $mailMessage->line("**{$key}:** {$jsonValue}");
                        $contextCount++;
                    }
                }
            }
        }
        
        // 発生日時を追加
        $mailMessage->line("**発生日時:** " . now()->format('Y-m-d H:i:s'));
        
        $mailMessage->line('このエラーについて調査し、必要に応じて対応をお願いします。');
        
        $mailMessage->salutation("よろしくお願いします。\n\n{$appName} システム");
        
        return $mailMessage;
    }

    /**
     * ログレベルに応じた色を取得
     *
     * @param string $logLevel
     * @return string
     */
    private function getLogLevelColor(string $logLevel): string
    {
        return match (strtoupper($logLevel)) {
            'EMERGENCY' => '#DC2626', // 赤（濃い）
            'ALERT' => '#EF4444',     // 赤
            'CRITICAL' => '#F97316',  // オレンジ
            'ERROR' => '#F59E0B',     // 黄色（濃い）
            'WARNING' => '#EAB308',   // 黄色
            default => '#3B82F6',     // 青（その他）
        };
    }

    /**
     * ログレベルに応じた背景色を取得
     *
     * @param string $logLevel
     * @return string
     */
    private function getLogLevelBgColor(string $logLevel): string
    {
        return match (strtoupper($logLevel)) {
            'EMERGENCY' => '#FEE2E2', // 赤（薄い）
            'ALERT' => '#FEE2E2',     // 赤（薄い）
            'CRITICAL' => '#FFEDD5',  // オレンジ（薄い）
            'ERROR' => '#FEF3C7',     // 黄色（薄い）
            'WARNING' => '#FEF9C3',   // 黄色（薄い）
            default => '#DBEAFE',     // 青（薄い）
        };
    }
}
