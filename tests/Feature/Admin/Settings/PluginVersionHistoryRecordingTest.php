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

declare(strict_types=1);

namespace Tests\Feature\Admin\Settings;

use App\Http\Controllers\Admin\Settings\AdminPluginsSettingsController;
use App\Models\AuditLog;
use App\Models\Plugin;
use App\Models\PluginVersionHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Covers the supply-chain version-history recording path that runs on
 * plugin install / update inside AdminPluginsSettingsController.
 *
 * The controller's recordVersionHistory() and persistSupplyChainMetadata()
 * helpers are protected, so we invoke them via reflection to keep the test
 * focused on the supply-chain logic without dragging in ZIP extraction
 * and the full HTTP flow.
 */
class PluginVersionHistoryRecordingTest extends TestCase
{
    use RefreshDatabase;

    public function test_install_records_history_row_with_no_old_values(): void
    {
        $plugin = $this->makePlugin([
            'signing_key_id' => 'dixlase-authority-2026',
            'author_id' => 'exc-d-inc',
            'authority_key_id' => 'dixlase-authority-2026',
        ]);

        $this->invokeRecordVersionHistory(
            plugin: $plugin,
            oldVersion: null,
            oldSigningKeyId: null,
            oldAuthorId: null,
            installationMethod: PluginVersionHistory::METHOD_INSTALL,
        );

        $row = PluginVersionHistory::query()
            ->where('plugin_slug', $plugin->slug)
            ->firstOrFail();

        $this->assertNull($row->old_version);
        $this->assertSame($plugin->version, $row->new_version);
        $this->assertFalse($row->signing_key_changed);
        $this->assertFalse($row->author_id_changed);
        $this->assertSame(PluginVersionHistory::METHOD_INSTALL, $row->installation_method);
    }

    public function test_update_with_no_change_records_history_without_change_flags(): void
    {
        $plugin = $this->makePlugin([
            'signing_key_id' => 'dixlase-authority-2026',
            'author_id' => 'exc-d-inc',
        ]);

        $this->invokeRecordVersionHistory(
            plugin: $plugin,
            oldVersion: '1.0.0',
            oldSigningKeyId: 'dixlase-authority-2026',
            oldAuthorId: 'exc-d-inc',
            installationMethod: PluginVersionHistory::METHOD_UPDATE,
        );

        $row = PluginVersionHistory::query()
            ->where('plugin_slug', $plugin->slug)
            ->firstOrFail();

        $this->assertSame('1.0.0', $row->old_version);
        $this->assertFalse($row->signing_key_changed);
        $this->assertFalse($row->author_id_changed);
    }

    public function test_signing_key_change_sets_flag_and_writes_audit_event(): void
    {
        $plugin = $this->makePlugin([
            'signing_key_id' => 'dixlase-authority-2027', // new key
            'author_id' => 'exc-d-inc',
        ]);

        $this->invokeRecordVersionHistory(
            plugin: $plugin,
            oldVersion: '1.0.0',
            oldSigningKeyId: 'dixlase-authority-2026',
            oldAuthorId: 'exc-d-inc',
            installationMethod: PluginVersionHistory::METHOD_UPDATE,
        );

        $row = PluginVersionHistory::query()
            ->where('plugin_slug', $plugin->slug)
            ->firstOrFail();

        $this->assertTrue($row->signing_key_changed);
        $this->assertFalse($row->author_id_changed);
        $this->assertSame('dixlase-authority-2026', $row->old_signing_key_id);
        $this->assertSame('dixlase-authority-2027', $row->new_signing_key_id);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLog::ACTION_PLUGIN_SIGNING_KEY_CHANGED,
        ]);
    }

    public function test_author_id_change_sets_flag_and_writes_audit_event(): void
    {
        $plugin = $this->makePlugin([
            'signing_key_id' => 'dixlase-authority-2026',
            'author_id' => 'new-owner', // hijacked-style swap
        ]);

        $this->invokeRecordVersionHistory(
            plugin: $plugin,
            oldVersion: '1.0.0',
            oldSigningKeyId: 'dixlase-authority-2026',
            oldAuthorId: 'exc-d-inc',
            installationMethod: PluginVersionHistory::METHOD_UPDATE,
        );

        $row = PluginVersionHistory::query()
            ->where('plugin_slug', $plugin->slug)
            ->firstOrFail();

        $this->assertFalse($row->signing_key_changed);
        $this->assertTrue($row->author_id_changed);
        $this->assertSame('exc-d-inc', $row->old_author_id);
        $this->assertSame('new-owner', $row->new_author_id);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLog::ACTION_PLUGIN_AUTHOR_ID_CHANGED,
        ]);
    }

    public function test_first_install_with_null_old_values_does_not_falsely_trigger_change_flags(): void
    {
        $plugin = $this->makePlugin([
            'signing_key_id' => 'dixlase-authority-2026',
            'author_id' => 'exc-d-inc',
        ]);

        // Initial install: oldSigningKeyId/oldAuthorId are null. They differ
        // from the new values, but the change-flag logic must NOT treat this
        // as a hijack (a fresh install is not a key rotation).
        $this->invokeRecordVersionHistory(
            plugin: $plugin,
            oldVersion: null,
            oldSigningKeyId: null,
            oldAuthorId: null,
            installationMethod: PluginVersionHistory::METHOD_INSTALL,
        );

        $row = PluginVersionHistory::query()
            ->where('plugin_slug', $plugin->slug)
            ->firstOrFail();

        $this->assertFalse($row->signing_key_changed);
        $this->assertFalse($row->author_id_changed);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makePlugin(array $overrides = []): Plugin
    {
        return Plugin::create(array_merge([
            'name' => 'Test Plugin',
            'package_name' => 'dixlase/test-plugin',
            'directory' => 'TestPlugin',
            'namespace' => 'Plugins\\TestPlugin',
            'slug' => 'test-plugin',
            'version' => '1.1.0',
            'author' => 'Test Author',
            'installed_at' => now(),
        ], $overrides));
    }

    private function invokeRecordVersionHistory(
        Plugin $plugin,
        ?string $oldVersion,
        ?string $oldSigningKeyId,
        ?string $oldAuthorId,
        string $installationMethod,
    ): void {
        $controller = $this->app->make(AdminPluginsSettingsController::class);
        $method = new ReflectionMethod($controller, 'recordVersionHistory');
        $method->setAccessible(true);
        $method->invoke($controller, $plugin, $oldVersion, $oldSigningKeyId, $oldAuthorId, $installationMethod);
    }
}
