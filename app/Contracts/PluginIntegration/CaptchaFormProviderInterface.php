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

namespace App\Contracts\PluginIntegration;

use App\Contracts\Plugin\PluginCapabilityInterface;
use App\DTO\PluginIntegration\CaptchaFormDTO;

/**
 * CAPTCHA フォームを提供するプラグイン用 Contract
 *
 * 自プラグインのフォームに CAPTCHA を適用したい場合、このインターフェースを実装し
 * ServiceProvider でサービスコンテナにタグ付き登録します。
 *
 * ```php
 * $this->app->tag([MyCaptchaFormProvider::class], PluginServiceResolver::CAPABILITY_TAG);
 * ```
 *
 * 加えて plugin.json の "capabilities" 配列に "captcha" を宣言してください。
 * コアの CaptchaService が PluginServiceResolver 経由で
 * 全プラグインの実装からフォーム定義を集約します。
 *
 * 検証・描画は引き続きコアの CaptchaHelper を使用します（プロバイダ切替・
 * フェイルオーバー・緊急バイパス等を一元管理するため）。
 */
interface CaptchaFormProviderInterface extends PluginCapabilityInterface
{
    /**
     * 自プラグインで CAPTCHA を適用するフォーム定義を返す
     *
     * 返した DTO の key は「{plugin_slug}.{key}」形式に変換され、
     * captcha_enabled_forms テーブルおよび管理画面でその完全キーが使われます。
     *
     * @return CaptchaFormDTO[]
     */
    public function getCaptchaForms(): array;
}
