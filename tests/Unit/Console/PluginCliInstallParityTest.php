<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

namespace Tests\Unit\Console;

use Tests\TestCase;

/**
 * Sandbox verification of the DixlaseCookie update/rollback flow found
 * that a plugin installed through the CLI chain —
 * `dls:plugin:download <slug> --extract` then `dls:plugin:install <Dir>`
 * — came out non-functional in two ways the admin add page avoids:
 *
 *   1. composer.local.json never learned the plugin's PSR-4, so its
 *      controllers did not resolve (routes 500) and the class_exists()
 *      seeder check silently skipped its DatabaseSeeder;
 *   2. nothing recorded which extension source served the ZIP, so the
 *      plugins.source_id column stayed NULL and dls:plugin:update
 *      refused to run ("has no linked source").
 *
 * The fixes are ordering-sensitive, and reproducing the real
 * download → extract → install chain in PHPUnit would need a live
 * source plus a plugin under the real plugins/ directory, which tests
 * must not touch. So, like VendorSwapAutoloadResyncTest, this pins the
 * textual invariants: the calls exist and sit where they must.
 */
class PluginCliInstallParityTest extends TestCase
{
    public function test_plugin_install_syncs_autoload_after_the_row_insert_and_before_the_seeder_check(): void
    {
        $source = $this->source('Console/Commands/PluginInstall.php');

        $insertPos = strpos($source, "DB::table('plugins')->updateOrInsert(");
        $syncPos = strpos($source, 'ComposerLocalHelper::syncAutoload()');
        $seederPos = strpos($source, 'class_exists($seederClass)');

        $this->assertNotFalse($insertPos);
        $this->assertNotFalse($seederPos);
        $this->assertNotFalse(
            $syncPos,
            'dls:plugin:install must call ComposerLocalHelper::syncAutoload(). The CLI '
            .'chain never passes through the admin download step that registers the '
            ."plugin's PSR-4, so without it the plugin's classes do not resolve."
        );
        $this->assertGreaterThan($insertPos, $syncPos, 'syncAutoload() must run after the plugins row is written.');
        $this->assertLessThan(
            $seederPos,
            $syncPos,
            'syncAutoload() must run before the class_exists() seeder check, or the '
            ."plugin's DatabaseSeeder is silently skipped on a CLI install."
        );
    }

    public function test_plugin_install_keeps_git_exclusions_in_step_like_the_admin_path(): void
    {
        $source = $this->source('Console/Commands/PluginInstall.php');

        $this->assertStringContainsString('GitExcludeHelper::addPluginExclusion($pluginName)', $source);
        $this->assertStringContainsString('GitIgnoreHelper::addPluginExclusion($pluginName)', $source);
        $this->assertStringNotContainsString(
            'is already done during plugin creation (make:plugin), so not needed here',
            $source,
            'The stale comment claiming autoload sync is unnecessary must not come back.'
        );
    }

    public function test_plugin_install_resolves_the_source_linkage_from_option_sidecar_and_official_default(): void
    {
        $source = $this->source('Console/Commands/PluginInstall.php');

        $this->assertStringContainsString('{--source=', $source, 'dls:plugin:install needs a --source=<id> escape hatch for disk-only installs.');
        $this->assertStringContainsString('->resolveInstallLinkage(', $source);
        $this->assertStringContainsString('$sidecar->read($pluginPath)', $source);

        $readPos = strpos($source, '$sidecar->read($pluginPath)');
        $insertPos = strpos($source, "DB::table('plugins')->updateOrInsert(");
        $deletePos = strpos($source, '$sidecar->delete($pluginPath)');

        $this->assertLessThan($insertPos, $readPos, 'The sidecar must be read before the row is written so its linkage lands in the insert.');
        $this->assertGreaterThan($insertPos, $deletePos, 'The sidecar must only be removed once the row is written, so a failed install can be retried.');
    }

    public function test_plugin_download_records_the_serving_source_after_extract(): void
    {
        $source = $this->source('Console/Commands/PluginDownload.php');

        $this->assertStringContainsString('->downloadWithSource(', $source, 'dls:plugin:download must keep the source that served the ZIP.');
        $this->assertStringNotContainsString('$manager->download(', $source);

        $extractPos = strpos($source, '$this->extractPlugin(');
        $writePos = strpos($source, '$sidecar->write(');

        $this->assertNotFalse($writePos, 'dls:plugin:download --extract must write the source sidecar for dls:plugin:install.');
        $this->assertGreaterThan($extractPos, $writePos, 'The sidecar is written into the extracted directory, so it must follow the extract.');
    }

    public function test_theme_install_mirrors_the_plugin_linkage_resolution(): void
    {
        $source = $this->source('Console/Commands/ThemeInstall.php');

        $this->assertStringContainsString('{--source=', $source);
        $this->assertStringContainsString('->resolveInstallLinkage(', $source);
        $this->assertStringContainsString('$sidecar->read($themeDir)', $source);
        $this->assertStringContainsString('ComposerLocalHelper::syncAutoload()', $source);
    }

    public function test_admin_controllers_share_the_sidecar_implementation(): void
    {
        foreach (['AdminPluginsSettingsController', 'AdminThemesSettingsController'] as $controller) {
            $source = $this->source("Http/Controllers/Admin/Settings/{$controller}.php");

            $this->assertStringContainsString('ExtensionSourceSidecar', $source, "{$controller} must use the shared sidecar service.");
            $this->assertStringNotContainsString('SOURCE_SIDECAR_FILENAME', $source, "{$controller} must not carry its own sidecar constant.");
            $this->assertStringNotContainsString('function writeSourceSidecar', $source);
            $this->assertStringNotContainsString('function consumeSourceSidecar', $source);
        }
    }

    private function source(string $relative): string
    {
        return (string) file_get_contents(app_path($relative));
    }
}
