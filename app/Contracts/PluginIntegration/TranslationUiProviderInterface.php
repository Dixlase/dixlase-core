<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
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

/**
 * Contract for plugins that provide a translation editor for content records
 *
 * Edit screens offer a "Translate" button through the core component
 * `<x-admin.translate-button>`. The component asks every provider of this
 * capability for the editor URL of the record being edited and renders
 * nothing when none answers, so a screen embeds it unconditionally:
 *
 * ```blade
 * <x-admin.translate-button type="my-plugin:post" :id="$post->id" :source-locale="$post->lang" />
 * ```
 *
 * The content type is `{extension-slug}:{type}`, the same key the
 * extension declares under `multilingual_content.types[].key` in its
 * `plugin.json` / `theme.json` (e.g. `dixlase-pages:page`).
 *
 * A provider registers with a tag in its ServiceProvider:
 *
 * ```php
 * $this->app->tag([MyTranslationUiProvider::class], PluginServiceResolver::CAPABILITY_TAG);
 * ```
 *
 * Reading translations stays with `App\Contracts\TranslationResolver`;
 * this contract only covers the admin affordance.
 */
interface TranslationUiProviderInterface extends PluginCapabilityInterface
{
    /**
     * Return the URL of the translation editor for one record, or null
     * when this provider does not translate the given content type.
     *
     * @param  string  $type  Content type, `{extension-slug}:{type}`
     * @param  int|string  $id  Primary key of the saved record
     */
    public function getTranslationEditUrl(string $type, int|string $id): ?string;
}
