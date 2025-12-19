<?php

namespace App\Contracts\Mail;

use App\DTO\Mail\MailMessageDTO;
use App\DTO\Mail\MailResultDTO;
use App\DTO\Mail\MailConfigDTO;

/**
 * メール送信サービスの契約
 * 
 * コアおよびプラグインからメール送信機能を利用するための
 * 統一インターフェースを提供します。
 * 
 * @package App\Contracts\Mail
 */
interface MailServiceInterface
{
    /**
     * メールを送信
     * 
     * @param MailMessageDTO $message メールメッセージ
     * @param MailConfigDTO|null $config カスタム設定（nullの場合はシステム設定を使用）
     * @return MailResultDTO
     */
    public function send(MailMessageDTO $message, ?MailConfigDTO $config = null): MailResultDTO;

    /**
     * 複数のメールを一括送信
     * 
     * @param array<MailMessageDTO> $messages メールメッセージの配列
     * @param MailConfigDTO|null $config カスタム設定
     * @return array<MailResultDTO>
     */
    public function sendMany(array $messages, ?MailConfigDTO $config = null): array;

    /**
     * キューにメールを追加（非同期送信）
     * 
     * @param MailMessageDTO $message メールメッセージ
     * @param MailConfigDTO|null $config カスタム設定
     * @param string|null $queue キュー名
     * @return MailResultDTO
     */
    public function queue(MailMessageDTO $message, ?MailConfigDTO $config = null, ?string $queue = null): MailResultDTO;

    /**
     * SMTP接続テスト
     * 
     * @param MailConfigDTO $config メール設定
     * @return MailResultDTO
     */
    public function testConnection(MailConfigDTO $config): MailResultDTO;

    /**
     * 現在のメール設定を取得
     * 
     * @return MailConfigDTO
     */
    public function getConfig(): MailConfigDTO;

    /**
     * メール設定が有効かどうか
     * 
     * @return bool
     */
    public function isConfigured(): bool;
}
