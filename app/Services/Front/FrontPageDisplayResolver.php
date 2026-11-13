<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
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

namespace App\Services\Front;

use App\Contracts\Front\FrontPageProviderInterface;
use App\Contracts\Repositories\SiteSettingRepositoryInterface;
use App\Services\Plugin\PluginServiceResolver;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Decides what the site front page (`/`) displays, from the per-site
 * `front_page_display` setting:
 *
 * - `theme` (default) — the theme-based front page (core renders it).
 * - `forbidden` — a 403; the front page is hidden.
 * - a provider slug — the matching registered FrontPageProviderInterface
 *   renders it, aggregated through the capability tag.
 *
 * While the setting is `theme`/unset, or names a provider that is not
 * registered or not available, core renders the theme front page as before.
 *
 * @internal
 */
class FrontPageDisplayResolver
{
    /** Per-site setting key naming what the front page displays. */
    public const SETTING_KEY = 'front_page_display';

    /** Built-in setting value: render the theme-based front page (the default). */
    public const MODE_THEME = 'theme';

    /** Built-in setting value: hide the front page with a 403. */
    public const MODE_FORBIDDEN = 'forbidden';

    public function __construct(private readonly PluginServiceResolver $resolver) {}

    /**
     * The response to serve for the front page, or null to render the default
     * theme-based front page.
     *
     * Aborts with 403 when the setting is `forbidden`.
     */
    public function resolveResponse(Request $request): ?Response
    {
        $mode = (string) app(SiteSettingRepositoryInterface::class)
            ->get(self::SETTING_KEY, self::MODE_THEME);

        if ($mode === '' || $mode === self::MODE_THEME) {
            return null;
        }

        if ($mode === self::MODE_FORBIDDEN) {
            abort(403);
        }

        // A provider slug: serve it when that provider is registered and
        // available; otherwise fall back to the theme front page.
        return $this->provider($mode)?->renderFrontPage($request);
    }

    /**
     * Available provider options for an operator chooser: provider slug => its
     * label, for every registered, available provider. The caller adds the
     * built-in `theme` and `forbidden` options.
     *
     * @return array<string, string>
     */
    public function availableProviders(): array
    {
        $options = [];

        foreach ($this->providers() as $provider) {
            $options[$provider->getPluginSlug()] = $provider->getFrontPageLabel();
        }

        return $options;
    }

    /**
     * The registered, available provider with this slug, or null.
     */
    private function provider(string $slug): ?FrontPageProviderInterface
    {
        foreach ($this->providers() as $provider) {
            if ($provider->getPluginSlug() === $slug) {
                return $provider;
            }
        }

        return null;
    }

    /**
     * Every registered, available FrontPageProviderInterface.
     *
     * @return list<FrontPageProviderInterface>
     */
    private function providers(): array
    {
        $providers = [];

        foreach ($this->resolver->resolveAll(FrontPageProviderInterface::class) as $result) {
            if (! $result->isResolved()) {
                continue;
            }

            $provider = $result->instance;
            if ($provider instanceof FrontPageProviderInterface) {
                $providers[] = $provider;
            }
        }

        return $providers;
    }
}
