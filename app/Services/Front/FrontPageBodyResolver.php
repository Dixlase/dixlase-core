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

namespace App\Services\Front;

use App\Contracts\Front\FrontPageBodyProviderInterface;
use App\DTO\Front\FrontPageBodyContext;
use App\DTO\Front\FrontPageBodyDTO;
use App\DTO\Front\FrontPageBodyResult;
use App\Services\Plugin\PluginServiceResolver;

/**
 * Aggregates the extensions that can supply the body of a theme's front page, and
 * renders the one an operator picked.
 *
 * A theme asks this service for the choices to put in its own settings, and later
 * for the body of the stored choice. Keeping both here means every theme gets the
 * same choice format and, more importantly, the same fallback: when a stored choice
 * names a provider that is gone, disabled or can no longer resolve its id, this
 * returns null and the theme renders core's front page content. A theme that wrote
 * that loop itself would get the fallback subtly different, and an uninstalled plugin
 * would blank a front page.
 *
 * Unlike FrontPageDisplayResolver, whose only caller is core's own front controller,
 * this service is called by themes — so it is part of the Plugin API rather than
 * internal.
 *
 * **The per-site `front_page_display` setting is resolved before any of this.** When
 * it names a front-page provider or `forbidden`, the theme front page never renders,
 * so a body choice does not apply. See FrontPageDisplayResolver.
 *
 * @see \App\Contracts\Front\FrontPageBodyProviderInterface
 * @see \App\Services\Front\FrontPageDisplayResolver
 */
class FrontPageBodyResolver
{
    /**
     * The value a theme stores to mean "core's front page content" — the default,
     * and what every fallback lands on. Defined here so themes agree on it.
     */
    public const BODY_CORE = 'core';

    /**
     * Separator between the provider key and the body id in a stored choice.
     */
    public const CHOICE_SEPARATOR = ':';

    public function __construct(private readonly PluginServiceResolver $resolver) {}

    /**
     * Every choice on offer, grouped by the provider that offers it, for a chooser
     * that wants option groups.
     *
     * A provider with nothing to offer right now is left out entirely rather than
     * shown as an empty group.
     *
     * @param  int  $limit  Maximum number of choices to take from each provider
     * @return array<string, array{label: string, choices: array<string, string>}>
     *                                                                             Keyed by provider key; `choices` maps a stored choice to its label
     */
    public function groupedBodies(int $limit = 100): array
    {
        $groups = [];

        foreach ($this->providers() as $provider) {
            $choices = [];

            foreach ($provider->availableFrontPageBodies($limit) as $body) {
                $choices[$this->choiceKeyFor($provider, $body)] = $body->label;
            }

            if ($choices === []) {
                continue;
            }

            $groups[$this->providerKey($provider)] = [
                'label' => $provider->getFrontPageBodyLabel(),
                'choices' => $choices,
            ];
        }

        return $groups;
    }

    /**
     * Every choice on offer as one flat list, for a chooser that wants a plain
     * select: a stored choice mapped to its label.
     *
     * @param  int  $limit  Maximum number of choices to take from each provider
     * @return array<string, string>
     */
    public function availableBodies(int $limit = 100): array
    {
        $options = [];

        foreach ($this->groupedBodies($limit) as $group) {
            foreach ($group['choices'] as $choice => $label) {
                $options[$choice] = $label;
            }
        }

        return $options;
    }

    /**
     * Render a stored choice as the front page body, or null to fall back to core's
     * front page content.
     *
     * Null covers every way a choice can fail to produce a body, so a caller has one
     * case to handle: the value is not a provider choice at all (BODY_CORE, or
     * anything malformed), the provider it names is not registered or not available,
     * or the provider declined the id.
     */
    public function render(string $choice, FrontPageBodyContext $context): ?FrontPageBodyResult
    {
        $split = self::splitChoiceKey($choice);

        if ($split === null) {
            return null;
        }

        return $this->provider($split['provider_key'])
            ?->renderFrontPageBody($split['body_id'], $context);
    }

    /**
     * Whether a stored choice names a provider that is registered and available.
     *
     * It does not ask the provider whether the id still resolves — only render() can
     * answer that, and it costs a render.
     */
    public function has(string $choice): bool
    {
        $split = self::splitChoiceKey($choice);

        return $split !== null && $this->provider($split['provider_key']) !== null;
    }

    /**
     * The registered, available provider with this key, or null.
     */
    private function provider(string $providerKey): ?FrontPageBodyProviderInterface
    {
        foreach ($this->providers() as $provider) {
            if ($this->providerKey($provider) === $providerKey) {
                return $provider;
            }
        }

        return null;
    }

    /**
     * Every registered, available FrontPageBodyProviderInterface.
     *
     * @return list<FrontPageBodyProviderInterface>
     */
    private function providers(): array
    {
        $providers = [];

        foreach ($this->resolver->resolveAll(FrontPageBodyProviderInterface::class) as $result) {
            if (! $result->isResolved()) {
                continue;
            }

            $provider = $result->instance;
            if ($provider instanceof FrontPageBodyProviderInterface) {
                $providers[] = $provider;
            }
        }

        return $providers;
    }

    /**
     * The key a provider is addressed by: its plugin slug.
     */
    private function providerKey(FrontPageBodyProviderInterface $provider): string
    {
        return $provider->getPluginSlug();
    }

    /**
     * The stored form of a choice: the provider's key and the body id in one string.
     *
     * This is the value a theme saves in its own settings, so that one string names
     * both the provider and the body. Composing it here means every theme stores it
     * the same way.
     *
     * @return string e.g. 'dixlase-blog:top'
     */
    public static function choiceKey(string $providerKey, string $bodyId): string
    {
        return $providerKey.self::CHOICE_SEPARATOR.$bodyId;
    }

    /**
     * Split a stored choice back into a provider key and a body id.
     *
     * Only the first separator is significant, so a body id may itself contain one.
     * Returns null when $choice is not in the stored form — which includes BODY_CORE
     * and anything malformed.
     *
     * @return array{provider_key: string, body_id: string}|null
     */
    public static function splitChoiceKey(string $choice): ?array
    {
        if (! str_contains($choice, self::CHOICE_SEPARATOR)) {
            return null;
        }

        [$providerKey, $bodyId] = explode(self::CHOICE_SEPARATOR, $choice, 2);

        if ($providerKey === '' || $bodyId === '') {
            return null;
        }

        return ['provider_key' => $providerKey, 'body_id' => $bodyId];
    }

    /**
     * The stored choice for one of a provider's bodies.
     *
     * The provider key is taken from the provider, not from the body, so a provider
     * cannot offer a choice that resolves to someone else.
     */
    private function choiceKeyFor(FrontPageBodyProviderInterface $provider, FrontPageBodyDTO $body): string
    {
        return self::choiceKey($this->providerKey($provider), $body->id);
    }
}
