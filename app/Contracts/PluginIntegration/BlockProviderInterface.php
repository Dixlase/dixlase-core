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

namespace App\Contracts\PluginIntegration;

use App\Contracts\Plugin\PluginCapabilityInterface;
use App\DTO\PluginIntegration\BlockContext;
use App\DTO\PluginIntegration\BlockDescriptor;
use Illuminate\Contracts\Support\Renderable;

/**
 * Contract for plugins/themes that provide reusable Block components
 *
 * A Block is a self-contained renderable unit (e.g. category list, recent posts,
 * embed, image, heading) that may be placed by the user into:
 *   - the GUI editor (post body / page content)
 *   - a theme widget area (sidebar, footer, etc.)
 *
 * Both surfaces share this contract so that a plugin only implements a Block
 * once and the user-facing systems compose it via their own adapters.
 *
 * Plugins register implementations by tagging them in the service container
 * (see PluginServiceResolver) — typically inside the plugin ServiceProvider:
 *
 *   $this->app->tag([CategoryListBlock::class], 'plugin.blocks');
 *
 * Implementation status: this Contract is the foundation reserved at v0.1.0.
 * The block registry, `<x-block>` renderer, `<x-widget-area>` component,
 * and admin UI are deferred to a later release. The Contract is part of the
 * Plugin API stability pledge from v0.1.0 onwards.
 */
interface BlockProviderInterface extends PluginCapabilityInterface
{
    /**
     * Surface identifier for the GUI editor (post body / page content).
     */
    public const SURFACE_EDITOR = 'editor';

    /**
     * Surface identifier for theme widget areas (sidebar, footer, etc.).
     */
    public const SURFACE_WIDGET_AREA = 'widget_area';

    /**
     * Surface identifier for admin preview rendering.
     */
    public const SURFACE_PREVIEW = 'preview';

    /**
     * Get the unique block identifier.
     *
     * Convention: '<plugin-slug>.<block-name>' (e.g. 'dixlase-blog.category_list').
     */
    public function key(): string;

    /**
     * Get a structured descriptor for this block.
     *
     * Used by registries and admin pickers to list available blocks
     * without invoking render().
     */
    public function descriptor(): BlockDescriptor;

    /**
     * Render the block for a given config and context.
     *
     * Implementations should:
     *  - validate $config against descriptor()->configSchema before rendering
     *  - never trust raw user input; escape output appropriately
     *  - honour $context->surface to differentiate front-end vs preview output
     *
     * @param  array<string, mixed>  $config  Block instance configuration
     * @param  BlockContext  $context  Render context (surface, area, site)
     * @return Renderable|string Rendered block (HTML string or Blade view)
     */
    public function render(array $config, BlockContext $context): Renderable|string;
}
