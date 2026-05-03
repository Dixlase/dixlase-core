<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact office@exc-d.com).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
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

namespace App\Services\Plugin;

use App\Contracts\Plugin\PluginCapabilityInterface;
use App\DTO\Plugin\CapabilityResolutionResult;
use Illuminate\Support\Facades\Log;

/**
 * プラグイン機能の権限チェック付き解決サービス
 *
 * プラグインが提供する機能（PluginCapabilityInterface の実装）を
 * 権限チェック付きで解決します。
 *
 * プラグインの ServiceProvider で以下のようにタグ付き登録:
 * ```php
 * $this->app->tag([MyMailCapable::class], 'plugin.capabilities');
 * ```
 *
 * 利用側:
 * ```php
 * $resolver = app(PluginServiceResolver::class);
 * $result = $resolver->resolve(MailCapableInterface::class);
 * ```
 */
class PluginServiceResolver
{
    /**
     * サービスコンテナのタグ名
     */
    public const CAPABILITY_TAG = 'plugin.capabilities';

    /**
     * インターフェースと必要権限のマッピング
     *
     * @var array<class-string<PluginCapabilityInterface>, string>
     */
    protected array $permissionMap = [];

    /**
     * 手動登録された機能インスタンス
     *
     * @var array<class-string<PluginCapabilityInterface>, array<PluginCapabilityInterface>>
     */
    protected array $registered = [];

    public function __construct(
        protected PluginPermissionService $permissionService,
    ) {}

    /**
     * インターフェースに必要な権限を登録
     *
     * @param  class-string<PluginCapabilityInterface>  $interface  機能インターフェース
     * @param  string  $permission  必要な権限キー（例: 'mail.send'）
     */
    public function registerPermission(string $interface, string $permission): void
    {
        $this->permissionMap[$interface] = $permission;
    }

    /**
     * 機能インスタンスを手動登録
     *
     * @param  class-string<PluginCapabilityInterface>  $interface  機能インターフェース
     * @param  PluginCapabilityInterface  $instance  実装インスタンス
     */
    public function register(string $interface, PluginCapabilityInterface $instance): void
    {
        $this->registered[$interface][] = $instance;
    }

    /**
     * 特定のインターフェースを実装する最初のプラグインを権限チェック付きで解決
     *
     * @param  class-string<PluginCapabilityInterface>  $interface  機能インターフェース
     * @param  string|null  $pluginSlug  特定のプラグインに限定する場合
     */
    public function resolve(string $interface, ?string $pluginSlug = null): CapabilityResolutionResult
    {
        $instances = $this->getInstances($interface);
        $lastFailure = null;

        foreach ($instances as $instance) {
            if ($pluginSlug !== null && $instance->getPluginSlug() !== $pluginSlug) {
                continue;
            }

            $result = $this->checkAndWrap($interface, $instance);
            if ($result->isResolved()) {
                return $result;
            }

            $lastFailure = $result;
        }

        return $lastFailure ?? CapabilityResolutionResult::notFound($pluginSlug);
    }

    /**
     * 特定のインターフェースを実装する全プラグインを権限チェック付きで解決
     *
     * @param  class-string<PluginCapabilityInterface>  $interface  機能インターフェース
     * @return array<CapabilityResolutionResult>
     */
    public function resolveAll(string $interface): array
    {
        $instances = $this->getInstances($interface);
        $results = [];

        foreach ($instances as $instance) {
            $results[] = $this->checkAndWrap($interface, $instance);
        }

        return $results;
    }

    /**
     * 特定のインターフェースが利用可能か確認
     *
     * @param  class-string<PluginCapabilityInterface>  $interface  機能インターフェース
     * @param  string|null  $pluginSlug  特定のプラグインに限定する場合
     */
    public function has(string $interface, ?string $pluginSlug = null): bool
    {
        return $this->resolve($interface, $pluginSlug)->isResolved();
    }

    /**
     * 登録済みの機能インターフェース一覧を取得
     *
     * @return array<class-string<PluginCapabilityInterface>>
     */
    public function getRegisteredInterfaces(): array
    {
        return array_unique(array_merge(
            array_keys($this->registered),
            array_keys($this->permissionMap),
        ));
    }

    /**
     * 特定のインターフェースの全インスタンスを取得（権限チェックなし）
     *
     * @param  class-string<PluginCapabilityInterface>  $interface
     * @return array<PluginCapabilityInterface>
     */
    protected function getInstances(string $interface): array
    {
        $instances = $this->registered[$interface] ?? [];

        // サービスコンテナのタグからも取得
        try {
            $tagged = app()->tagged(self::CAPABILITY_TAG);
            foreach ($tagged as $service) {
                if ($service instanceof $interface && ! $this->isDuplicate($instances, $service)) {
                    $instances[] = $service;
                }
            }
        } catch (\Throwable) {
            // タグが未登録の場合は無視
        }

        return $instances;
    }

    /**
     * 権限チェックを行い結果をラップ
     *
     * @param  class-string<PluginCapabilityInterface>  $interface
     */
    protected function checkAndWrap(string $interface, PluginCapabilityInterface $instance): CapabilityResolutionResult
    {
        $pluginSlug = $instance->getPluginSlug();

        // 機能が利用不可の場合
        if (! $instance->isCapabilityAvailable()) {
            return CapabilityResolutionResult::unavailable($pluginSlug);
        }

        // 権限チェック
        $requiredPermission = $this->getRequiredPermission($interface);
        if ($requiredPermission !== null && ! $this->permissionService->check($pluginSlug, $requiredPermission)) {
            Log::warning('プラグイン機能の権限チェックに失敗', [
                'plugin' => $pluginSlug,
                'interface' => $interface,
                'permission' => $requiredPermission,
            ]);

            return CapabilityResolutionResult::permissionDenied($pluginSlug, $requiredPermission);
        }

        return CapabilityResolutionResult::success($instance);
    }

    /**
     * インターフェースに必要な権限キーを取得
     *
     * @param  class-string<PluginCapabilityInterface>  $interface
     */
    protected function getRequiredPermission(string $interface): ?string
    {
        // 手動登録された権限マップを優先
        if (isset($this->permissionMap[$interface])) {
            return $this->permissionMap[$interface];
        }

        // インターフェースの REQUIRED_PERMISSION 定数を確認
        if (defined("{$interface}::REQUIRED_PERMISSION")) {
            return constant("{$interface}::REQUIRED_PERMISSION");
        }

        return null;
    }

    /**
     * 同一プラグインの重複インスタンスかどうか確認
     *
     * @param  array<PluginCapabilityInterface>  $existing
     */
    protected function isDuplicate(array $existing, PluginCapabilityInterface $candidate): bool
    {
        foreach ($existing as $instance) {
            if ($instance->getPluginSlug() === $candidate->getPluginSlug()
                && get_class($instance) === get_class($candidate)) {
                return true;
            }
        }

        return false;
    }
}
