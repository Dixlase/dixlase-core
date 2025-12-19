<?php

namespace App\DTO\FileIntegrity;

use JsonSerializable;

/**
 * スキャン対象DTO
 * 
 * ファイル整合性スキャンの対象を定義する不変データオブジェクトです。
 * 
 * @package App\DTO\FileIntegrity
 */
final readonly class ScanTargetDTO implements JsonSerializable
{
    public const SCOPE_CORE = 'core';
    public const SCOPE_PLUGIN = 'plugin';
    public const SCOPE_THEME = 'theme';
    public const SCOPE_ALL = 'all';

    /**
     * @param string $scope スコープ（core, plugin, theme, all）
     * @param string|null $identifier プラグイン/テーマのスラッグ（scope=plugin/themeの場合）
     * @param array<string> $paths スキャン対象パス
     * @param array<string> $ignorePatterns 除外パターン
     * @param string $hashAlgo ハッシュアルゴリズム
     */
    public function __construct(
        public string $scope = self::SCOPE_CORE,
        public ?string $identifier = null,
        public array $paths = [],
        public array $ignorePatterns = [],
        public string $hashAlgo = 'sha256',
    ) {}

    /**
     * コアスキャン用のターゲットを生成
     * 
     * @return self
     */
    public static function core(): self
    {
        return new self(
            scope: self::SCOPE_CORE,
            paths: [
                'app',
                'bootstrap',
                'config',
                'routes',
                'public/index.php',
                'artisan',
                'composer.json',
                'composer.lock',
            ],
            ignorePatterns: [
                'app/Custom',
                'storage',
                'vendor',
                'node_modules',
                'bootstrap/cache',
                '.git',
                '.env',
                '.env.*',
            ],
        );
    }

    /**
     * プラグインスキャン用のターゲットを生成
     * 
     * @param string $pluginSlug プラグインスラッグ
     * @return self
     */
    public static function plugin(string $pluginSlug): self
    {
        return new self(
            scope: self::SCOPE_PLUGIN,
            identifier: $pluginSlug,
            paths: [
                "plugins/{$pluginSlug}",
            ],
            ignorePatterns: [
                'vendor',
                'node_modules',
                '.git',
            ],
        );
    }

    /**
     * テーマスキャン用のターゲットを生成
     * 
     * @param string $themeSlug テーマスラッグ
     * @return self
     */
    public static function theme(string $themeSlug): self
    {
        return new self(
            scope: self::SCOPE_THEME,
            identifier: $themeSlug,
            paths: [
                "themes/{$themeSlug}",
            ],
            ignorePatterns: [
                'vendor',
                'node_modules',
                '.git',
            ],
        );
    }

    /**
     * JSON形式にシリアライズ
     * 
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'scope' => $this->scope,
            'identifier' => $this->identifier,
            'paths' => $this->paths,
            'ignore_patterns' => $this->ignorePatterns,
            'hash_algo' => $this->hashAlgo,
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
            scope: $data['scope'] ?? self::SCOPE_CORE,
            identifier: $data['identifier'] ?? null,
            paths: $data['paths'] ?? [],
            ignorePatterns: $data['ignore_patterns'] ?? [],
            hashAlgo: $data['hash_algo'] ?? 'sha256',
        );
    }

    /**
     * ベースラインファイル名を取得
     * 
     * @return string
     */
    public function getBaselineFilename(): string
    {
        return match ($this->scope) {
            self::SCOPE_PLUGIN => "plugin_{$this->identifier}_hashes.json",
            self::SCOPE_THEME => "theme_{$this->identifier}_hashes.json",
            default => 'core_hashes.json',
        };
    }
}
