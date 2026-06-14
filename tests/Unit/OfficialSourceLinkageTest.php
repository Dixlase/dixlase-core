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

use App\Models\ExtensionSource;
use App\Services\Extension\ExtensionSourceManager;
use App\Services\Extension\SourceVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * officialLinkage() decides whether a bundled/CLI-installed extension
 * is linked to the official source at install time. The vendor gate is
 * the load-bearing rule: only an extension published under the official
 * owner is linked, so a third-party or hand-copied extension is never
 * mis-linked to the official source.
 */
class OfficialSourceLinkageTest extends TestCase
{
    use RefreshDatabase;

    private ExtensionSourceManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = new ExtensionSourceManager(new SourceVerifier());
    }

    private function makeOfficialSource(): ExtensionSource
    {
        return ExtensionSource::query()->create([
            'name' => 'GitHub',
            'type' => 'github',
            'base_url' => 'https://api.github.com',
            'owner' => 'Dixlase',
            'is_enabled' => true,
            'is_official' => true,
            'priority' => 0,
        ]);
    }

    public function test_links_an_official_vendor_extension_to_the_official_source(): void
    {
        $source = $this->makeOfficialSource();

        $linkage = $this->manager->officialLinkage('dixlase-onepage', 'theme', 'dixlase/dixlase-onepage');

        $this->assertNotNull($linkage);
        $this->assertSame($source->id, $linkage['source_id']);
        $this->assertSame('theme-dixlase-onepage', $linkage['source_repo']);
        $this->assertSame('github', $linkage['installation_method']);
        $this->assertSame('https://github.com/Dixlase/theme-dixlase-onepage', $linkage['installed_from_url']);
    }

    public function test_uses_the_plugin_repo_prefix_for_plugins(): void
    {
        $this->makeOfficialSource();

        $linkage = $this->manager->officialLinkage('dixlase-inquiry', 'plugin', 'dixlase/dixlase-inquiry');

        $this->assertNotNull($linkage);
        $this->assertSame('plugin-dixlase-inquiry', $linkage['source_repo']);
    }

    public function test_vendor_match_is_case_insensitive(): void
    {
        $this->makeOfficialSource();

        $linkage = $this->manager->officialLinkage('dixlase-onepage', 'theme', 'Dixlase/Dixlase-OnePage');

        $this->assertNotNull($linkage);
        $this->assertSame('theme-dixlase-onepage', $linkage['source_repo']);
    }

    public function test_does_not_link_a_third_party_vendor_extension(): void
    {
        $this->makeOfficialSource();

        $linkage = $this->manager->officialLinkage('acme-theme', 'theme', 'acme/acme-theme');

        $this->assertNull($linkage);
    }

    public function test_does_not_link_when_package_name_is_null(): void
    {
        $this->makeOfficialSource();

        $linkage = $this->manager->officialLinkage('mystery-theme', 'theme', null);

        $this->assertNull($linkage);
    }

    public function test_returns_null_when_no_official_source_exists(): void
    {
        // A non-official source exists, but no official one.
        ExtensionSource::query()->create([
            'name' => 'Third Party',
            'type' => 'github',
            'base_url' => 'https://api.github.com',
            'owner' => 'acme',
            'is_enabled' => true,
            'is_official' => false,
            'priority' => 0,
        ]);

        $linkage = $this->manager->officialLinkage('dixlase-onepage', 'theme', 'dixlase/dixlase-onepage');

        $this->assertNull($linkage);
    }
}
