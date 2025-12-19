<?php

namespace App\DTO\Mail;

use JsonSerializable;

/**
 * メール添付ファイルDTO
 * 
 * メールの添付ファイル情報を保持する不変データオブジェクトです。
 * 
 * @package App\DTO\Mail
 */
final readonly class MailAttachmentDTO implements JsonSerializable
{
    /**
     * @param string $path ファイルパス
     * @param string|null $name 表示名（nullの場合はファイル名を使用）
     * @param string|null $mime MIMEタイプ（nullの場合は自動検出）
     */
    public function __construct(
        public string $path,
        public ?string $name = null,
        public ?string $mime = null,
    ) {}

    /**
     * ファイルパスから生成
     * 
     * @param string $path ファイルパス
     * @param string|null $name 表示名
     * @return self
     */
    public static function fromPath(string $path, ?string $name = null): self
    {
        return new self(
            path: $path,
            name: $name ?? basename($path),
        );
    }

    /**
     * ストレージパスから生成
     * 
     * @param string $storagePath ストレージ相対パス
     * @param string|null $name 表示名
     * @return self
     */
    public static function fromStorage(string $storagePath, ?string $name = null): self
    {
        return new self(
            path: storage_path('app/' . $storagePath),
            name: $name ?? basename($storagePath),
        );
    }

    /**
     * 表示名を取得
     * 
     * @return string
     */
    public function getDisplayName(): string
    {
        return $this->name ?? basename($this->path);
    }

    /**
     * ファイルが存在するか
     * 
     * @return bool
     */
    public function exists(): bool
    {
        return file_exists($this->path);
    }

    /**
     * ファイルサイズを取得
     * 
     * @return int|null
     */
    public function getSize(): ?int
    {
        if (!$this->exists()) {
            return null;
        }
        return filesize($this->path) ?: null;
    }

    /**
     * JSON形式にシリアライズ
     * 
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'path' => $this->path,
            'name' => $this->getDisplayName(),
            'mime' => $this->mime,
            'size' => $this->getSize(),
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
            path: $data['path'],
            name: $data['name'] ?? null,
            mime: $data['mime'] ?? null,
        );
    }
}
