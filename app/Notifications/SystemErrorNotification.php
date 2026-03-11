<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

/**
 * システムエラー通知
 */
class SystemErrorNotification extends Notification
{
    use Queueable;

    /**
     * エラー件名
     *
     * @var string
     */
    protected $subject;

    /**
     * エラーメッセージ
     *
     * @var string
     */
    protected $message;

    /**
     * コンテキスト情報
     *
     * @var array
     */
    protected $context;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $subject, string $message, array $context = [])
    {
        $this->subject = $subject;
        $this->message = $message;
        $this->context = $context;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $appName = env('APP_NAME', 'Dixlase');

        // ログレベルに応じた色を取得
        $logLevel = $this->context['log_level'] ?? 'Error';
        $levelColor = $this->getLogLevelColor($logLevel);
        $levelBgColor = $this->getLogLevelBgColor($logLevel);

        $mailMessage = (new MailMessage())
            ->subject("[{$appName}] {$this->subject}")
            ->greeting('システム管理者様');

        // レベル表示（色付き）
        $levelHtml = '<div style="padding: 12px; background-color: '.$levelBgColor.'; border-left: 4px solid '.$levelColor.'; margin: 16px 0; border-radius: 4px;">';
        $levelHtml .= '<span style="color: '.$levelColor.'; font-weight: bold; font-size: 18px;">【'.$logLevel.'】</span>';
        $levelHtml .= '<span style="color: #333; font-weight: bold; font-size: 16px; margin-left: 8px;">'.htmlspecialchars($this->subject).'</span>';
        $levelHtml .= '</div>';

        $mailMessage->line(new HtmlString($levelHtml));

        $mailMessage->line('');
        $mailMessage->line('**エラーメッセージ:**');
        $mailMessage->line($this->message);

        // エラー詳細情報を追加
        if (! empty($this->context)) {
            $mailMessage->line('');
            $mailMessage->line('**エラー詳細:**');

            if (isset($this->context['type'])) {
                $mailMessage->line("**エラータイプ:** {$this->context['type']}");
            }

            if (isset($this->context['file'])) {
                $mailMessage->line("**ファイル:** {$this->context['file']}");
            }

            if (isset($this->context['line'])) {
                $mailMessage->line("**行番号:** {$this->context['line']}");
            }

            if (isset($this->context['url'])) {
                $mailMessage->line("**URL:** {$this->context['url']}");
            }

            if (isset($this->context['user_agent'])) {
                $mailMessage->line("**User Agent:** {$this->context['user_agent']}");
            }

            if (isset($this->context['ip'])) {
                $mailMessage->line("**IPアドレス:** {$this->context['ip']}");
            }

            // スタックトレースを追加（最初の10行のみ）
            if (isset($this->context['trace'])) {
                $traceLines = explode("\n", $this->context['trace']);
                $limitedTrace = array_slice($traceLines, 0, 10);
                $mailMessage->line('**スタックトレース（抜粋）:**');
                $mailMessage->line('```');
                foreach ($limitedTrace as $traceLine) {
                    $mailMessage->line($traceLine);
                }
                $mailMessage->line('```');
            }

            // その他のコンテキスト情報を追加（適度な長さに制限）
            $excludeKeys = ['type', 'file', 'line', 'url', 'user_agent', 'ip', 'trace', 'exception', 'log_level', 'log_level_value', 'timestamp', 'channel', 'method'];
            $maxContextLength = 200;
            $contextCount = 0;
            $maxContextItems = 10;

            foreach ($this->context as $key => $value) {
                if ($contextCount >= $maxContextItems) {
                    $mailMessage->line('_（その他のコンテキスト情報は省略されました）_');
                    break;
                }

                if (! in_array($key, $excludeKeys)) {
                    if (is_string($value) || is_numeric($value)) {
                        $displayValue = is_string($value) && strlen($value) > $maxContextLength
                            ? substr($value, 0, $maxContextLength).'...'
                            : $value;
                        $mailMessage->line("**{$key}:** {$displayValue}");
                        $contextCount++;
                    } elseif (is_array($value) || is_object($value)) {
                        $jsonValue = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        if (strlen($jsonValue) > $maxContextLength) {
                            $jsonValue = substr($jsonValue, 0, $maxContextLength).'...';
                        }
                        $mailMessage->line("**{$key}:** {$jsonValue}");
                        $contextCount++;
                    }
                }
            }
        }

        // 発生日時を追加
        $mailMessage->line('**発生日時:** '.now()->format('Y-m-d H:i:s'));

        $mailMessage->line('このエラーについて調査し、必要に応じて対応をお願いします。');

        $mailMessage->salutation("よろしくお願いします。\n\n{$appName} システム");

        return $mailMessage;
    }

    /**
     * ログレベルに応じた色を取得
     */
    private function getLogLevelColor(string $logLevel): string
    {
        return match (strtoupper($logLevel)) {
            'EMERGENCY' => '#DC2626',
            'ALERT' => '#EF4444',
            'CRITICAL' => '#F97316',
            'ERROR' => '#F59E0B',
            'WARNING' => '#EAB308',
            default => '#3B82F6',
        };
    }

    /**
     * ログレベルに応じた背景色を取得
     */
    private function getLogLevelBgColor(string $logLevel): string
    {
        return match (strtoupper($logLevel)) {
            'EMERGENCY' => '#FEE2E2',
            'ALERT' => '#FEE2E2',
            'CRITICAL' => '#FFEDD5',
            'ERROR' => '#FEF3C7',
            'WARNING' => '#FEF9C3',
            default => '#DBEAFE',
        };
    }
}
