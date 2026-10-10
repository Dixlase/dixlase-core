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

use App\Console\Commands\PluginDownload;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\File;
use ReflectionMethod;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

/**
 * `dls:plugin:download --extract` used to name the directory it created by
 * studly-casing the slug, so `dixlase-seo` landed in `plugins/DixlaseSeo`
 * while the plugin's files declare `Plugins\DixlaseSEO` — a PSR-4 prefix no
 * class is found through, and the same release installed through the admin
 * panel produced `plugins/DixlaseSEO` instead (#488).
 *
 * The real download → extract chain needs a live source and writes into the
 * repository's own plugins/, which tests must not touch, so this exercises
 * the resolution step on its own against a manifest in a throwaway directory.
 */
class PluginDownloadDirectoryNameTest extends TestCase
{
    private string $tmp;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmp = storage_path('framework/testing/plugin-download-'.uniqid());
        File::ensureDirectoryExists($this->tmp);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->tmp);

        parent::tearDown();
    }

    /**
     * @param  array<string, mixed>|string  $manifest  decoded manifest, or raw file contents
     */
    private function resolve(array|string $manifest, ?string $archiveRoot, string $slug): string
    {
        $path = $this->tmp.'/plugin.json';
        File::put($path, is_string($manifest) ? $manifest : (string) json_encode($manifest));

        // The fallback path warns, so the command needs somewhere to write.
        $command = app(PluginDownload::class);
        $command->setOutput(new OutputStyle(new ArrayInput([]), new BufferedOutput()));

        $method = new ReflectionMethod(PluginDownload::class, 'resolveDirectoryName');
        $method->setAccessible(true);

        return $method->invoke($command, $path, $archiveRoot, $slug);
    }

    public function test_the_manifest_decides_even_when_the_slug_and_archive_disagree(): void
    {
        $this->assertSame(
            'DixlaseSEO',
            $this->resolve(['namespace' => 'Plugins\\DixlaseSEO'], 'dixlase-seo-0.1.1', 'dixlase-seo'),
        );
    }

    public function test_the_archive_folder_is_used_when_the_manifest_names_nothing(): void
    {
        $this->assertSame(
            'DixlaseSEO',
            $this->resolve(['version' => '0.1.1'], 'DixlaseSEO', 'dixlase-seo'),
            "The release ZIP wraps the payload in the repository's own directory name, which is better than the slug."
        );
    }

    public function test_the_studly_slug_is_the_last_resort(): void
    {
        $this->assertSame(
            'DixlaseCookie',
            $this->resolve(['version' => '0.1.3'], null, 'dixlase-cookie'),
            'With no manifest name and no single top-level folder, the old behaviour is what remains.'
        );
    }

    public function test_an_archive_folder_that_is_not_a_single_path_segment_is_not_used(): void
    {
        $this->assertSame(
            'DixlaseSeo',
            $this->resolve(['version' => '0.1.1'], '../public/shell', 'dixlase-seo'),
            'The archive controls that name, so it goes through the same guard as the manifest.'
        );
    }

    public function test_an_unreadable_manifest_falls_back_instead_of_throwing(): void
    {
        $this->assertSame(
            'DixlaseSEO',
            $this->resolve('{ not json', 'DixlaseSEO', 'dixlase-seo'),
            'A malformed plugin.json must not abort a download that the old code completed.'
        );
    }
}
