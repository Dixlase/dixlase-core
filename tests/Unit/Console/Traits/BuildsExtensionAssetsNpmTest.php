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

namespace Tests\Unit\Console\Traits;

use App\Console\Traits\BuildsExtensionAssets;
use App\Services\Extension\ExtensionAssetBuildReport;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

/**
 * Pins how the asset build installs npm packages and reports failures.
 *
 * A fake `npm` placed first on PATH records its arguments, so the test
 * sees which command the trait ran without needing Node. The fake fails
 * `npm run build` when FAKE_NPM_FAIL_BUILD is set.
 *
 * Regression: `npm install` rewrote a signed plugin's package-lock.json
 * during install, and the plugin then failed verification on activation;
 * the failed build itself was invisible from the admin panel.
 */
class BuildsExtensionAssetsNpmTest extends TestCase
{
    private string $root;

    private string $extension;

    private string|false $originalPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = storage_path('framework/testing/npm-build-'.uniqid());
        $this->extension = $this->root.'/extension';
        File::ensureDirectoryExists($this->root.'/bin');
        File::ensureDirectoryExists($this->extension);

        File::put($this->root.'/bin/npm', <<<'SH'
#!/bin/sh
echo "$*" >> "$(dirname "$0")/../calls.log"
if [ "$1" = "run" ] && [ -n "$FAKE_NPM_FAIL_BUILD" ]; then
    echo "vite build failed" >&2
    exit 1
fi
exit 0
SH);
        chmod($this->root.'/bin/npm', 0755);

        File::put($this->extension.'/package.json', json_encode([
            'name' => 'extension',
            'scripts' => ['build' => 'vite build'],
        ]));

        $this->originalPath = getenv('PATH');
        putenv('PATH='.$this->root.'/bin:'.($this->originalPath ?: '/usr/bin:/bin'));
    }

    protected function tearDown(): void
    {
        putenv($this->originalPath === false ? 'PATH' : 'PATH='.$this->originalPath);
        $this->failBuild(false);
        File::deleteDirectory($this->root);

        parent::tearDown();
    }

    public function test_npm_ci_is_used_when_a_lock_file_is_shipped(): void
    {
        $lock = '{"lockfileVersion": 3, "packages": {"node_modules/x": {"libc": ["glibc"]}}}';
        File::put($this->extension.'/package-lock.json', $lock);

        $this->assertTrue($this->makeHost()->build($this->extension));

        $this->assertSame(['ci', 'run build'], $this->calls());
        $this->assertSame($lock, File::get($this->extension.'/package-lock.json'));
    }

    public function test_npm_install_is_used_without_a_lock_file(): void
    {
        $this->assertTrue($this->makeHost()->build($this->extension));

        $this->assertSame(['install', 'run build'], $this->calls());
    }

    public function test_a_failed_build_is_logged_and_recorded(): void
    {
        File::put($this->extension.'/package-lock.json', '{}');
        $this->failBuild(true);
        Log::spy();

        $this->assertFalse($this->makeHost()->build($this->extension));

        $failure = app(ExtensionAssetBuildReport::class)->failureFor($this->extension);
        $this->assertSame(['command' => 'npm run build', 'exit_code' => 1], $failure);
        Log::shouldHaveReceived('warning')->withArgs(
            fn (string $message, array $context) => $message === 'Extension asset build step failed'
                && $context['command'] === 'npm run build'
                && str_contains($context['output'], 'vite build failed')
        )->once();
    }

    public function test_a_later_successful_build_clears_the_failure(): void
    {
        $this->failBuild(true);
        $this->makeHost()->build($this->extension);
        $this->failBuild(false);

        $this->assertTrue($this->makeHost()->build($this->extension));

        $this->assertNull(app(ExtensionAssetBuildReport::class)->failureFor($this->extension));
    }

    /**
     * Symfony Process passes a variable to the child only when it is in
     * both getenv() and $_SERVER, so set it in both.
     */
    private function failBuild(bool $fail): void
    {
        if ($fail) {
            putenv('FAKE_NPM_FAIL_BUILD=1');
            $_SERVER['FAKE_NPM_FAIL_BUILD'] = '1';
        } else {
            putenv('FAKE_NPM_FAIL_BUILD');
            unset($_SERVER['FAKE_NPM_FAIL_BUILD']);
        }
    }

    /**
     * @return list<string>
     */
    private function calls(): array
    {
        return array_values(array_filter(explode("\n", File::get($this->root.'/calls.log'))));
    }

    private function makeHost(): object
    {
        return new class
        {
            use BuildsExtensionAssets;

            private BufferedOutput $output;

            public function __construct()
            {
                $this->output = new BufferedOutput();
            }

            public function build(string $path): bool
            {
                return $this->buildExtensionAssets($path, 'force');
            }

            public function info(string $message): void {}

            public function warn(string $message): void {}

            public function getOutput(): BufferedOutput
            {
                return $this->output;
            }
        };
    }
}
