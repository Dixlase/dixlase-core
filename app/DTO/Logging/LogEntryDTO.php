<?php

namespace App\DTO\Logging;

use JsonSerializable;

/**
 * ログエントリDTO
 * 
 * ログファイルから読み取ったエントリを保持する不変データオブジェクトです。
 * 
 * @package App\DTO\Logging
 */
final readonly class LogEntryDTO implements JsonSerializable
{
    public const LEVEL_DEBUG = 'debug';
    public const LEVEL_INFO = 'info';
    public const LEVEL_NOTICE = 'notice';
    public const LEVEL_WARNING = 'warning';
    public const LEVEL_ERROR = 'error';
    public const LEVEL_CRITICAL = 'critical';
    public const LEVEL_ALERT = 'alert';
    public const LEVEL_EMERGENCY = 'emergency';

    /**
     * @param string $level ログレベル
     * @param string $message メッセージ
     * @param string $channel チャンネル名
     * @param string $timestamp タイムスタンプ
     * @param array<string,mixed> $context コンテキスト
     * @param string|null $source ソース（ファイル名など）
     * @param int|null $line 行番号
     */
    public function __construct(
        public string $level,
        public string $message,
        public string $channel,
        public string $timestamp,
        public array $context = [],
        public ?string $source = null,
        public ?int $line = null,
    ) {}

    /**
     * エラーレベルかどうか
     * 
     * @return bool
     */
    public function isError(): bool
    {
        return in_array($this->level, [
            self::LEVEL_ERROR,
            self::LEVEL_CRITICAL,
            self::LEVEL_ALERT,
            self::LEVEL_EMERGENCY,
        ]);
    }

    /**
     * 警告レベルかどうか
     * 
     * @return bool
     */
    public function isWarning(): bool
    {
        return $this->level === self::LEVEL_WARNING;
    }

    /**
     * 情報レベルかどうか
     * 
     * @return bool
     */
    public function isInfo(): bool
    {
        return $this->level === self::LEVEL_INFO;
    }

    /**
     * デバッグレベルかどうか
     * 
     * @return bool
     */
    public function isDebug(): bool
    {
        return $this->level === self::LEVEL_DEBUG;
    }

    /**
     * ログレベルの重要度を取得（数値）
     * 
     * @return int
     */
    public function getSeverity(): int
    {
        return match ($this->level) {
            self::LEVEL_EMERGENCY => 8,
            self::LEVEL_ALERT => 7,
            self::LEVEL_CRITICAL => 6,
            self::LEVEL_ERROR => 5,
            self::LEVEL_WARNING => 4,
            self::LEVEL_NOTICE => 3,
            self::LEVEL_INFO => 2,
            self::LEVEL_DEBUG => 1,
            default => 0,
        };
    }

    /**
     * JSON形式にシリアライズ
     * 
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'level' => $this->level,
            'message' => $this->message,
            'channel' => $this->channel,
            'timestamp' => $this->timestamp,
            'context' => $this->context,
            'source' => $this->source,
            'line' => $this->line,
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
            level: $data['level'] ?? self::LEVEL_INFO,
            message: $data['message'] ?? '',
            channel: $data['channel'] ?? 'default',
            timestamp: $data['timestamp'] ?? now()->toDateTimeString(),
            context: $data['context'] ?? [],
            source: $data['source'] ?? null,
            line: $data['line'] ?? null,
        );
    }

    /**
     * ログ行をパースしてDTOを生成
     * 
     * @param string $line ログ行
     * @param string $channel チャンネル名
     * @return self|null
     */
    public static function fromLogLine(string $line, string $channel = 'default'): ?self
    {
        // Laravel標準のログフォーマットをパース
        // [2025-01-15 12:34:56] local.INFO: Message {"context":"value"}
        $pattern = '/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (\w+)\.(\w+): (.+)$/';

        if (!preg_match($pattern, $line, $matches)) {
            return null;
        }

        $timestamp = $matches[1];
        $env = $matches[2];
        $level = strtolower($matches[3]);
        $messageWithContext = $matches[4];

        // メッセージとコンテキストを分離
        $context = [];
        $message = $messageWithContext;

        if (preg_match('/^(.+?) (\{.+\}|\[.+\])$/', $messageWithContext, $msgMatches)) {
            $message = $msgMatches[1];
            $contextJson = $msgMatches[2];
            $decoded = json_decode($contextJson, true);
            if (is_array($decoded)) {
                $context = $decoded;
            }
        }

        return new self(
            level: $level,
            message: $message,
            channel: $channel,
            timestamp: $timestamp,
            context: $context,
        );
    }
}
