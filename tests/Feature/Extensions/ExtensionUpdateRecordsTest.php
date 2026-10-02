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

namespace Tests\Feature\Extensions;

use App\Console\Commands\PluginRollback;
use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Models\AuditLog;
use App\Models\Member;
use App\Models\Plugin;
use App\Models\PluginVersionHistory;
use App\Models\Theme;
use App\Models\ThemeVersionHistory;
use App\Services\Extension\ExtensionUpdateRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Extension updates and rollbacks leave an audit entry and a version-history
 * row, from whichever entry point ran them (dixlase-core#454).
 *
 * The extensions used here have no directory on disk, so nothing reads or
 * writes the real plugins/ or themes/.
 */
class ExtensionUpdateRecordsTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_update_records_history_and_audit(): void
    {
        $plugin = $this->plugin('0.1.0');
        $member = $this->member();
        $before = ExtensionUpdateRecorder::snapshot($plugin);
        $plugin->update(['version' => '0.1.1']);

        ExtensionUpdateRecorder::succeeded('update', $plugin, $before, $member->id, ['downloaded_sha256' => 'abc']);

        $row = PluginVersionHistory::where('plugin_slug', 'records-test')->sole();
        $this->assertSame(
            ['0.1.0', '0.1.1', PluginVersionHistory::METHOD_UPDATE, $member->id],
            [$row->old_version, $row->new_version, $row->installation_method, $row->applied_by_id],
        );

        $audit = AuditLog::where('action', AuditLog::ACTION_PLUGIN_UPDATED)->sole();
        $this->assertSame(AuditLog::OUTCOME_SUCCESS, $audit->outcome);
        $this->assertSame($member->id, (int) $audit->actor_id);
        $this->assertSame('abc', $audit->context['downloaded_sha256'] ?? null);
        $this->assertSame(['0.1.0', '0.1.1'], [$audit->context['from'] ?? null, $audit->context['to'] ?? null]);
    }

    public function test_a_rollback_records_history_and_audit(): void
    {
        $theme = Theme::create(['name' => 'Records', 'directory' => 'RecordsTestTheme', 'slug' => 'records-theme', 'version' => '0.1.1']);
        $before = ExtensionUpdateRecorder::snapshot($theme);
        $theme->update(['version' => '0.1.0']);

        ExtensionUpdateRecorder::succeeded('rollback', $theme, $before, null);

        $row = ThemeVersionHistory::where('theme_slug', 'records-theme')->sole();
        $this->assertSame(
            ['0.1.1', '0.1.0', ThemeVersionHistory::METHOD_ROLLBACK],
            [$row->old_version, $row->new_version, $row->installation_method],
        );
        $this->assertSame(1, AuditLog::where('action', AuditLog::ACTION_THEME_ROLLED_BACK)->count());
    }

    public function test_a_failure_is_audited_without_a_history_row(): void
    {
        $plugin = $this->plugin('0.1.0');

        ExtensionUpdateRecorder::failed('update', $plugin, '0.1.0', '0.1.1', null, 'boom');
        ExtensionUpdateRecorder::failed('rollback', $plugin, '0.1.0', null, null, 'boom');

        $this->assertSame(0, PluginVersionHistory::count());
        $failed = AuditLog::whereIn('action', [AuditLog::ACTION_PLUGIN_UPDATE_FAILED, AuditLog::ACTION_PLUGIN_ROLLBACK_FAILED])->get();
        $this->assertCount(2, $failed);
        foreach ($failed as $audit) {
            $this->assertSame(AuditLog::OUTCOME_FAILURE, $audit->outcome);
            $this->assertSame('boom', $audit->context['error'] ?? null);
        }
    }

    public function test_a_changed_signing_key_is_flagged(): void
    {
        $plugin = $this->plugin('0.1.0', 'key-old');
        $before = ExtensionUpdateRecorder::snapshot($plugin);
        $plugin->update(['version' => '0.1.1', 'signing_key_id' => 'key-new']);

        ExtensionUpdateRecorder::succeeded('update', $plugin, $before, null);

        $this->assertTrue((bool) PluginVersionHistory::sole()->signing_key_changed);
        $this->assertSame(1, AuditLog::where('action', AuditLog::ACTION_PLUGIN_SIGNING_KEY_CHANGED)->count());
    }

    public function test_a_rollback_keeps_the_newer_version_on_offer(): void
    {
        $dir = storage_path('framework/testing/rollback-offer-'.uniqid());
        File::ensureDirectoryExists($dir);
        File::put($dir.'/plugin.json', json_encode(['version' => '0.1.0']));
        $plugin = $this->plugin('0.1.1');

        try {
            $command = $this->app->make(PluginRollback::class);
            (new \ReflectionMethod($command, 'syncPluginVersion'))->invoke($command, $plugin, $dir, '0.1.1');
        } finally {
            File::deleteDirectory($dir);
        }

        $plugin->refresh();
        $this->assertSame('0.1.0', $plugin->version);
        $this->assertSame('0.1.1', $plugin->available_version);
    }

    public function test_every_entry_point_goes_through_the_recorder(): void
    {
        foreach (['PluginUpdate', 'ThemeUpdate', 'PluginRollback', 'ThemeRollback'] as $command) {
            $source = File::get(app_path("Console/Commands/{$command}.php"));
            $this->assertStringContainsString('ExtensionUpdateRecorder::succeeded(', $source, $command);
            $this->assertStringContainsString('ExtensionUpdateRecorder::failed(', $source, $command);
            $this->assertStringContainsString('{--applied-by=', $source, $command);
        }

        $batch = File::get(app_path('Console/Commands/ExtensionsUpdate.php'));
        $this->assertSame(2, substr_count($batch, "'--applied-by' => \$appliedBy"));

        $screen = File::get(app_path('Http/Controllers/Admin/Settings/Systems/AdminSystemUpdatesController.php'));
        $this->assertStringContainsString('dls:extensions:update%s --no-interaction >> %s', $screen);
        $this->assertStringContainsString("' --applied-by='", $screen);

        $core = File::get(app_path('Console/Commands/CoreRollback.php'));
        $this->assertStringContainsString("CoreRelease::singleton()->forceFill(['available_version' => \$rolledBackFrom])", $core);
    }

    private function plugin(string $version, ?string $signingKey = null): Plugin
    {
        return Plugin::create([
            'name' => 'RecordsTest',
            'directory' => 'RecordsTestPlugin',
            'slug' => 'records-test',
            'namespace' => 'Plugins\\RecordsTestPlugin\\',
            'version' => $version,
            'installed_at' => now(),
            'signing_key_id' => $signingKey,
        ]);
    }

    private function member(): Member
    {
        return Member::create([
            'account_name' => 'recorder',
            'display_name' => 'Recorder',
            'email' => 'recorder@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => MemberRole::SUPER_ADMIN,
            'status' => MemberStatus::Active,
        ]);
    }
}
