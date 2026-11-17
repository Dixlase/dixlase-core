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

namespace Tests\Feature\Console\Backup;

use App\Console\Commands\Backup\BackupCreateCommand;
use App\Contracts\Backup\BackupServiceInterface;
use App\DTO\Backup\BackupResultDTO;
use Mockery;
use Mockery\MockInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use Tests\TestCase;

/**
 * `dls:backup:create --format=json` writes one JSON object to stdout and
 * nothing else; progress goes to stderr. The field names are documented
 * in docs/operations/maintenance/backup-commands.md and are a contract.
 */
class BackupCreateJsonOutputTest extends TestCase
{
    private const FIELDS = [
        'success', 'backup_record_id', 'file_path', 'file_name', 'file_size',
        'duration', 'targets', 'hash', 'error',
    ];

    public function test_success_prints_one_object_with_the_documented_fields(): void
    {
        $this->fakeService(fn (MockInterface $m) => $m->shouldReceive('backup')
            ->once()->with(['database'], [])
            ->andReturn(BackupResultDTO::success(
                backupRecordId: 42,
                filePath: '/srv/backups/dixlase-backup-20261117-093000-database.zip',
                fileSize: 2048,
                duration: 1.23456,
                targets: ['database'],
                metadata: ['hash' => 'abc123'],
            )));

        [$exit, $stdout, $stderr] = $this->runSplit(['--targets' => 'database', '--format' => 'json']);

        $this->assertSame(0, $exit);
        $this->assertSame([
            'success' => true,
            'backup_record_id' => 42,
            'file_path' => '/srv/backups/dixlase-backup-20261117-093000-database.zip',
            'file_name' => 'dixlase-backup-20261117-093000-database.zip',
            'file_size' => 2048,
            'duration' => 1.23,
            'targets' => ['database'],
            'hash' => 'abc123',
            'error' => null,
        ], $this->decodeSingleObject($stdout));
        $this->assertStringContainsString('Creating backup with targets: database', $stderr);
    }

    public function test_failure_keeps_the_shape_and_exits_with_failure(): void
    {
        $this->fakeService(fn (MockInterface $m) => $m->shouldReceive('backup')
            ->once()->andReturn(BackupResultDTO::failure('disk full')));

        [$exit, $stdout] = $this->runSplit(['--targets' => 'database', '--format' => 'json']);

        $this->assertSame(1, $exit);
        $object = $this->decodeSingleObject($stdout);
        $this->assertSame(self::FIELDS, array_keys($object));
        $this->assertFalse($object['success']);
        $this->assertNull($object['backup_record_id']);
        $this->assertNull($object['file_path']);
        $this->assertSame(['database'], $object['targets']);
        $this->assertSame('disk full', $object['error']);
    }

    public function test_an_exception_from_the_service_still_prints_json(): void
    {
        $this->fakeService(fn (MockInterface $m) => $m->shouldReceive('backup')
            ->once()->andThrow(new \RuntimeException('cannot create temp dir')));

        [$exit, $stdout] = $this->runSplit(['--targets' => 'database', '--format' => 'json']);

        $this->assertSame(1, $exit);
        $object = $this->decodeSingleObject($stdout);
        $this->assertFalse($object['success']);
        $this->assertSame('cannot create temp dir', $object['error']);
    }

    public function test_an_invalid_target_prints_json_and_exits_invalid(): void
    {
        $this->fakeService(fn (MockInterface $m) => $m->shouldNotReceive('backup'));

        [$exit, $stdout, $stderr] = $this->runSplit(['--targets' => 'database,bogus', '--format' => 'json']);

        $this->assertSame(2, $exit);
        $object = $this->decodeSingleObject($stdout);
        $this->assertFalse($object['success']);
        $this->assertSame('Invalid target(s): bogus', $object['error']);
        $this->assertSame(['database', 'bogus'], $object['targets']);
        $this->assertStringContainsString('Available targets: database, media', $stderr);
    }

    public function test_an_unknown_format_is_refused_before_any_backup(): void
    {
        $this->fakeService(fn (MockInterface $m) => $m->shouldNotReceive('backup'));

        $this->artisan('dls:backup:create', ['--format' => 'yaml'])
            ->expectsOutputToContain('Unknown format: yaml')
            ->assertExitCode(2);
    }

    public function test_text_output_is_unchanged_by_default(): void
    {
        $this->fakeService(fn (MockInterface $m) => $m->shouldReceive('backup')
            ->once()->andReturn(BackupResultDTO::success(7, '/srv/b.zip', 0, 0.5, ['database'], ['hash' => 'h'])));

        $this->artisan('dls:backup:create', ['--targets' => 'database'])
            ->expectsOutput('Creating backup with targets: database')
            ->expectsOutput('Backup created successfully:')
            ->expectsOutput('  ID:       7')
            ->expectsOutput('  Hash:     h')
            ->assertExitCode(0);
    }

    private function fakeService(callable $expectations): void
    {
        $mock = Mockery::mock(BackupServiceInterface::class);
        $mock->shouldReceive('getAvailableTargets')->andReturn(['database', 'media']);
        $mock->shouldReceive('getDefaultTargets')->andReturn(['database', 'media']);
        $expectations($mock);
        $this->app->instance(BackupServiceInterface::class, $mock);
    }

    /**
     * Run the command with separate stdout and stderr streams, as a shell would.
     *
     * @param  array<string,string>  $options
     * @return array{0:int,1:string,2:string}
     */
    private function runSplit(array $options): array
    {
        $command = $this->app->make(BackupCreateCommand::class);
        $command->setLaravel($this->app);

        $output = new SplitConsoleOutput();
        $exit = $command->run(new ArrayInput($options), $output);

        return [$exit, $output->stdout(), $output->stderr()];
    }

    /**
     * @return array<string,mixed>
     */
    private function decodeSingleObject(string $stdout): array
    {
        $lines = array_values(array_filter(explode("\n", $stdout), fn ($l) => $l !== ''));
        $this->assertCount(1, $lines, "stdout must hold exactly one line:\n".$stdout);
        $decoded = json_decode($lines[0], true, flags: JSON_THROW_ON_ERROR);
        $this->assertIsArray($decoded);

        return $decoded;
    }
}

/**
 * A console output whose stdout and stderr are two in-memory streams.
 */
class SplitConsoleOutput extends StreamOutput implements ConsoleOutputInterface
{
    private OutputInterface $error;

    public function __construct()
    {
        parent::__construct(fopen('php://memory', 'w+'), decorated: false);
        $this->error = new StreamOutput(fopen('php://memory', 'w+'), decorated: false);
    }

    public function getErrorOutput(): OutputInterface
    {
        return $this->error;
    }

    public function setErrorOutput(OutputInterface $error): void
    {
        $this->error = $error;
    }

    public function section(): ConsoleSectionOutput
    {
        throw new \LogicException('Sections are not used by this command.');
    }

    public function stdout(): string
    {
        return $this->read($this);
    }

    public function stderr(): string
    {
        /** @var StreamOutput $error */
        $error = $this->error;

        return $this->read($error);
    }

    private function read(StreamOutput $output): string
    {
        $stream = $output->getStream();
        rewind($stream);

        return (string) stream_get_contents($stream);
    }
}
