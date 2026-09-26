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

namespace Tests\Unit\Services\Core;

use App\Services\Core\ArtisanProcess;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Tests\TestCase;

/**
 * ArtisanProcess runs post-vendor-swap steps of a core update in a new
 * process. A stand-in "artisan" script records its arguments, so the
 * test checks the command line and the failure handling without booting
 * the application a second time.
 */
class ArtisanProcessTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = storage_path('framework/testing/artisan-process-'.uniqid());
        File::ensureDirectoryExists($this->dir);
        File::put($this->dir.'/artisan', <<<'PHP'
<?php
file_put_contents(__DIR__.'/argv.json', json_encode(array_slice($argv, 1)));
echo "ran {$argv[1]}\n";
exit($argv[1] === 'fail' ? 3 : 0);
PHP);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);

        parent::tearDown();
    }

    public function test_options_are_passed_the_way_artisan_call_takes_them(): void
    {
        $output = $this->runner()->run('migrate', [
            '--path' => 'database/migrations',
            '--force' => true,
            '--pretend' => false,
        ]);

        $this->assertSame('ran migrate', $output);
        $this->assertSame(
            ['migrate', '--path=database/migrations', '--force', '--no-interaction'],
            json_decode(File::get($this->dir.'/argv.json'), true),
        );
    }

    public function test_it_runs_in_the_directory_of_artisan(): void
    {
        File::put($this->dir.'/artisan', '<?php echo getcwd();');

        $this->assertSame(realpath($this->dir), realpath($this->runner()->run('about')));
    }

    public function test_a_non_zero_exit_throws_with_the_output(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('php artisan fail failed (exit 3): ran fail');

        $this->runner()->run('fail');
    }

    public function test_boots_is_true_when_a_new_process_reaches_artisan(): void
    {
        $this->assertTrue($this->runner()->boots());
        $this->assertSame(
            ['--version', '--no-interaction'],
            json_decode(File::get($this->dir.'/argv.json'), true),
        );
    }

    public function test_boots_is_false_when_the_application_cannot_start(): void
    {
        File::put($this->dir.'/artisan', '<?php fwrite(STDERR, "boom"); exit(1);');

        $this->assertFalse($this->runner()->boots());
    }

    public function test_boots_is_false_rather_than_throwing_when_artisan_is_gone(): void
    {
        File::delete($this->dir.'/artisan');

        // What a rollback leaves behind if the restored release predates the
        // file: the caller needs an answer, not an exception.
        $this->assertFalse($this->runner()->boots());
    }

    private function runner(): ArtisanProcess
    {
        return new ArtisanProcess($this->dir.'/artisan', PHP_BINARY);
    }
}
