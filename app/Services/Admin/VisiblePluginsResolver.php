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

namespace App\Services\Admin;

use App\Contracts\PluginIntegration\VisiblePluginsProviderInterface;
use App\Services\Plugin\PluginServiceResolver;

/**
 * Aggregates the plugin-slug allowlist that restricts the plugins admin screen.
 *
 * Extensions contribute an allowlist through VisiblePluginsProviderInterface
 * (tagged on the capability tag); the slugs from every registered provider are
 * merged. While no provider is registered, every plugin is shown. A null result
 * means no restriction.
 *
 * @internal
 */
class VisiblePluginsResolver
{
    private bool $resolved = false;

    /** @var list<string>|null */
    private ?array $allowed = null;

    public function __construct(private readonly PluginServiceResolver $resolver) {}

    /**
     * The effective allowlist of plugin slugs, or null for no restriction.
     *
     * @return list<string>|null
     */
    public function allowedSlugs(): ?array
    {
        if ($this->resolved) {
            return $this->allowed;
        }

        $this->resolved = true;

        $slugs = [];
        $anyProvider = false;

        foreach ($this->resolver->resolveAll(VisiblePluginsProviderInterface::class) as $result) {
            if (! $result->isResolved()) {
                continue;
            }

            $provider = $result->instance;
            if (! $provider instanceof VisiblePluginsProviderInterface) {
                continue;
            }

            $list = $provider->getVisiblePluginSlugs();
            if ($list === null) {
                // The provider imposes no restriction of its own.
                continue;
            }

            $anyProvider = true;
            foreach ($list as $slug) {
                $slugs[] = $slug;
            }
        }

        if (! $anyProvider) {
            return $this->allowed = null;
        }

        return $this->allowed = array_values(array_unique($slugs));
    }

    /**
     * Drop the memoised answer. Call this after changing providers within the
     * same request.
     */
    public function forget(): void
    {
        $this->resolved = false;
        $this->allowed = null;
    }
}
