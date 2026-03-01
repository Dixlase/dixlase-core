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

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    protected $table = 'api_keys';

    protected $fillable = [
        'name',
        'key_hash',
        'key_prefix',
        'created_by',
        'is_active',
        'environment',
        'scopes',
        'rate_limit',
        'allowed_ips',
        'expires_at',
        'last_used_at',
        'usage_count',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'scopes' => 'array',
        'allowed_ips' => 'array',
        'rate_limit' => 'integer',
        'usage_count' => 'integer',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    // ========================================
    // 環境定数
    // ========================================

    public const ENV_LIVE = 'live';
    public const ENV_TEST = 'test';

    // ========================================
    // スコープ定数
    // ========================================

    public const SCOPE_READ_EVENTS = 'read:events';
    public const SCOPE_WRITE_EVENTS = 'write:events';
    public const SCOPE_READ_TRANSLATIONS = 'read:translations';
    public const SCOPE_WRITE_TRANSLATIONS = 'write:translations';
    public const SCOPE_READ_CONTENT = 'read:content';
    public const SCOPE_WRITE_CONTENT = 'write:content';

    /**
     * 利用可能なスコープ一覧
     */
    public static function availableScopes(): array
    {
        return [
            self::SCOPE_READ_EVENTS => 'イベント読み取り',
            self::SCOPE_WRITE_EVENTS => 'イベント書き込み',
            self::SCOPE_READ_TRANSLATIONS => '翻訳読み取り',
            self::SCOPE_WRITE_TRANSLATIONS => '翻訳書き込み',
            self::SCOPE_READ_CONTENT => 'コンテンツ読み取り',
            self::SCOPE_WRITE_CONTENT => 'コンテンツ書き込み',
        ];
    }

    // ========================================
    // リレーション
    // ========================================

    /**
     * 作成者とのリレーション
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'created_by');
    }

    // ========================================
    // ファクトリメソッド
    // ========================================

    /**
     * 新しいAPIキーを生成
     * 
     * @param string $name キー名
     * @param string $environment 環境（live/test）
     * @param array $scopes 権限スコープ
     * @param int|null $createdBy 作成者ID
     * @param array $options その他のオプション
     * @return array ['model' => ApiKey, 'plain_key' => string]
     */
    public static function generate(
        string $name,
        string $environment = self::ENV_LIVE,
        array $scopes = [],
        ?int $createdBy = null,
        array $options = []
    ): array {
        // プレフィックス生成
        $prefix = $environment === self::ENV_TEST ? 'dxl_test_' : 'dxl_live_';
        
        // ランダムキー生成（32文字）
        $randomKey = Str::random(32);
        $plainKey = $prefix . $randomKey;
        
        // ハッシュ化
        $keyHash = hash('sha256', $plainKey);
        
        $apiKey = self::create([
            'name' => $name,
            'key_hash' => $keyHash,
            'key_prefix' => $prefix,
            'created_by' => $createdBy,
            'is_active' => true,
            'environment' => $environment,
            'scopes' => $scopes,
            'rate_limit' => $options['rate_limit'] ?? null,
            'allowed_ips' => $options['allowed_ips'] ?? null,
            'expires_at' => $options['expires_at'] ?? null,
            'description' => $options['description'] ?? null,
        ]);
        
        return [
            'model' => $apiKey,
            'plain_key' => $plainKey,
        ];
    }

    /**
     * APIキーを検証
     * 
     * @param string $plainKey 平文のAPIキー
     * @return self|null 有効なAPIキーモデル、または無効な場合はnull
     */
    public static function validate(string $plainKey): ?self
    {
        $keyHash = hash('sha256', $plainKey);
        
        $apiKey = self::where('key_hash', $keyHash)
            ->where('is_active', true)
            ->first();
        
        if (!$apiKey) {
            return null;
        }
        
        // 有効期限チェック
        if ($apiKey->expires_at && now()->greaterThan($apiKey->expires_at)) {
            return null;
        }
        
        return $apiKey;
    }

    // ========================================
    // インスタンスメソッド
    // ========================================

    /**
     * 使用記録を更新
     */
    public function recordUsage(): void
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }

    /**
     * キーを無効化
     */
    public function revoke(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * キーを有効化
     */
    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    /**
     * 指定されたスコープを持っているか
     */
    public function hasScope(string $scope): bool
    {
        return in_array($scope, $this->scopes ?? []);
    }

    /**
     * 指定されたIPからのアクセスを許可するか
     */
    public function allowsIp(string $ip): bool
    {
        // 許可IPリストが空の場合は全IP許可
        if (empty($this->allowed_ips)) {
            return true;
        }
        
        return in_array($ip, $this->allowed_ips);
    }

    /**
     * 有効期限切れかどうか
     */
    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return false;
        }
        
        return now()->greaterThan($this->expires_at);
    }

    /**
     * マスクされたキーを取得（表示用）
     */
    public function getMaskedKey(): string
    {
        return $this->key_prefix . str_repeat('*', 8) . '...';
    }

    // ========================================
    // スコープ
    // ========================================

    /**
     * 有効なキーのみ取得
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * 環境でフィルタ
     */
    public function scopeForEnvironment($query, string $environment)
    {
        return $query->where('environment', $environment);
    }

    /**
     * 期限切れでないキーのみ取得
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }
}
