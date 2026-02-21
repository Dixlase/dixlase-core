<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
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

namespace App\DTO\Plugin;

use App\Contracts\Plugin\PluginCapabilityInterface;
use JsonSerializable;

/**
 * @api プラグイン/テーマから使用可能な安定APIです
 *
 * 機能解決結果のDTO
 *
 * PluginServiceResolver による機能解決の結果を保持します。
 * 解決成功時はインスタンスを、失敗時は理由を含みます。
 */
final readonly class CapabilityResolutionResult implements JsonSerializable
{
    /**
     * @param  bool  $resolved  解決に成功したか
     * @param  PluginCapabilityInterface|null  $instance  解決されたインスタンス
     * @param  string|null  $pluginSlug  対象プラグインのスラッグ
     * @param  string|null  $failureReason  失敗理由
     * @param  string|null  $deniedPermission  拒否された権限キー
     */
    public function __construct(
        public bool $resolved,
        public ?PluginCapabilityInterface $instance = null,
        public ?string $pluginSlug = null,
        public ?string $failureReason = null,
        public ?string $deniedPermission = null,
    ) {}

    /**
     * 解決成功の結果を生成
     */
    public static function success(PluginCapabilityInterface $instance): self
    {
        return new self(
            resolved: true,
            instance: $instance,
            pluginSlug: $instance->getPluginSlug(),
        );
    }

    /**
     * 権限不足による失敗結果を生成
     */
    public static function permissionDenied(string $pluginSlug, string $permission): self
    {
        return new self(
            resolved: false,
            pluginSlug: $pluginSlug,
            failureReason: 'permission_denied',
            deniedPermission: $permission,
        );
    }

    /**
     * 機能が利用不可による失敗結果を生成
     */
    public static function unavailable(string $pluginSlug): self
    {
        return new self(
            resolved: false,
            pluginSlug: $pluginSlug,
            failureReason: 'capability_unavailable',
        );
    }

    /**
     * 実装が見つからない失敗結果を生成
     */
    public static function notFound(?string $pluginSlug = null): self
    {
        return new self(
            resolved: false,
            pluginSlug: $pluginSlug,
            failureReason: 'not_found',
        );
    }

    /**
     * 解決に成功したか
     */
    public function isResolved(): bool
    {
        return $this->resolved;
    }

    /**
     * 権限不足で失敗したか
     */
    public function isPermissionDenied(): bool
    {
        return $this->failureReason === 'permission_denied';
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'resolved' => $this->resolved,
            'plugin_slug' => $this->pluginSlug,
            'failure_reason' => $this->failureReason,
            'denied_permission' => $this->deniedPermission,
        ];
    }
}
