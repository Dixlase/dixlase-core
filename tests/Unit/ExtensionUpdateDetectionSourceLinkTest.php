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

namespace Tests\Unit;

use App\Models\Plugin;
use App\Models\Theme;
use App\Services\Extension\ExtensionSourceManager;
use App\Services\Extension\SourceVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Update detection must be scoped to an extension's linked source.
 *
 * dls:plugin:update / dls:theme:update both refuse to update an
 * extension with no linked source (source_id NULL). Detection must
 * agree: an unlinked extension is not updatable, so it must not be
 * advertised as having an update — otherwise the operator sees an
 * "update available" badge that leads to a dead-end button. These
 * tests pin that detection neither queries a source nor reports an
 * update for an unlinked extension, and that it clears any stale flag.
 */
class ExtensionUpdateDetectionSourceLinkTest extends TestCase
{
    use RefreshDatabase;

    private ExtensionSourceManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = new ExtensionSourceManager(new SourceVerifier());
    }

    public function test_unlinked_plugin_is_not_advertised_for_update_and_no_source_is_queried(): void
    {
        Http::fake();

        $plugin = Plugin::query()->create([
            'name' => 'Bundled Plugin',
            'slug' => 'bundled-plugin',
            'directory' => 'BundledPlugin',
            'namespace' => 'BundledPlugin',
            'version' => '0.9.0',
            'installed_at' => now(),
            'source_id' => null,
            'available_version' => '1.0.0', // stale flag from a prior check
        ]);

        $updates = $this->invokeProtected('checkPluginUpdates');

        $plugin->refresh();
        $this->assertNull($plugin->available_version, 'unlinked plugin must not advertise an update');
        $this->assertNotNull($plugin->last_version_check, 'the check must still record it ran');
        $this->assertSame([], $updates, 'unlinked plugin must not appear in the update list');
        Http::assertNothingSent();
    }

    public function test_unlinked_theme_is_not_advertised_for_update_and_no_source_is_queried(): void
    {
        Http::fake();

        $theme = Theme::query()->create([
            'name' => 'Bundled Theme',
            'slug' => 'bundled-theme',
            'directory' => 'BundledTheme',
            'version' => '0.9.0',
            'installed_at' => now(),
            'source_id' => null,
            'available_version' => '1.0.0', // stale flag from a prior check
        ]);

        $updates = $this->invokeProtected('checkThemeUpdates');

        $theme->refresh();
        $this->assertNull($theme->available_version, 'unlinked theme must not advertise an update');
        $this->assertNotNull($theme->last_version_check, 'the check must still record it ran');
        $this->assertSame([], $updates, 'unlinked theme must not appear in the update list');
        Http::assertNothingSent();
    }

    private function invokeProtected(string $method): array
    {
        $ref = new \ReflectionMethod($this->manager, $method);
        $ref->setAccessible(true);

        return $ref->invoke($this->manager);
    }
}
