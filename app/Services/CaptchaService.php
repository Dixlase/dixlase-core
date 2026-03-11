<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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

/**
 * @internal コア専用。プラグイン/テーマから参照しないこと
 */
class CaptchaService
{
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
     * Get form definitions from all plugins.
     */
    protected function getPluginForms(): array
    {
        $pluginForms = [];
        $pluginsPath = base_path('plugins');

        if (! File::exists($pluginsPath)) {
            return $pluginForms;
        }

        // Get only installed and enabled plugins (use directory column)
        $enabledPlugins = \App\Models\Plugin::whereNotNull('installed_at')
            ->whereNotNull('enabled_at')
            ->pluck('directory')
            ->toArray();

        $pluginDirs = File::directories($pluginsPath);

        foreach ($pluginDirs as $pluginDir) {
            $pluginName = basename($pluginDir);

            // Skip if plugin is not installed or not enabled
            if (! in_array($pluginName, $enabledPlugins)) {
                continue;
            }

            // plugin.jsonからslugを取得（フォールバック: ディレクトリ名）
            $pluginJsonPath = $pluginDir.'/plugin.json';
            $pluginSlug = $pluginName; // デフォルトはディレクトリ名

            if (File::exists($pluginJsonPath)) {
                $pluginJson = json_decode(File::get($pluginJsonPath), true);
                if (isset($pluginJson['slug'])) {
                    $pluginSlug = $pluginJson['slug'];
                }
            }

            $captchaConfigPath = $pluginDir.'/config/captcha.php';

            if (File::exists($captchaConfigPath)) {
                $config = include $captchaConfigPath;

                if (isset($config['forms']) && is_array($config['forms'])) {
                    foreach ($config['forms'] as $key => $form) {
                        // Use slug as prefix (fallback to directory name)
                        $formKey = $pluginSlug.'.'.$key;
                        $pluginForms[$formKey] = array_merge($form, [
                            'plugin' => $pluginName,
                            'plugin_slug' => $pluginSlug,
                        ]);
                    }
                }
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
        // データベースにレコードが存在すれば有効
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
            // 有効な場合のみデータベースに保存
            CaptchaEnabledForm::updateOrCreate(
                ['form_key' => $formKey],
                [
                    'enabled' => true,
                    'provider' => $provider,
                ]
            );
        } else {
            // 無効な場合はレコードを削除
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
