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
        
        // Get only installed and enabled plugins
        $enabledPlugins = \App\Models\Plugin::whereNotNull('installed_at')
            ->whereNotNull('enabled_at')
            ->pluck('slug')
            ->toArray();
        
        $pluginDirs = File::directories($pluginsPath);
        
        foreach ($pluginDirs as $pluginDir) {
            $pluginName = basename($pluginDir);
            
            // Skip if plugin is not installed or not enabled
            if (!in_array($pluginName, $enabledPlugins)) {
                continue;
            }
            
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
     * Returns true only if the form exists in the database.
     *
     * @param string $formKey
     * @return bool
     */
    public function isEnabled(string $formKey): bool
    {
        // データベースにレコードが存在すれば有効
        return CaptchaEnabledForm::where('form_key', $formKey)
            ->where('enabled', true)
            ->exists();
    }

    /**
     * Update CAPTCHA setting for a form.
     * Enabled forms are saved to database, disabled forms are deleted.
     *
     * @param string $formKey
     * @param bool $enabled
     * @param string|null $provider
     * @return void
     */
    public function updateFormSetting(string $formKey, bool $enabled, ?string $provider = null): void
    {
        \Log::info('CaptchaService::updateFormSetting 呼び出し', [
            'form_key' => $formKey,
            'enabled' => $enabled,
            'provider' => $provider,
        ]);
        
        if ($enabled) {
            // 有効な場合のみデータベースに保存
            $result = CaptchaEnabledForm::updateOrCreate(
                ['form_key' => $formKey],
                [
                    'enabled' => true,
                    'provider' => $provider,
                ]
            );
            \Log::info('CAPTCHAフォーム有効化: レコード作成/更新', [
                'form_key' => $formKey,
                'record_id' => $result->id,
                'was_recently_created' => $result->wasRecentlyCreated,
            ]);
        } else {
            // 無効な場合はレコードを削除
            $deleted = CaptchaEnabledForm::where('form_key', $formKey)->delete();
            \Log::info('CAPTCHAフォーム無効化: レコード削除', [
                'form_key' => $formKey,
                'deleted_count' => $deleted,
            ]);
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
        \Log::info('CaptchaService::bulkUpdateFormSettings 呼び出し', [
            'form_settings' => $formSettings,
            'count' => count($formSettings),
        ]);
        
        foreach ($formSettings as $formKey => $enabled) {
            \Log::info('フォーム設定処理中', [
                'form_key' => $formKey,
                'enabled_raw' => $enabled,
                'enabled_bool' => (bool) $enabled,
            ]);
            $this->updateFormSetting($formKey, (bool) $enabled);
        }
        
        \Log::info('CaptchaService::bulkUpdateFormSettings 完了');
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
