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
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
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

namespace App\DTO\PluginIntegration;

use JsonSerializable;

/**
 * CAPTCHA フォーム定義 DTO
 *
 * プラグインが CAPTCHA 検証を有効化したいフォームを宣言するための不変データオブジェクト。
 * CaptchaFormProviderInterface 経由でコアの CaptchaService に渡され、
 * 管理画面の CAPTCHA 設定 UI とデータベース上の有効状態管理に使用される。
 *
 * key にはプラグイン側で一意な識別子（例: 'inquiry_contact', 'user_login'）を渡す。
 * 最終的なフォームキーは「{plugin_slug}.{key}」の形式で集約される。
 */
final readonly class CaptchaFormDTO implements JsonSerializable
{
    /**
     * @param  string  $key  プラグイン内で一意なフォーム識別子（例: 'inquiry_contact'）
     * @param  string  $name  表示名の翻訳キー（例: 'dixlase-inquiry::captcha.forms.inquiry_contact'）
     * @param  string  $route  フォーム送信先ルート名（例: 'inquiry.send'）
     * @param  string  $category  UI グルーピング用カテゴリ（例: 'contact', 'users'）
     * @param  bool  $defaultEnabled  初回登録時のデフォルト有効状態
     * @param  int  $priority  表示順序（小さいほど上位）
     */
    public function __construct(
        public string $key,
        public string $name,
        public string $route,
        public string $category,
        public bool $defaultEnabled = false,
        public int $priority = 1000,
    ) {}

    /**
     * JSON 形式にシリアライズ
     *
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'key' => $this->key,
            'name' => $this->name,
            'route' => $this->route,
            'category' => $this->category,
            'default_enabled' => $this->defaultEnabled,
            'priority' => $this->priority,
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
     * 配列から DTO を生成
     *
     * @param  array<string,mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            key: (string) ($data['key'] ?? ''),
            name: (string) ($data['name'] ?? ''),
            route: (string) ($data['route'] ?? ''),
            category: (string) ($data['category'] ?? 'general'),
            defaultEnabled: (bool) ($data['default_enabled'] ?? false),
            priority: (int) ($data['priority'] ?? 1000),
        );
    }
}
