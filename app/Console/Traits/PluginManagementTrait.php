<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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

namespace App\Console\Traits;

use App\Models\Plugin;
use Exception;
use Illuminate\Support\Facades\DB;

/**
 * Additional logic for creating custom validators (or custom validation rules)
 * -> Use MakeFileTrait to share file generation logic
 */
trait PluginManagementTrait
{
    /**
     * Determine if the current environment is production
     */
    protected function isProduction(): bool
    {
        return app()->environment('production');
    }

    /**
     * Confirm execution in production environment
     *
     * @param  string  $action  Description text (e.g., "migrate")
     */
    protected function confirmProduction(string $action): bool
    {
        if ($this->isProduction()) {
            return $this->confirm("You are running this command in production. Do you wish to continue with {$action}?");
        }

        return true;
    }

    /**
     * Check if plugin directory exists
     *
     * @param  string  $plugin  Plugin name
     */
    protected function pluginExists(string $plugin): bool
    {
        return is_dir(base_path("plugins/{$plugin}"));
    }

    /**
     * Get plugin data
     *
     * @return object|null
     */
    protected function getPluginData(string $pluginName)
    {
        return DB::table('plugins')->where('name', $pluginName)->first();
    }

    /**
     * Activate plugin
     *
     * Enabled state lives in the `plugins.enabled_at` timestamp (non-null
     * = enabled). The update goes through the Plugin model so its saved()
     * hook keeps `site_plugin_activations` in sync, matching the
     * dls:plugin:enable command.
     */
    protected function enablePlugin(string $pluginName): void
    {
        $plugin = Plugin::where('name', $pluginName)->first();
        if ($plugin !== null) {
            $plugin->update(['enabled_at' => now()]);
        }
        $this->info(__('console/traits/plugin_management_trait.plugin_enabled', ['pluginName' => $pluginName]));
    }

    /**
     * Deactivate plugin
     *
     * Clears `plugins.enabled_at`. The update goes through the Plugin
     * model so its saved() hook keeps `site_plugin_activations` in sync,
     * matching the dls:plugin:disable command.
     */
    protected function disablePlugin(string $pluginName): void
    {
        $plugin = Plugin::where('name', $pluginName)->first();
        if ($plugin !== null) {
            $plugin->update(['enabled_at' => null]);
        }
        $this->info(__('console/traits/plugin_management_trait.plugin_disabled', ['pluginName' => $pluginName]));
    }

    /**
     * Check if migration directory exists
     *
     * @param  string  $plugin  Plugin name
     */
    protected function migrationPathExists(string $plugin): bool
    {
        return is_dir(base_path("plugins/{$plugin}/database/migrations"));
    }

    /**
     * Validate and process options
     *
     * @param  array  $options  Options to process
     * @return array Processed options
     */
    protected function processOptions(array $options): array
    {
        // Set default value for 'step' option
        $options['step'] = $options['step'] ?? null;

        // Process other options as needed
        $options['pretend'] = filter_var($options['pretend'] ?? false, FILTER_VALIDATE_BOOLEAN);

        return $options;
    }

    /**
     * Wrapper method to safely execute migration operations
     *
     * @param  callable  $operation  Migration operation to execute
     */
    protected function executeOperation(callable $operation): bool
    {
        try {
            $operation();

            return true;
        } catch (Exception $e) {
            $this->error($e->getMessage());

            return false;
        }
    }

    /**
     * Update autoload
     *
     * Note: This method is deprecated
     * Use ComposerLocalHelper::syncAutoload() to update composer.local.json
     *
     * @deprecated
     */
    protected function updateAutoload(): void
    {
        $this->warn(__('console/traits/plugin_management_trait.update_autoload_deprecated_use_composer_local'));
    }
}
