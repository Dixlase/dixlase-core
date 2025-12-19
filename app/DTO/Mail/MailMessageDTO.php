<?php

namespace App\DTO\Mail;

use JsonSerializable;

/**
 * メールメッセージDTO
 * 
 * メール送信に必要な情報を保持する不変データオブジェクトです。
 * 
 * @package App\DTO\Mail
 */
final readonly class MailMessageDTO implements JsonSerializable
{
    /**
     * @param string|array<string> $to 宛先メールアドレス
     * @param string $subject 件名
     * @param string $body 本文（HTMLまたはテキスト）
     * @param bool $isHtml HTML形式かどうか
     * @param string|null $from 送信元メールアドレス
     * @param string|null $fromName 送信元名
     * @param string|null $replyTo 返信先メールアドレス
     * @param array<string> $cc CCメールアドレス
     * @param array<string> $bcc BCCメールアドレス
     * @param array<MailAttachmentDTO> $attachments 添付ファイル
     * @param array<string,mixed> $headers カスタムヘッダー
     * @param array<string,mixed> $meta メタデータ（ログ用など）
     */
    public function __construct(
        public string|array $to,
        public string $subject,
        public string $body,
        public bool $isHtml = true,
        public ?string $from = null,
        public ?string $fromName = null,
        public ?string $replyTo = null,
        public array $cc = [],
        public array $bcc = [],
        public array $attachments = [],
        public array $headers = [],
        public array $meta = [],
    ) {}

    /**
     * 宛先を配列で取得
     * 
     * @return array<string>
     */
    public function getRecipients(): array
    {
        return is_array($this->to) ? $this->to : [$this->to];
    }

    /**
     * 添付ファイルがあるか
     * 
     * @return bool
     */
    public function hasAttachments(): bool
    {
        return !empty($this->attachments);
    }

    /**
     * CCがあるか
     * 
     * @return bool
     */
    public function hasCc(): bool
    {
        return !empty($this->cc);
    }

    /**
     * BCCがあるか
     * 
     * @return bool
     */
    public function hasBcc(): bool
    {
        return !empty($this->bcc);
    }

    /**
     * JSON形式にシリアライズ
     * 
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'to' => $this->to,
            'subject' => $this->subject,
            'body' => $this->body,
            'is_html' => $this->isHtml,
            'from' => $this->from,
            'from_name' => $this->fromName,
            'reply_to' => $this->replyTo,
            'cc' => $this->cc,
            'bcc' => $this->bcc,
            'attachments_count' => count($this->attachments),
            'headers' => $this->headers,
            'meta' => $this->meta,
        ];
    }

    /**
     * 配列形式に変換
     * 
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return $this->jsonSerialize();
    }

    /**
     * 配列からDTOを生成
     * 
     * @param array<string,mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            to: $data['to'],
            subject: $data['subject'],
            body: $data['body'],
            isHtml: $data['is_html'] ?? true,
            from: $data['from'] ?? null,
            fromName: $data['from_name'] ?? null,
            replyTo: $data['reply_to'] ?? null,
            cc: $data['cc'] ?? [],
            bcc: $data['bcc'] ?? [],
            attachments: $data['attachments'] ?? [],
            headers: $data['headers'] ?? [],
            meta: $data['meta'] ?? [],
        );
    }

    /**
     * 件名を変更した新しいDTOを生成
     * 
     * @param string $subject 新しい件名
     * @return self
     */
    public function withSubject(string $subject): self
    {
        return new self(
            to: $this->to,
            subject: $subject,
            body: $this->body,
            isHtml: $this->isHtml,
            from: $this->from,
            fromName: $this->fromName,
            replyTo: $this->replyTo,
            cc: $this->cc,
            bcc: $this->bcc,
            attachments: $this->attachments,
            headers: $this->headers,
            meta: $this->meta,
        );
    }

    /**
     * 宛先を変更した新しいDTOを生成
     * 
     * @param string|array<string> $to 新しい宛先
     * @return self
     */
    public function withTo(string|array $to): self
    {
        return new self(
            to: $to,
            subject: $this->subject,
            body: $this->body,
            isHtml: $this->isHtml,
            from: $this->from,
            fromName: $this->fromName,
            replyTo: $this->replyTo,
            cc: $this->cc,
            bcc: $this->bcc,
            attachments: $this->attachments,
            headers: $this->headers,
            meta: $this->meta,
        );
    }

    /**
     * メタデータを追加した新しいDTOを生成
     * 
     * @param array<string,mixed> $meta 追加するメタデータ
     * @return self
     */
    public function withMeta(array $meta): self
    {
        return new self(
            to: $this->to,
            subject: $this->subject,
            body: $this->body,
            isHtml: $this->isHtml,
            from: $this->from,
            fromName: $this->fromName,
            replyTo: $this->replyTo,
            cc: $this->cc,
            bcc: $this->bcc,
            attachments: $this->attachments,
            headers: $this->headers,
            meta: array_merge($this->meta, $meta),
        );
    }
}
