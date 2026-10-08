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

use App\Models\ExtensionSource;
use App\Services\Extension\ExtensionDownloadFreshness;
use App\Services\Extension\ExtensionSourceManager;
use App\Services\Extension\ExtensionSourceSidecar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

/**
 * A downloaded but not yet installed extension that is older than its
 * source's latest release is flagged (dixlase-core#456).
 *
 * The sidecar and the source are mocked, so nothing reads the real
 * plugins/ or themes/.
 */
class StaleDownloadWarningTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_download_older_than_the_latest_release_is_flagged(): void
    {
        $freshness = $this->freshness(['source_id' => 7], '0.1.2');

        $this->assertSame('0.1.2', $freshness->newerRelease('plugin', 'DixlasePages', 'dixlase-pages', '0.1.0'));
        $this->assertSame('0.1.2', $freshness->newerRelease('theme', 'DixlaseOnePage', 'dixlase-onepage', 'v0.1.1'));
    }

    public function test_a_current_download_is_not_flagged(): void
    {
        $freshness = $this->freshness(['source_id' => 7], '0.1.2');

        $this->assertNull($freshness->newerRelease('plugin', 'DixlasePages', 'dixlase-pages', '0.1.2'));
        $this->assertNull($freshness->newerRelease('plugin', 'DixlasePages', 'dixlase-pages', '0.2.0'));
    }

    public function test_a_download_without_a_source_is_not_checked(): void
    {
        $sources = Mockery::mock(ExtensionSourceManager::class);
        $sources->shouldNotReceive('latestReleaseVersionFor');
        $freshness = $this->freshness(null, null, $sources);

        $this->assertNull($freshness->newerRelease('plugin', 'UploadedZip', 'uploaded-zip', '0.1.0'));
    }

    public function test_a_failed_lookup_is_not_flagged(): void
    {
        $sources = Mockery::mock(ExtensionSourceManager::class);
        $sources->shouldReceive('latestReleaseVersionFor')->andThrow(new \RuntimeException('rate limited'));

        $this->assertNull($this->freshness(['source_id' => 7], null, $sources)->newerRelease('plugin', 'DixlasePages', 'dixlase-pages', '0.1.0'));
    }

    public function test_the_latest_release_lookup_is_cached(): void
    {
        Cache::flush();
        $source = ExtensionSource::query()->create([
            'name' => 'Official', 'type' => 'github', 'base_url' => 'https://api.github.com',
            'owner' => 'TestOrg', 'is_enabled' => true, 'priority' => 0,
        ]);
        Http::fake([
            '*/releases/latest' => Http::response(['tag_name' => 'v0.1.2', 'name' => 'v0.1.2', 'assets' => []]),
            '*' => Http::response([], 404),
        ]);

        $manager = app(ExtensionSourceManager::class);
        $this->assertSame('0.1.2', $manager->latestReleaseVersionFor('dixlase-pages', 'plugin', $source->id));
        $this->assertSame('0.1.2', $manager->latestReleaseVersionFor('dixlase-pages', 'plugin', $source->id));

        Http::assertSentCount(1);
    }

    public function test_the_lists_the_install_screens_and_the_cli_use_it(): void
    {
        foreach (['AdminPluginsSettingsController', 'AdminThemesSettingsController'] as $controller) {
            $this->assertStringContainsString("['stale_latest_version'] = \$freshness->newerRelease(", File::get(app_path("Http/Controllers/Admin/Settings/{$controller}.php")), $controller);
        }
        foreach (['PluginInstall', 'ThemeInstall'] as $command) {
            $this->assertStringContainsString('ExtensionDownloadFreshness::class)->newerRelease(', File::get(app_path("Console/Commands/{$command}.php")), $command);
        }
        $this->assertStringContainsString("'staleLatestVersion' => \$isModel ? null", File::get(app_path('Presenters/Admin/ExtensionCardPresenter.php')));
        $this->assertStringContainsString('data-stale-notice=', File::get(resource_path('views/admin/settings/plugins/partials/uninstalled-actions.blade.php')));
        $this->assertStringContainsString('staleLatestVersion', File::get(resource_path('views/admin/settings/themes/partials/uninstalled-actions.blade.php')));
        $this->assertStringContainsString('staleNotice', File::get(resource_path('src/admin/settings/plugins/js/two-stage-modal.js')));
    }

    /**
     * @param  array<string, mixed>|null  $sidecar
     */
    private function freshness(?array $sidecar, ?string $latest, ?ExtensionSourceManager $sources = null): ExtensionDownloadFreshness
    {
        $reader = Mockery::mock(ExtensionSourceSidecar::class);
        $reader->shouldReceive('read')->andReturn($sidecar);

        if ($sources === null) {
            $sources = Mockery::mock(ExtensionSourceManager::class);
            $sources->shouldReceive('latestReleaseVersionFor')->andReturn($latest);
        }

        return new ExtensionDownloadFreshness($sources, $reader);
    }
}
