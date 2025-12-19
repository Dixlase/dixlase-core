<?php

namespace App\DTO\Mail;

use JsonSerializable;

/**
 * メール設定DTO
 * 
 * メールサーバー設定を保持する不変データオブジェクトです。
 * 
 * @package App\DTO\Mail
 */
final readonly class MailConfigDTO implements JsonSerializable
{
    public const MAILER_SMTP = 'smtp';
    public const MAILER_SENDMAIL = 'sendmail';
    public const MAILER_LOG = 'log';

    public const ENCRYPTION_NONE = '';
    public const ENCRYPTION_TLS = 'tls';
    public const ENCRYPTION_SSL = 'ssl';

    /**
     * @param string $mailer メーラー（smtp, sendmail, log）
     * @param string $host SMTPホスト
     * @param int $port SMTPポート
     * @param string|null $username SMTP認証ユーザー名
     * @param string|null $password SMTP認証パスワード
     * @param string $encryption 暗号化方式（'', 'tls', 'ssl'）
     * @param string $fromAddress デフォルト送信元アドレス
     * @param string|null $fromName デフォルト送信元名
     * @param int $timeout 接続タイムアウト（秒）
     */
    public function __construct(
        public string $mailer = self::MAILER_SMTP,
        public string $host = '',
        public int $port = 587,
        public ?string $username = null,
        public ?string $password = null,
        public string $encryption = self::ENCRYPTION_TLS,
        public string $fromAddress = '',
        public ?string $fromName = null,
        public int $timeout = 30,
    ) {}

    /**
     * SMTPかどうか
     * 
     * @return bool
     */
    public function isSmtp(): bool
    {
        return $this->mailer === self::MAILER_SMTP;
    }

    /**
     * 認証が必要かどうか
     * 
     * @return bool
     */
    public function requiresAuth(): bool
    {
        return !empty($this->username) && !empty($this->password);
    }

    /**
     * 暗号化が有効かどうか
     * 
     * @return bool
     */
    public function hasEncryption(): bool
    {
        return !empty($this->encryption);
    }

    /**
     * 設定が有効かどうか（最低限の設定があるか）
     * 
     * @return bool
     */
    public function isValid(): bool
    {
        if ($this->mailer === self::MAILER_SMTP) {
            return !empty($this->host) && $this->port > 0 && !empty($this->fromAddress);
        }
        return !empty($this->fromAddress);
    }

    /**
     * JSON形式にシリアライズ（パスワードは除外）
     * 
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'mailer' => $this->mailer,
            'host' => $this->host,
            'port' => $this->port,
            'username' => $this->username,
            'encryption' => $this->encryption,
            'from_address' => $this->fromAddress,
            'from_name' => $this->fromName,
            'timeout' => $this->timeout,
            // パスワードは含めない
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
            mailer: $data['mailer'] ?? $data['mail_mailer'] ?? self::MAILER_SMTP,
            host: $data['host'] ?? $data['mail_host'] ?? '',
            port: (int) ($data['port'] ?? $data['mail_port'] ?? 587),
            username: $data['username'] ?? $data['mail_username'] ?? null,
            password: $data['password'] ?? $data['mail_password'] ?? null,
            encryption: $data['encryption'] ?? $data['mail_encryption'] ?? self::ENCRYPTION_TLS,
            fromAddress: $data['from_address'] ?? $data['mail_from_address'] ?? '',
            fromName: $data['from_name'] ?? $data['mail_from_name'] ?? null,
            timeout: (int) ($data['timeout'] ?? 30),
        );
    }

    /**
     * システム設定からDTOを生成
     * 
     * @return self
     */
    public static function fromSystemConfig(): self
    {
        return new self(
            mailer: config('mail.default', self::MAILER_SMTP),
            host: config('mail.mailers.smtp.host', ''),
            port: (int) config('mail.mailers.smtp.port', 587),
            username: config('mail.mailers.smtp.username'),
            password: config('mail.mailers.smtp.password'),
            encryption: config('mail.mailers.smtp.encryption', self::ENCRYPTION_TLS),
            fromAddress: config('mail.from.address', ''),
            fromName: config('mail.from.name'),
            timeout: (int) config('mail.mailers.smtp.timeout', 30),
        );
    }

    /**
     * Laravelのメール設定配列に変換
     * 
     * @return array<string,mixed>
     */
    public function toLaravelConfig(): array
    {
        return [
            'mail.default' => $this->mailer,
            'mail.mailers.smtp.host' => $this->host,
            'mail.mailers.smtp.port' => $this->port,
            'mail.mailers.smtp.username' => $this->username,
            'mail.mailers.smtp.password' => $this->password,
            'mail.mailers.smtp.encryption' => $this->encryption,
            'mail.mailers.smtp.timeout' => $this->timeout,
            'mail.from.address' => $this->fromAddress,
            'mail.from.name' => $this->fromName ?? config('app.name'),
        ];
    }
}
