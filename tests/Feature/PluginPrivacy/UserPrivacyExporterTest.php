<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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
 *       (see LICENSE.commercial, or contact office@exc-d.com).
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

namespace Tests\Feature\PluginPrivacy;

use App\Contracts\PluginIntegration\PrivacyDataProviderInterface;
use App\DTO\PluginPrivacy\UserDataExportDTO;
use App\Enums\PluginPrivacy\DeletionMode;
use App\Services\Plugin\PluginServiceResolver;
use App\Services\Privacy\UserPrivacyExporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class UserPrivacyExporterTest extends TestCase
{
    use RefreshDatabase;

    public function test_aggregates_multiple_providers_into_a_single_zip(): void
    {
        $this->registerMockProvider('core-mock-a', [
            'first_table' => [['id' => 1, 'note' => 'alpha']],
        ]);
        $this->registerMockProvider('core-mock-b', [
            'second_table' => [['id' => 2, 'note' => 'beta']],
        ]);

        $exporter = $this->app->make(UserPrivacyExporter::class);
        $zipPath = $exporter->exportToZip(42, null);

        $this->assertFileExists($zipPath);

        $contents = $this->openZip($zipPath);

        $this->assertArrayHasKey('manifest.json', $contents);
        $this->assertArrayHasKey('core-mock-a/data.json', $contents);
        $this->assertArrayHasKey('core-mock-b/data.json', $contents);

        $manifest = json_decode($contents['manifest.json'], true);
        $this->assertSame(42, $manifest['user_id']);
        $this->assertNull($manifest['site_id']);
        $this->assertSame('network', $manifest['scope']);

        $providerKeys = array_column($manifest['providers'], 'provider_key');
        $this->assertContains('core-mock-a', $providerKeys);
        $this->assertContains('core-mock-b', $providerKeys);

        $dataA = json_decode($contents['core-mock-a/data.json'], true);
        $this->assertSame('alpha', $dataA['first_table'][0]['note']);

        $dataB = json_decode($contents['core-mock-b/data.json'], true);
        $this->assertSame('beta', $dataB['second_table'][0]['note']);

        @unlink($zipPath);
    }

    public function test_provider_exception_is_recorded_in_manifest_without_aborting_the_run(): void
    {
        $this->registerMockProvider('core-mock-good', ['data' => ['ok']]);
        $this->registerThrowingProvider('core-mock-bad', new \RuntimeException('mock failure'));

        $exporter = $this->app->make(UserPrivacyExporter::class);
        $zipPath = $exporter->exportToZip(99, null);

        $contents = $this->openZip($zipPath);
        $manifest = json_decode($contents['manifest.json'], true);

        $providerKeys = array_column($manifest['providers'], 'provider_key');
        $this->assertContains('core-mock-good', $providerKeys);
        $this->assertArrayHasKey('core-mock-good/data.json', $contents);

        $errorSlugs = array_column($manifest['errors'], 'plugin_slug');
        $this->assertContains('core-mock-bad', $errorSlugs);

        @unlink($zipPath);
    }

    public function test_files_field_attaches_binary_entries_under_provider_directory(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'privacy_test_');
        file_put_contents($tmpFile, 'binary-payload');

        $this->registerMockProvider('core-mock-files', ['note' => 'has files'], [
            'avatar.bin' => $tmpFile,
        ]);

        $exporter = $this->app->make(UserPrivacyExporter::class);
        $zipPath = $exporter->exportToZip(7, null);

        $contents = $this->openZip($zipPath);
        $this->assertArrayHasKey('core-mock-files/files/avatar.bin', $contents);
        $this->assertSame('binary-payload', $contents['core-mock-files/files/avatar.bin']);

        @unlink($zipPath);
        @unlink($tmpFile);
    }

    public function test_site_scoped_export_records_scope_in_manifest(): void
    {
        $this->registerMockProvider('core-mock-scoped', ['scope_field' => 'present']);

        $exporter = $this->app->make(UserPrivacyExporter::class);
        $zipPath = $exporter->exportToZip(1, 5);

        $contents = $this->openZip($zipPath);
        $manifest = json_decode($contents['manifest.json'], true);

        $this->assertSame(5, $manifest['site_id']);
        $this->assertSame('site', $manifest['scope']);

        @unlink($zipPath);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, string>  $files
     */
    private function registerMockProvider(string $key, array $data, array $files = []): void
    {
        $provider = new class($key, $data, $files) implements PrivacyDataProviderInterface
        {
            public function __construct(
                private readonly string $key,
                /** @var array<string, mixed> */
                private readonly array $data,
                /** @var array<string, string> */
                private readonly array $files,
            ) {}

            public function getPluginSlug(): string
            {
                return $this->key;
            }

            public function isCapabilityAvailable(): bool
            {
                return true;
            }

            public function privacyProviderKey(): string
            {
                return $this->key;
            }

            public function privacyDataDescription(): array
            {
                return ['en' => "Mock {$this->key}", 'ja' => "モック {$this->key}"];
            }

            public function exportUserData(int $userId, ?int $siteId = null): UserDataExportDTO
            {
                return new UserDataExportDTO(
                    providerKey: $this->key,
                    data: $this->data,
                    files: $this->files,
                );
            }

            public function deleteUserData(int $userId, DeletionMode $mode, ?int $siteId = null): \App\DTO\PluginPrivacy\UserDataDeletionDTO
            {
                return new \App\DTO\PluginPrivacy\UserDataDeletionDTO(
                    providerKey: $this->key,
                    mode: $mode,
                );
            }
        };

        // Anonymous classes from the same `new class` statement share
        // a class string, so binding by raw class would overwrite earlier
        // mocks. Bind under a synthetic per-slug key instead.
        $bindingKey = $provider::class.'#'.$key;
        $this->app->instance($bindingKey, $provider);
        $this->app->tag([$bindingKey], PluginServiceResolver::CAPABILITY_TAG);
    }

    private function registerThrowingProvider(string $key, \Throwable $exception): void
    {
        $provider = new class($key, $exception) implements PrivacyDataProviderInterface
        {
            public function __construct(
                private readonly string $key,
                private readonly \Throwable $exception,
            ) {}

            public function getPluginSlug(): string
            {
                return $this->key;
            }

            public function isCapabilityAvailable(): bool
            {
                return true;
            }

            public function privacyProviderKey(): string
            {
                return $this->key;
            }

            public function privacyDataDescription(): array
            {
                return ['en' => "Throwing {$this->key}"];
            }

            public function exportUserData(int $userId, ?int $siteId = null): UserDataExportDTO
            {
                throw $this->exception;
            }

            public function deleteUserData(int $userId, DeletionMode $mode, ?int $siteId = null): \App\DTO\PluginPrivacy\UserDataDeletionDTO
            {
                throw $this->exception;
            }
        };

        $bindingKey = $provider::class.'#'.$key;
        $this->app->instance($bindingKey, $provider);
        $this->app->tag([$bindingKey], PluginServiceResolver::CAPABILITY_TAG);
    }

    /**
     * @return array<string, string>
     */
    private function openZip(string $path): array
    {
        $zip = new ZipArchive();
        $zip->open($path);

        $entries = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            $entries[$name] = (string) $zip->getFromName($name);
        }
        $zip->close();

        return $entries;
    }
}
