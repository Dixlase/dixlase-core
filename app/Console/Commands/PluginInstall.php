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

namespace App\Console\Commands;

use App\Console\Traits\BuildsExtensionAssets;
use App\Console\Traits\PluginManagementTrait;
use App\Services\Extension\ExtensionSourceManager;
use App\Services\Licensing\LicenseCompatibilityChecker;
use App\Services\PluginMigrator;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class PluginInstall extends Command
{
    use BuildsExtensionAssets;
    use PluginManagementTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dls:plugin:install {pluginName : The name of the plugin to install} {--enable : Enable the plugin after installation} {--force : Skip the license-compatibility guard and install even when the manifest license is refused or missing} {--build : Force a front-end asset rebuild even when compiled assets already exist} {--skip-build : Skip the npm install / build step entirely}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'command.plugin_install.description';

    /**
     * Execute the console command.
     */
    public function handle(LicenseCompatibilityChecker $licenseChecker)
    {
        //
        $pluginName = $this->argument('pluginName');
        $pluginPath = base_path('plugins/'.$pluginName);
        $pluginJsonPath = $pluginPath.'/plugin.json';
        $composerPath = $pluginPath.'/composer.json';

        if (! File::exists($pluginPath)) {
            $this->error(__('admin/command/plugin.not_exists'));

            return;
        }

        // Read plugin information (priority: plugin.json → composer.json → default values)
        $version = '1.0.0';
        $description = null;
        $license = null;
        $author = null;
        $email = null;
        $web = null;
        $packageName = null;
        $slug = Str::slug(Str::headline($pluginName), '-');

        // 1. Read from plugin.json (highest priority)
        if (File::exists($pluginJsonPath)) {
            $pluginData = json_decode(File::get($pluginJsonPath), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $packageName = $pluginData['package_name'] ?? $pluginData['name'] ?? null;
                $description = $pluginData['description'] ?? null;
                if (is_array($description)) {
                    $description = $description['en'] ?? $description['ja'] ?? null;
                }
                $license = $pluginData['license'] ?? null;
                $author = $pluginData['author'] ?? null;
                $email = $pluginData['email'] ?? null;
                $web = $pluginData['url'] ?? $pluginData['homepage'] ?? $pluginData['web'] ?? null;
                $version = $pluginData['version'] ?? '1.0.0';
                $slug = $pluginData['slug'] ?? $slug;
            }
        }

        // 2. Fallback to composer.json
        if (File::exists($composerPath)) {
            $composerData = json_decode(File::get($composerPath), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error(__('admin/command/plugin-install.composer_parse_error', [
                    'error' => json_last_error_msg(),
                ]));

                return;
            }

            $packageName = $packageName ?? $composerData['name'] ?? null;
            $description = $description ?? $composerData['description'] ?? null;
            $license = $license ?? $composerData['license'] ?? null;

            if ($version === '1.0.0') {
                $version = $composerData['version'] ?? '1.0.0';
            }

            // Get author information
            if (! $author) {
                $authors = $composerData['authors'] ?? [];
                $firstAuthor = $authors[0] ?? [];
                $author = $firstAuthor['name'] ?? null;
                $email = $email ?? $firstAuthor['email'] ?? null;
                $web = $web ?? $firstAuthor['homepage'] ?? null;
            }

            $slug = $slug ?? $composerData['extra']['slug'] ?? Str::slug(Str::headline($pluginName), '-');
        }

        // License compatibility guard
        // ---------------------------
        // Evaluate the manifest's `license` field against the SPDX whitelist
        // in config/licensing.php. The guard:
        //   - fails when the field is missing, malformed, or explicitly refused
        //     (use --force to override; the failure reason is logged either way)
        //   - prints a soft warning on unknown SPDX values and continues
        //   - is silent on accepted values
        $licenseVerdict = $licenseChecker->evaluateForInstall($license);
        $force = (bool) $this->option('force');

        if ($licenseVerdict['verdict'] === LicenseCompatibilityChecker::VERDICT_FAIL) {
            $this->error(sprintf(
                "Plugin install refused: %s\n  status: %s\n  license: %s",
                $licenseVerdict['reason'] ?? 'License is not accepted by this Dixlase install.',
                $licenseVerdict['status'],
                $licenseVerdict['spdx'] ?? '(none)',
            ));
            if (! $force) {
                $this->line('  Pass --force to install anyway (the refusal reason is recorded in the audit log).');

                return 1;
            }
            $this->warn('Proceeding despite license guard refusal because --force was passed.');
        } elseif ($licenseVerdict['verdict'] === LicenseCompatibilityChecker::VERDICT_WARN) {
            $this->warn(sprintf(
                'License notice: %s',
                $licenseVerdict['reason'] ?? 'License is not in the accepted-licenses table.',
            ));
        }

        // Default an official-vendor plugin to the official source so it
        // is updatable out of the box. A plugin installed from a
        // configured source has its real source recorded afterwards via
        // the sidecar (persistSupplyChainMetadata), which overrides this
        // default; a third-party plugin is left unlinked (officialLinkage
        // returns null when the package_name is not under the official
        // vendor).
        $linkage = app(ExtensionSourceManager::class)
            ->officialLinkage($slug, 'plugin', $packageName);

        // Register in database
        DB::table('plugins')->updateOrInsert(
            ['name' => $pluginName],
            [
                'package_name' => $packageName,
                'namespace' => "Plugins\\$pluginName",
                'directory' => $pluginName,
                'slug' => $slug,
                'description' => $description,
                'license' => $license,
                'author' => $author,
                'email' => $email,
                'url' => $web,
                'version' => $version, // Get from composer.json
                'source_id' => $linkage['source_id'] ?? null,
                'source_repo' => $linkage['source_repo'] ?? null,
                'installed_from_url' => $linkage['installed_from_url'] ?? null,
                'installation_method' => $linkage['installation_method'] ?? null,
                'installed_at' => now(),
            ]
        );

        $this->info(__('admin/command/plugin-install.installed', [
            'pluginName' => $pluginName,
        ]));

        // Run migrations
        $this->info(__('admin/command/plugin-install.migrating'));
        $migrator = new PluginMigrator(app(Filesystem::class), app(ConnectionResolverInterface::class), 'plugin_migrations', $slug);
        $migrator->migrate($pluginName);

        // Run seeder (only if DatabaseSeeder exists)
        $seederClass = "Plugins\\{$pluginName}\\Database\\Seeders\\DatabaseSeeder";
        if (class_exists($seederClass)) {
            $this->info(__('admin/command/plugin-install.seeding'));
            $this->call('dls:plugin:seed', [
                'plugin' => $pluginName,
                '--force' => true,
            ]);
        }

        // Note: Updating composer.local.json and .git/info/exclude
        // is already done during plugin creation (make:plugin), so not needed here

        // Build front-end assets if the plugin ships its own npm pipeline.
        // No-op when the plugin has no package.json (most plugins) or when
        // assets are already compiled (re-install / unchanged sources).
        $this->buildExtensionAssets($pluginPath, $this->resolveAssetBuildMode());

        // Backfill any settings defaults the plugin declares via
        // ProvidesSettingsDefaultsInterface. On a fresh install this is
        // typically a no-op (the plugin's own DatabaseSeeder above already
        // filled the table) but the contract makes install / update
        // symmetric, and covers re-installs / --force where the seeder
        // was skipped or the table was hand-modified. Idempotent by design.
        $sync = app(\App\Services\Extension\ExtensionSettingsDefaultsSync::class)
            ->syncForExtension($pluginPath, 'plugin');
        if ($sync['synced_keys'] !== []) {
            $this->line(sprintf(
                'Settings defaults synced into %s: %d key(s).',
                $sync['table'],
                count($sync['synced_keys']),
            ));
        }

        // Discard any Blade views compiled against a prior version of this
        // plugin (relevant for re-install / --force re-install where the
        // .blade.php files were replaced). Update / rollback paths clear the
        // view cache too — this keeps install consistent with them.
        Artisan::call('view:clear');

        // Confirm plugin activation (only if --enable option is not specified)
        // When running via web, interactive input is not possible, so judge only by presence of --enable option
        if ($this->option('enable')) {
            $this->call('dls:plugin:enable', [
                'pluginName' => $pluginName,
            ]);
        } elseif (app()->runningInConsole() && ! app()->runningUnitTests()) {
            // Show confirmation prompt only when running from CLI
            if ($this->confirm(__('admin/command/plugin-install.enable_confirm', [
                'pluginName' => $pluginName,
            ]), false)) {
                $this->call('dls:plugin:enable', [
                    'pluginName' => $pluginName,
                ]);
            } else {
                $this->info(__('admin/command/plugin-install.enable_skipped', [
                    'pluginName' => $pluginName,
                ]));
            }
        } else {
            $this->info(__('admin/command/plugin-install.enable_skipped', [
                'pluginName' => $pluginName,
            ]));
        }
    }
}
