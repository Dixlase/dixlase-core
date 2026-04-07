<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
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

namespace App\Services\Auth;

/**
 * 認証コンテキストレジストリ
 *
 * プラグインが独自の認証コンテキスト（ルート、翻訳プレフィックスなど）を
 * 登録できるようにするためのレジストリサービス
 */
class AuthContextRegistryService
{
    /**
     * 登録された認証コンテキスト
     *
     * @var array<string, array>
     */
    protected static array $contexts = [];

    /**
     * デフォルトの管理者コンテキスト
     */
    protected static array $defaultAdminContext = [
        'guard' => 'member',
        'routes' => [
            'login' => 'admin.login',
            'dashboard' => 'admin.dashboard',
            'two_fa.email' => 'admin.two-fa.email.show',
            'two_fa.recovery_code' => 'admin.two-fa.recovery-code.show',
        ],
        'translation_prefix' => 'admin/members',
        'entity_type' => 'member',
    ];

    /**
     * 認証コンテキストを登録
     *
     * @param  string  $context  コンテキスト名（例: 'user', 'admin'）
     * @param  array  $config  設定配列
     */
    public static function register(string $context, array $config): void
    {
        static::$contexts[$context] = array_merge([
            'guard' => $context,
            'routes' => [],
            'translation_prefix' => null,
            'entity_type' => $context,
        ], $config);
    }

    /**
     * 登録されたコンテキストを取得
     *
     * @param  string  $context  コンテキスト名
     */
    public static function get(string $context): ?array
    {
        // 管理者コンテキストはデフォルト設定を使用
        if ($context === 'admin') {
            return static::$defaultAdminContext;
        }

        return static::$contexts[$context] ?? null;
    }

    /**
     * ルート名を取得
     *
     * @param  string  $context  コンテキスト名
     * @param  string  $routeKey  ルートキー（例: 'login', 'dashboard'）
     */
    public static function getRoute(string $context, string $routeKey): ?string
    {
        $config = static::get($context);

        return $config['routes'][$routeKey] ?? null;
    }

    /**
     * 翻訳プレフィックスを取得
     *
     * @param  string  $context  コンテキスト名
     */
    public static function getTranslationPrefix(string $context): ?string
    {
        $config = static::get($context);

        return $config['translation_prefix'] ?? null;
    }

    /**
     * エンティティタイプを取得
     *
     * @param  string  $context  コンテキスト名
     */
    public static function getEntityType(string $context): ?string
    {
        $config = static::get($context);

        return $config['entity_type'] ?? null;
    }

    /**
     * ガード名を取得
     *
     * @param  string  $context  コンテキスト名
     */
    public static function getGuard(string $context): ?string
    {
        $config = static::get($context);

        return $config['guard'] ?? null;
    }

    /**
     * すべての登録済みコンテキストを取得
     */
    public static function all(): array
    {
        return array_merge(
            ['admin' => static::$defaultAdminContext],
            static::$contexts
        );
    }

    /**
     * コンテキストが登録されているか確認
     *
     * @param  string  $context  コンテキスト名
     */
    public static function has(string $context): bool
    {
        return $context === 'admin' || isset(static::$contexts[$context]);
    }

    /**
     * すべてのコンテキストをクリア（テスト用）
     */
    public static function clear(): void
    {
        static::$contexts = [];
    }
}
