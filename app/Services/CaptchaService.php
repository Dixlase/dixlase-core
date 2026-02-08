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
        
        \Log::info('CaptchaService: プラグインフォーム読み込み開始', [
            'plugins_path' => $pluginsPath,
            'path_exists' => File::exists($pluginsPath),
        ]);
        
        if (!File::exists($pluginsPath)) {
            \Log::warning('CaptchaService: pluginsディレクトリが存在しません');
            return $pluginForms;
        }
        
        // Get only installed and enabled plugins (use directory column)
        $enabledPlugins = \App\Models\Plugin::whereNotNull('installed_at')
            ->whereNotNull('enabled_at')
            ->pluck('directory')
            ->toArray();
        
        \Log::info('CaptchaService: 有効なプラグイン', [
            'enabled_plugins' => $enabledPlugins,
            'count' => count($enabledPlugins),
        ]);
        
        $pluginDirs = File::directories($pluginsPath);
        
        foreach ($pluginDirs as $pluginDir) {
            $pluginName = basename($pluginDir);
            
            \Log::info('CaptchaService: プラグインディレクトリをチェック', [
                'plugin_name' => $pluginName,
                'is_enabled' => in_array($pluginName, $enabledPlugins),
            ]);
            
            // Skip if plugin is not installed or not enabled
            if (!in_array($pluginName, $enabledPlugins)) {
                \Log::info('CaptchaService: プラグインがインストール/有効化されていないためスキップ', [
                    'plugin_name' => $pluginName,
                ]);
                continue;
            }
            
            // plugin.jsonからslugを取得（フォールバック: ディレクトリ名）
            $pluginJsonPath = $pluginDir . '/plugin.json';
            $pluginSlug = $pluginName; // デフォルトはディレクトリ名
            
            if (File::exists($pluginJsonPath)) {
                $pluginJson = json_decode(File::get($pluginJsonPath), true);
                if (isset($pluginJson['slug'])) {
                    $pluginSlug = $pluginJson['slug'];
                    \Log::info('CaptchaService: plugin.jsonからslugを読み込み', [
                        'plugin_name' => $pluginName,
                        'slug' => $pluginSlug,
                    ]);
                }
            }
            
            $captchaConfigPath = $pluginDir . '/config/captcha.php';
            
            \Log::info('CaptchaService: CAPTCHA設定ファイルをチェック', [
                'plugin_name' => $pluginName,
                'plugin_slug' => $pluginSlug,
                'config_path' => $captchaConfigPath,
                'file_exists' => File::exists($captchaConfigPath),
            ]);
            
            if (File::exists($captchaConfigPath)) {
                $config = include $captchaConfigPath;
                
                \Log::info('CaptchaService: CAPTCHA設定ファイルを読み込み', [
                    'plugin_name' => $pluginName,
                    'plugin_slug' => $pluginSlug,
                    'has_forms' => isset($config['forms']),
                    'forms_count' => isset($config['forms']) ? count($config['forms']) : 0,
                ]);
                
                if (isset($config['forms']) && is_array($config['forms'])) {
                    foreach ($config['forms'] as $key => $form) {
                        // Use slug as prefix (fallback to directory name)
                        $formKey = $pluginSlug . '.' . $key;
                        $pluginForms[$formKey] = array_merge($form, [
                            'plugin' => $pluginName,
                            'plugin_slug' => $pluginSlug,
                        ]);
                        
                        \Log::info('CaptchaService: プラグインフォームを追加', [
                            'plugin_name' => $pluginName,
                            'plugin_slug' => $pluginSlug,
                            'original_key' => $key,
                            'form_key' => $formKey,
                            'form_name' => $form['name'] ?? 'N/A',
                        ]);
                    }
                }
            }
        }
        
        \Log::info('CaptchaService: プラグインフォーム読み込み完了', [
            'total_plugin_forms' => count($pluginForms),
            'form_keys' => array_keys($pluginForms),
        ]);
        
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
        $exists = CaptchaEnabledForm::where('form_key', $formKey)
            ->where('enabled', true)
            ->exists();
        
        \Log::info('CaptchaService::isEnabled チェック', [
            'form_key' => $formKey,
            'exists' => $exists,
            'record_count' => CaptchaEnabledForm::where('form_key', $formKey)->count(),
        ]);
        
        return $exists;
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
