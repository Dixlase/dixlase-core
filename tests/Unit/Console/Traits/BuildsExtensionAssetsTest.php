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
use Tests\TestCase;

/**
 * Pins the tri-state that resolveAssetBuildMode() derives from the
 * --build / --skip-build flags. The default (no flag) is 'auto' —
 * the release-artifact contract on the trait relies on this staying
 * that way, since a regression to 'force' would silently make every
 * production install / update require Node.
 */
class BuildsExtensionAssetsTest extends TestCase
{
    public function test_no_flag_defaults_to_auto(): void
    {
        $host = $this->makeHost(build: false, skipBuild: false);

        $this->assertSame('auto', $host->publicResolveAssetBuildMode());
    }

    public function test_build_flag_forces_a_rebuild(): void
    {
        $host = $this->makeHost(build: true, skipBuild: false);

        $this->assertSame('force', $host->publicResolveAssetBuildMode());
    }

    public function test_skip_build_flag_skips(): void
    {
        $host = $this->makeHost(build: false, skipBuild: true);

        $this->assertSame('skip', $host->publicResolveAssetBuildMode());
    }

    public function test_skip_build_wins_over_build_when_both_are_passed(): void
    {
        // A wrapper script that always sets --build should still yield
        // 'skip' when tonight's operator adds --skip-build on top.
        $host = $this->makeHost(build: true, skipBuild: true);

        $this->assertSame('skip', $host->publicResolveAssetBuildMode());
    }

    private function makeHost(bool $build, bool $skipBuild): object
    {
        return new class($build, $skipBuild)
        {
            use BuildsExtensionAssets;

            public function __construct(
                private bool $buildFlag,
                private bool $skipBuildFlag,
            ) {}

            public function option(string $key): mixed
            {
                return match ($key) {
                    'build' => $this->buildFlag,
                    'skip-build' => $this->skipBuildFlag,
                    default => null,
                };
            }

            public function publicResolveAssetBuildMode(): string
            {
                return $this->resolveAssetBuildMode();
            }
        };
    }
}
