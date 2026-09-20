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

namespace Tests\Unit;

use App\Models\ExtensionSource;
use App\Services\Extension\ExtensionSourceManager;
use App\Services\Extension\SourceVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * resolveInstallLinkage() is what dls:plugin:install / dls:theme:install
 * use to decide which extension source a freshly registered extension
 * is updatable from. dls:*:update refuses to run for a row whose
 * source_id is NULL, so the resolution order — explicit --source,
 * then the download sidecar, then the official-vendor default (manifest
 * package_name, then composer.json name) — is the contract under test.
 */
class InstallLinkageResolutionTest extends TestCase
{
    use RefreshDatabase;

    private ExtensionSourceManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = new ExtensionSourceManager(new SourceVerifier());
    }

    private function makeSource(string $name, bool $official, int $priority = 0): ExtensionSource
    {
        return ExtensionSource::query()->create([
            'name' => $name,
            'type' => 'github',
            'base_url' => 'https://api.github.com',
            'owner' => $official ? 'Dixlase' : 'Acme',
            // resolveSourceLinkage() builds installed_from_url from settings.owner
            'settings' => ['owner' => $official ? 'Dixlase' : 'Acme'],
            'is_enabled' => true,
            'is_official' => $official,
            'priority' => $priority,
        ]);
    }

    public function test_explicit_source_id_wins_over_sidecar_and_official_default(): void
    {
        $official = $this->makeSource('GitHub', official: true);
        $mirror = $this->makeSource('Mirror', official: false, priority: 5);
        $sidecar = ['source_id' => $official->id, 'source_repo' => 'x', 'installation_method' => 'github', 'installed_from_url' => null];

        $linkage = $this->manager->resolveInstallLinkage('dixlase-cookie', 'plugin', $mirror->id, $sidecar, 'dixlase/dixlase-cookie');

        $this->assertSame($mirror->id, $linkage['source_id']);
        $this->assertSame('plugin-dixlase-cookie', $linkage['source_repo']);
        $this->assertSame('github', $linkage['installation_method']);
        $this->assertSame('https://github.com/Acme/plugin-dixlase-cookie', $linkage['installed_from_url']);
    }

    public function test_unknown_explicit_source_id_falls_through_to_the_sidecar(): void
    {
        $official = $this->makeSource('GitHub', official: true);
        $sidecar = ['source_id' => $official->id, 'source_repo' => 'plugin-dixlase-cookie', 'installation_method' => 'github', 'installed_from_url' => 'https://github.com/Dixlase/plugin-dixlase-cookie'];

        $linkage = $this->manager->resolveInstallLinkage('dixlase-cookie', 'plugin', 999999, $sidecar, null);

        $this->assertSame($sidecar, $linkage);
    }

    public function test_sidecar_wins_over_the_official_default(): void
    {
        $this->makeSource('GitHub', official: true);
        $mirror = $this->makeSource('Mirror', official: false, priority: 5);
        $sidecar = ['source_id' => $mirror->id, 'source_repo' => 'plugin-dixlase-cookie', 'installation_method' => 'github', 'installed_from_url' => 'https://github.com/Acme/plugin-dixlase-cookie'];

        // The manifest says dixlase/..., which would pass the official
        // vendor gate — but the plugin was actually downloaded from the
        // mirror, and that is what it must stay linked to.
        $linkage = $this->manager->resolveInstallLinkage('dixlase-cookie', 'plugin', null, $sidecar, 'dixlase/dixlase-cookie');

        $this->assertSame($sidecar, $linkage);
    }

    public function test_official_default_applies_when_the_manifest_package_name_is_under_the_official_vendor(): void
    {
        $official = $this->makeSource('GitHub', official: true);

        $linkage = $this->manager->resolveInstallLinkage('dixlase-cookie', 'plugin', null, null, 'dixlase/dixlase-cookie');

        $this->assertSame($official->id, $linkage['source_id']);
    }

    public function test_official_default_falls_back_to_the_composer_name_when_the_manifest_package_name_fails_the_vendor_gate(): void
    {
        // The published DixlaseCookie manifest says `plugins/dixlase-cookie`
        // while its composer.json says `dixlase/dixlase-cookie`. The
        // manifest is what install() reads first, so without this
        // fallback the plugin ended up unlinked and dls:plugin:update
        // refused to run.
        $official = $this->makeSource('GitHub', official: true);

        $linkage = $this->manager->resolveInstallLinkage('dixlase-cookie', 'plugin', null, null, 'plugins/dixlase-cookie', 'dixlase/dixlase-cookie');

        $this->assertNotNull($linkage);
        $this->assertSame($official->id, $linkage['source_id']);
        $this->assertSame('plugin-dixlase-cookie', $linkage['source_repo']);
    }

    public function test_third_party_extension_with_no_source_stays_unlinked(): void
    {
        $this->makeSource('GitHub', official: true);

        $linkage = $this->manager->resolveInstallLinkage('acme-widget', 'plugin', null, null, 'acme/acme-widget', 'acme/acme-widget');

        $this->assertNull($linkage);
    }

    public function test_sidecar_without_source_id_is_ignored(): void
    {
        $official = $this->makeSource('GitHub', official: true);

        $linkage = $this->manager->resolveInstallLinkage('dixlase-cookie', 'plugin', null, ['installation_method' => 'github'], 'dixlase/dixlase-cookie');

        $this->assertSame($official->id, $linkage['source_id']);
    }
}
