<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
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

declare(strict_types=1);

namespace Tests\Feature\Install;

use App\Http\Middleware\CheckInstallationReady;
use App\Services\Install\InstallFinalizer;
use App\Support\Install\FinalizePendingMarker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * The install log does not carry the values the installer collects.
 *
 * The channel is a single unrotated file and the backup service copies the
 * whole log directory into every archive, so a value written here travels
 * further than the host it was typed on. The admin URL is the clearest case:
 * this project keeps it out of every written record.
 *
 * The channel is pointed at a throwaway file and the real output is read,
 * because what matters is what lands in the file, not which arguments a
 * mock was handed.
 */
class InstallLogOmitsSecretsTest extends TestCase
{
    use RefreshDatabase;

    private const ADMIN_SEGMENT = 'admin-custom';

    private const CSRF = 'csrf-token-value-that-must-not-be-logged';

    private string $logPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logPath = storage_path('framework/testing/install-'.uniqid().'.log');
        File::ensureDirectoryExists(dirname($this->logPath));

        config(['logging.channels.install' => [
            'driver' => 'single',
            'path' => $this->logPath,
            'level' => 'debug',
        ]]);

        $this->withoutMiddleware(CheckInstallationReady::class);

        $this->mock(InstallFinalizer::class, function ($mock): void {
            $mock->shouldReceive('finalize')->once()->andReturn(null);
        });

        FinalizePendingMarker::mark();
    }

    protected function tearDown(): void
    {
        FinalizePendingMarker::clear();
        File::delete($this->logPath);

        parent::tearDown();
    }

    public function test_finalize_logs_neither_the_admin_url_nor_the_request_body(): void
    {
        $this->post('/install/finalize', [
            'redirect_to' => '/'.self::ADMIN_SEGMENT.'/login',
            '_token' => self::CSRF,
        ])->assertRedirect('/'.self::ADMIN_SEGMENT.'/login');

        $written = File::exists($this->logPath) ? File::get($this->logPath) : '';

        // Guard against a vacuous pass: the step must have logged something,
        // otherwise the assertions below hold for an empty file.
        $this->assertStringContainsString(
            'finalize',
            $written,
            'The finalize step is expected to log; an empty log would make this test vacuous.',
        );

        $this->assertStringNotContainsString(
            self::ADMIN_SEGMENT,
            $written,
            'The install log must not carry the admin URL.',
        );
        $this->assertStringNotContainsString(
            self::CSRF,
            $written,
            'The install log must not carry the request body.',
        );
    }
}
