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

namespace App\Services;

use App\Contracts\PluginIntegration\CaptchaFormProviderInterface;
use App\Models\CaptchaEnabledForm;
use App\Services\Plugin\PluginServiceResolver;
use Illuminate\Support\Collection;

/**
 * @internal For Core use only. Do not reference from plugins/themes
 */
class CaptchaService
{
    public function __construct(
        protected PluginServiceResolver $pluginServiceResolver,
    ) {}

    /**
     * Get all form definitions from core and plugins.
     */
    public function getAllForms(): Collection
    {
        $forms = collect(config('captcha.forms', []));

        // Get plugin forms
        $pluginForms = $this->getPluginForms();
        $forms = $forms->merge($pluginForms);

        return $forms->sortBy('priority');
    }

    /**
     * Get form definitions from all plugins via the "captcha" capability.
     *
     * Plugins implement CaptchaFormProviderInterface and tag it under
     * `plugin.capabilities`. PluginServiceResolver checks plugin permissions
     * and skips disabled or untrusted providers.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function getPluginForms(): array
    {
        $pluginForms = [];

        $results = $this->pluginServiceResolver->resolveAll(CaptchaFormProviderInterface::class);

        foreach ($results as $result) {
            if (! $result->isResolved()) {
                continue;
            }

            $provider = $result->instance;
            if (! $provider instanceof CaptchaFormProviderInterface) {
                continue;
            }

            $pluginSlug = $provider->getPluginSlug();

            foreach ($provider->getCaptchaForms() as $form) {
                $formKey = $pluginSlug.'.'.$form->key;
                $pluginForms[$formKey] = array_merge($form->toArray(), [
                    'plugin' => $pluginSlug,
                    'plugin_slug' => $pluginSlug,
                ]);
            }
        }

        return $pluginForms;
    }

    /**
     * Get forms grouped by category.
     */
    public function getFormsByCategory(): Collection
    {
        return $this->getAllForms()->groupBy('category');
    }

    /**
     * Check if CAPTCHA is enabled for a specific form.
     * Returns true only if the form exists in the database.
     */
    public function isEnabled(string $formKey): bool
    {
        // Valid if a record exists in the database
        return CaptchaEnabledForm::where('form_key', $formKey)
            ->where('enabled', true)
            ->exists();
    }

    /**
     * Update CAPTCHA setting for a form.
     * Enabled forms are saved to database, disabled forms are deleted.
     */
    public function updateFormSetting(string $formKey, bool $enabled, ?string $provider = null): void
    {
        if ($enabled) {
            // Save to database only when valid
            CaptchaEnabledForm::updateOrCreate(
                ['form_key' => $formKey],
                [
                    'enabled' => true,
                    'provider' => $provider,
                ]
            );
        } else {
            // Delete the record if invalid
            CaptchaEnabledForm::where('form_key', $formKey)->delete();
        }
    }

    /**
     * Bulk update form settings.
     */
    public function bulkUpdateFormSettings(array $formSettings): void
    {
        foreach ($formSettings as $formKey => $enabled) {
            $this->updateFormSetting($formKey, (bool) $enabled);
        }
    }

    /**
     * Get enabled forms with their details.
     */
    public function getEnabledForms(): Collection
    {
        $enabledKeys = CaptchaEnabledForm::getEnabledFormKeys();
        $allForms = $this->getAllForms();

        return collect($enabledKeys)->map(function ($key) use ($allForms) {
            return $allForms->get($key);
        })->filter();
    }

    /**
     * Get form definition by key.
     */
    public function getFormDefinition(string $formKey): ?array
    {
        $allForms = $this->getAllForms();

        return $allForms->get($formKey);
    }

    /**
     * Check if a form key exists in definitions.
     */
    public function formExists(string $formKey): bool
    {
        return $this->getAllForms()->has($formKey);
    }
}
