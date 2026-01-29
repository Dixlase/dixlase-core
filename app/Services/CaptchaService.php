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

namespace App\Services;

use App\Models\CaptchaEnabledForm;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class CaptchaService
{
    /**
     * Get all form definitions from core and plugins.
     *
     * @return Collection
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
     * Get form definitions from all plugins.
     *
     * @return array
     */
    protected function getPluginForms(): array
    {
        $pluginForms = [];
        $pluginsPath = base_path('plugins');
        
        if (!File::exists($pluginsPath)) {
            return $pluginForms;
        }
        
        $pluginDirs = File::directories($pluginsPath);
        
        foreach ($pluginDirs as $pluginDir) {
            $pluginName = basename($pluginDir);
            $captchaConfigPath = $pluginDir . '/config/captcha.php';
            
            if (File::exists($captchaConfigPath)) {
                $config = include $captchaConfigPath;
                
                if (isset($config['forms']) && is_array($config['forms'])) {
                    foreach ($config['forms'] as $key => $form) {
                        // Add plugin prefix to form key to avoid conflicts
                        $pluginForms[$pluginName . '.' . $key] = array_merge($form, [
                            'plugin' => $pluginName,
                        ]);
                    }
                }
            }
        }
        
        return $pluginForms;
    }

    /**
     * Get forms grouped by category.
     *
     * @return Collection
     */
    public function getFormsByCategory(): Collection
    {
        return $this->getAllForms()->groupBy('category');
    }

    /**
     * Check if CAPTCHA is enabled for a specific form.
     *
     * @param string $formKey
     * @return bool
     */
    public function isEnabled(string $formKey): bool
    {
        // Check if form is in enabled forms table
        $enabledForm = CaptchaEnabledForm::where('form_key', $formKey)->first();
        
        if ($enabledForm) {
            return $enabledForm->enabled;
        }
        
        // If not in database, check default_enabled from config
        return $this->getDefaultEnabled($formKey);
    }

    /**
     * Get default enabled state from config.
     *
     * @param string $formKey
     * @return bool
     */
    protected function getDefaultEnabled(string $formKey): bool
    {
        $allForms = $this->getAllForms();
        
        if ($allForms->has($formKey)) {
            return $allForms[$formKey]['default_enabled'] ?? false;
        }
        
        return false;
    }

    /**
     * Update CAPTCHA setting for a form.
     *
     * @param string $formKey
     * @param bool $enabled
     * @param string|null $provider
     * @return void
     */
    public function updateFormSetting(string $formKey, bool $enabled, ?string $provider = null): void
    {
        if ($enabled) {
            CaptchaEnabledForm::enableForm($formKey, $provider);
        } else {
            CaptchaEnabledForm::disableForm($formKey);
        }
    }

    /**
     * Bulk update form settings.
     *
     * @param array $formSettings
     * @return void
     */
    public function bulkUpdateFormSettings(array $formSettings): void
    {
        foreach ($formSettings as $formKey => $enabled) {
            $this->updateFormSetting($formKey, (bool) $enabled);
        }
    }

    /**
     * Get enabled forms with their details.
     *
     * @return Collection
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
     *
     * @param string $formKey
     * @return array|null
     */
    public function getFormDefinition(string $formKey): ?array
    {
        $allForms = $this->getAllForms();
        
        return $allForms->get($formKey);
    }

    /**
     * Check if a form key exists in definitions.
     *
     * @param string $formKey
     * @return bool
     */
    public function formExists(string $formKey): bool
    {
        return $this->getAllForms()->has($formKey);
    }
}
