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

namespace Tests\Unit\Support;

use App\Support\ExtensionDirectoryName;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The directory an extension is installed into is also the middle segment of
 * the namespace its classes are autoloaded under, because
 * ComposerLocalManifest generates `Plugins\{Name}\App\` from the directory
 * basename alone. Composer matches a PSR-4 prefix case-sensitively, so a
 * directory whose spelling differs from what the files declare yields a
 * mapping no class is found through (#488).
 *
 * This pins the resolution order, and the one case that started it: a slug
 * cannot produce an acronym.
 */
class ExtensionDirectoryNameTest extends TestCase
{
    public function test_the_namespace_segment_beats_the_studly_slug_for_an_acronym(): void
    {
        // ucwords('dixlase seo') is 'DixlaseSeo'. Str::studly agrees. The
        // manifest is the only place that knows the plugin declares
        // Plugins\DixlaseSEO, which is why the name has to come from here.
        $this->assertSame(
            'DixlaseSEO',
            ExtensionDirectoryName::fromManifest(
                ['namespace' => 'Plugins\\DixlaseSEO'],
                ExtensionDirectoryName::KIND_PLUGIN,
            ),
        );

        $this->assertNotSame(
            'DixlaseSEO',
            str_replace(' ', '', ucwords(str_replace('-', ' ', 'dixlase-seo'))),
            'If the slug transform ever produced the right case, this whole class would be unnecessary.'
        );
    }

    public function test_package_wins_over_namespace_which_wins_over_package_name(): void
    {
        $all = [
            'package' => 'FromPackage',
            'namespace' => 'Plugins\\FromNamespace',
            'package_name' => 'vendor/from-package-name',
        ];

        $this->assertSame('FromPackage', ExtensionDirectoryName::fromManifest($all, ExtensionDirectoryName::KIND_PLUGIN));

        unset($all['package']);
        $this->assertSame('FromNamespace', ExtensionDirectoryName::fromManifest($all, ExtensionDirectoryName::KIND_PLUGIN));

        unset($all['namespace']);
        $this->assertSame('from-package-name', ExtensionDirectoryName::fromManifest($all, ExtensionDirectoryName::KIND_PLUGIN));
    }

    public function test_a_manifest_that_names_nothing_decides_nothing(): void
    {
        $this->assertNull(ExtensionDirectoryName::fromManifest([], ExtensionDirectoryName::KIND_PLUGIN));
        $this->assertNull(ExtensionDirectoryName::fromManifest(null, ExtensionDirectoryName::KIND_THEME));
        $this->assertNull(ExtensionDirectoryName::fromManifest(
            ['package' => '', 'namespace' => '', 'package_name' => ''],
            ExtensionDirectoryName::KIND_PLUGIN,
        ));
    }

    public function test_a_trailing_or_leading_separator_does_not_produce_an_empty_name(): void
    {
        $this->assertSame('MyPlugin', ExtensionDirectoryName::fromManifest(
            ['namespace' => '\\Plugins\\MyPlugin\\'],
            ExtensionDirectoryName::KIND_PLUGIN,
        ));

        $this->assertSame('my-theme', ExtensionDirectoryName::fromManifest(
            ['package_name' => '/vendor/my-theme/'],
            ExtensionDirectoryName::KIND_THEME,
        ));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function refusedNames(): array
    {
        return [
            'traversal into the webroot' => ['../public/shell'],
            'plain traversal' => ['..'],
            'current directory' => ['.'],
            'absolute path' => ['/etc/cron.d/evil'],
            'nested path' => ['sub/dir'],
            'backslash path' => ['sub\\dir'],
            'leading dot' => ['.hidden'],
            'nul byte' => ["ok\0/../public"],
            'empty' => [''],
        ];
    }

    #[DataProvider('refusedNames')]
    public function test_a_candidate_that_is_not_a_single_path_segment_is_refused(string $candidate): void
    {
        $this->assertNull(
            ExtensionDirectoryName::sanitise($candidate, ExtensionDirectoryName::KIND_PLUGIN),
            "The caller interpolates the result into base_path(\"plugins/{\$dir}\") before any scan runs, so {$candidate} must not survive."
        );
    }

    public function test_ordinary_names_still_pass(): void
    {
        foreach (['DixlasePages', 'DixlaseSEO', 'dixlase-seo', 'My_Plugin.v2', 'A1'] as $name) {
            $this->assertSame($name, ExtensionDirectoryName::sanitise($name, ExtensionDirectoryName::KIND_PLUGIN));
        }
    }
}
