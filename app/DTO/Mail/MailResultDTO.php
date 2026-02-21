<?php

namespace App\DTO\Mail;

use JsonSerializable;

/**
 * @api プラグイン/テーマから使用可能な安定APIです
 *
 * メール送信結果DTO
 *
 * メール送信の結果を保持する不変データオブジェクトです。
 */
final readonly class MailResultDTO implements JsonSerializable
{
    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    public const STATUS_QUEUED = 'queued';

    /**
     * @param  bool  $success  成功したかどうか
     * @param  string  $status  ステータス（success, failed, queued）
     * @param  string|null  $message  メッセージ
     * @param  string|null  $messageId  メッセージID（送信成功時）
     * @param  string|null  $error  エラーメッセージ（失敗時）
     * @param  string|null  $errorCode  エラーコード（失敗時）
     * @param  array<string>  $recipients  送信先
     * @param  string  $sentAt  送信日時
     * @param  array<string,mixed>  $meta  メタデータ
     */
    public function __construct(
        public bool $success,
        public string $status,
        public ?string $message = null,
        public ?string $messageId = null,
        public ?string $error = null,
        public ?string $errorCode = null,
        public array $recipients = [],
        public string $sentAt = '',
        public array $meta = [],
    ) {}

    /**
     * 成功結果を生成
     *
     * @param  array<string>  $recipients  送信先
     * @param  string|null  $messageId  メッセージID
     * @param  string|null  $message  メッセージ
     */
    public static function success(array $recipients, ?string $messageId = null, ?string $message = null): self
    {
        return new self(
            success: true,
            status: self::STATUS_SUCCESS,
            message: $message ?? __('mail.send_success'),
            messageId: $messageId,
            recipients: $recipients,
            sentAt: now()->toIso8601String(),
        );
    }

    /**
     * 失敗結果を生成
     *
     * @param  string  $error  エラーメッセージ
     * @param  string|null  $errorCode  エラーコード
     * @param  array<string>  $recipients  送信先
     */
    public static function failed(string $error, ?string $errorCode = null, array $recipients = []): self
    {
        return new self(
            success: false,
            status: self::STATUS_FAILED,
            error: $error,
            errorCode: $errorCode,
            recipients: $recipients,
            sentAt: now()->toIso8601String(),
        );
    }

    /**
     * キュー追加結果を生成
     *
     * @param  array<string>  $recipients  送信先
     * @param  string|null  $message  メッセージ
     */
    public static function queued(array $recipients, ?string $message = null): self
    {
        return new self(
            success: true,
            status: self::STATUS_QUEUED,
            message: $message ?? __('mail.queued_success'),
            recipients: $recipients,
            sentAt: now()->toIso8601String(),
        );
    }

    /**
     * 成功したか
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * 失敗したか
     */
    public function isFailed(): bool
    {
        return ! $this->success;
    }

    /**
     * キューに追加されたか
     */
    public function isQueued(): bool
    {
        return $this->status === self::STATUS_QUEUED;
    }

    /**
     * JSON形式にシリアライズ
     *
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return array_filter([
            'success' => $this->success,
            'status' => $this->status,
            'message' => $this->message,
            'message_id' => $this->messageId,
            'error' => $this->error,
            'error_code' => $this->errorCode,
            'recipients' => $this->recipients,
            'sent_at' => $this->sentAt,
            'meta' => $this->meta,
        ], fn ($v) => $v !== null && $v !== []);
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
}
