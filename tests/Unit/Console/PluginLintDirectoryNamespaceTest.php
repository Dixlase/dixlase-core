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

use App\Console\Commands\PluginLint;
use Illuminate\Support\Facades\File;
use ReflectionMethod;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

/**
 * Nothing used to report a directory whose name is not the one its manifest
 * declares. It surfaced as a white screen after an autoloader dump without
 * `--optimize`, because the optimized classmap was the only thing resolving
 * the declared casing (#488). `dls:plugin:lint` reports it now.
 */
class PluginLintDirectoryNamespaceTest extends TestCase
{
    private string $tmp;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmp = storage_path('framework/testing/plugin-lint-'.uniqid());
        File::ensureDirectoryExists($this->tmp);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->tmp);

        parent::tearDown();
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @return array{0: bool, 1: string} whether it reported, and what it printed
     */
    private function check(array $manifest, string $directoryName): array
    {
        File::put($this->tmp.'/plugin.json', (string) json_encode($manifest));

        $command = app(PluginLint::class);
        $output = new BufferedOutput();
        $command->setOutput(new \Illuminate\Console\OutputStyle(
            new \Symfony\Component\Console\Input\ArrayInput([]),
            $output,
        ));

        $method = new ReflectionMethod(PluginLint::class, 'reportDirectoryNamespaceMismatch');
        $method->setAccessible(true);

        return [$method->invoke($command, $this->tmp, $directoryName), $output->fetch()];
    }

    public function test_a_directory_whose_case_differs_from_the_namespace_is_reported(): void
    {
        [$reported, $printed] = $this->check(['namespace' => 'Plugins\\DixlaseSEO'], 'DixlaseSeo');

        $this->assertTrue($reported, 'DixlaseSeo vs Plugins\\DixlaseSEO is exactly the case PSR-4 refuses to match.');
        $this->assertStringContainsString('DixlaseSeo', $printed);
        $this->assertStringContainsString('DixlaseSEO', $printed, 'The report has to name the directory it should be.');
        $this->assertStringContainsString('--optimize', $printed, 'The hint names the command that must carry --optimize.');
    }

    public function test_a_matching_directory_is_silent(): void
    {
        [$reported, $printed] = $this->check(['namespace' => 'Plugins\\DixlaseSEO'], 'DixlaseSEO');

        $this->assertFalse($reported);
        $this->assertSame('', $printed);
    }

    public function test_a_manifest_that_declares_no_name_is_not_reported_as_a_mismatch(): void
    {
        [$reported] = $this->check(['version' => '0.1.0'], 'Whatever');

        $this->assertFalse($reported, 'With nothing to compare against, there is no finding to report.');
    }

    public function test_an_unreadable_manifest_does_not_throw(): void
    {
        File::put($this->tmp.'/plugin.json', '{ not json');

        $command = app(PluginLint::class);
        $command->setOutput(new \Illuminate\Console\OutputStyle(
            new \Symfony\Component\Console\Input\ArrayInput([]),
            new BufferedOutput(),
        ));

        $method = new ReflectionMethod(PluginLint::class, 'reportDirectoryNamespaceMismatch');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($command, $this->tmp, 'Whatever'));
    }
}
