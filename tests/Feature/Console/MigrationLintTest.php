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

namespace Tests\Feature\Console;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * MigrationLint コマンドのフィーチャーテスト。
 *
 * --base-path で fixture ディレクトリに切り替え、本番の database/migration-lock.json
 * や database/migrations/ に影響を与えずに 8 ケースを検証する。
 */
class MigrationLintTest extends TestCase
{
    /**
     * テスト用 fixture ディレクトリ（base_path 相当）
     */
    protected string $fixtureBasePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixtureBasePath = sys_get_temp_dir().'/dls-migration-lint-'.uniqid('', true);
        File::ensureDirectoryExists($this->fixtureBasePath.'/database/migrations');
    }

    protected function tearDown(): void
    {
        if (File::isDirectory($this->fixtureBasePath)) {
            File::deleteDirectory($this->fixtureBasePath);
        }

        parent::tearDown();
    }

    /**
     * 最低限のマイグレーションファイルを作成する。
     */
    protected function writeMigration(string $relativePath, string $body = '<?php // dummy'): void
    {
        $absolute = $this->fixtureBasePath.'/'.ltrim($relativePath, '/');
        File::ensureDirectoryExists(dirname($absolute));
        File::put($absolute, $body);
    }

    /**
     * --lock を実行してロックファイルを生成する。
     */
    protected function generateLockfile(): int
    {
        return Artisan::call('dls:migration:lint', [
            '--lock' => true,
            '--base-path' => $this->fixtureBasePath,
        ]);
    }

    /**
     * 検証実行（戻り値: exit code）
     */
    protected function runVerify(bool $json = false): int
    {
        $options = ['--base-path' => $this->fixtureBasePath];
        if ($json) {
            $options['--json'] = true;
        }

        return Artisan::call('dls:migration:lint', $options);
    }

    public function test_clean_state_passes_after_lock(): void
    {
        $this->writeMigration('database/migrations/0001_01_01_000000_create_alpha_table.php');
        $this->writeMigration('database/migrations/0001_01_01_000001_create_beta_table.php');

        $this->assertSame(0, $this->generateLockfile());
        $this->assertFileExists($this->fixtureBasePath.'/database/migration-lock.json');

        $this->assertSame(0, $this->runVerify());
        $this->assertStringContainsString('All migration files are consistent', Artisan::output());
    }

    public function test_modified_locked_migration_is_detected(): void
    {
        $path = 'database/migrations/0001_01_01_000000_create_alpha_table.php';
        $this->writeMigration($path, '<?php // original');
        $this->generateLockfile();

        // ロック後にロック済みマイグレーションを改変
        $this->writeMigration($path, '<?php // tampered');

        $this->assertSame(1, $this->runVerify());
        $output = Artisan::output();
        $this->assertStringContainsString('VIOLATION', $output);
        $this->assertStringContainsString('1 modified migration', $output);
        $this->assertStringContainsString($path, $output);
    }

    public function test_deleted_locked_migration_is_detected(): void
    {
        $path = 'database/migrations/0001_01_01_000000_create_alpha_table.php';
        $this->writeMigration($path);
        $this->writeMigration('database/migrations/0001_01_01_000001_create_beta_table.php');
        $this->generateLockfile();

        File::delete($this->fixtureBasePath.'/'.$path);

        $this->assertSame(1, $this->runVerify());
        $output = Artisan::output();
        $this->assertStringContainsString('1 deleted migration', $output);
        $this->assertStringContainsString($path, $output);
    }

    public function test_legitimate_post_lock_addition_is_accepted(): void
    {
        $this->writeMigration('database/migrations/0001_01_01_000000_create_alpha_table.php');
        $this->generateLockfile();

        // リリース後の正規追加（YYYY_MM_DD_HHMMSS_*）
        $this->writeMigration('database/migrations/2026_05_01_120000_add_gamma_column.php');

        $this->assertSame(0, $this->runVerify());
        $output = Artisan::output();
        $this->assertStringContainsString('1 new migration', $output);
        $this->assertStringContainsString('2026_05_01_120000_add_gamma_column.php', $output);
    }

    public function test_post_lock_addition_with_pre_release_prefix_is_violation(): void
    {
        $this->writeMigration('database/migrations/0001_01_01_000000_create_alpha_table.php');
        $this->generateLockfile();

        // リリース後に 0001_01_01_* で追加 → 違反
        $this->writeMigration('database/migrations/0001_01_01_999998_add_late_table.php');

        $this->assertSame(1, $this->runVerify());
        $output = Artisan::output();
        $this->assertStringContainsString('naming convention violation', $output);
        $this->assertStringContainsString('0001_01_01_999998_add_late_table.php', $output);
    }

    public function test_missing_lockfile_warns_and_passes(): void
    {
        $this->writeMigration('database/migrations/0001_01_01_000000_create_alpha_table.php');

        // --lock を呼ばずに verify
        $this->assertSame(0, $this->runVerify());
        $this->assertStringContainsString('Lockfile not found', Artisan::output());
    }

    public function test_json_output_emits_valid_structure(): void
    {
        $this->writeMigration('database/migrations/0001_01_01_000000_create_alpha_table.php');
        $this->generateLockfile();

        $this->assertSame(0, $this->runVerify(json: true));
        $output = Artisan::output();
        $decoded = json_decode($output, true);

        $this->assertIsArray($decoded);
        $this->assertSame('verify', $decoded['action']);
        $this->assertSame('clean', $decoded['status']);
        $this->assertSame([], $decoded['modified']);
        $this->assertSame([], $decoded['deleted']);
        $this->assertSame([], $decoded['naming_violations']);
        $this->assertArrayHasKey('lockfile_generated_at', $decoded);
    }

    public function test_lockfile_includes_plugin_and_theme_migrations(): void
    {
        $this->writeMigration('database/migrations/0001_01_01_000000_create_alpha_table.php');
        $this->writeMigration('plugins/SomePlugin/database/migrations/0001_01_01_000001_create_plg_table.php');
        $this->writeMigration('themes/SomeTheme/database/migrations/0001_01_01_000001_create_thm_table.php');

        $this->generateLockfile();

        $lockfile = json_decode(File::get($this->fixtureBasePath.'/database/migration-lock.json'), true);
        $this->assertCount(3, $lockfile['files']);
        $this->assertArrayHasKey('database/migrations/0001_01_01_000000_create_alpha_table.php', $lockfile['files']);
        $this->assertArrayHasKey('plugins/SomePlugin/database/migrations/0001_01_01_000001_create_plg_table.php', $lockfile['files']);
        $this->assertArrayHasKey('themes/SomeTheme/database/migrations/0001_01_01_000001_create_thm_table.php', $lockfile['files']);
    }

    public function test_underscore_prefix_backup_files_are_excluded_from_lockfile(): void
    {
        $this->writeMigration('database/migrations/0001_01_01_000000_create_alpha_table.php');
        $this->writeMigration('database/migrations/_0001_01_01_999998_legacy.php.bak');

        $this->generateLockfile();

        $lockfile = json_decode(File::get($this->fixtureBasePath.'/database/migration-lock.json'), true);
        $this->assertCount(1, $lockfile['files']);
        $this->assertArrayHasKey('database/migrations/0001_01_01_000000_create_alpha_table.php', $lockfile['files']);
    }
}
