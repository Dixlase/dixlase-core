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
use App\DTO\PluginPrivacy\UserDataDeletionDTO;
use App\DTO\PluginPrivacy\UserDataExportDTO;
use App\Enums\PluginPrivacy\DeletionMode;
use App\Services\Plugin\PluginServiceResolver;
use App\Services\Privacy\UserPrivacyEraser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UserPrivacyEraserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: DeletionMode}>
     */
    public static function deletionModeProvider(): array
    {
        return [
            'hard delete' => [DeletionMode::HardDelete],
            'soft delete' => [DeletionMode::SoftDelete],
            'anonymize' => [DeletionMode::Anonymize],
        ];
    }

    #[DataProvider('deletionModeProvider')]
    public function test_dispatches_deletion_to_every_resolved_provider(DeletionMode $mode): void
    {
        $this->registerMockProvider('core-mock-x', deletedRecords: 5);
        $this->registerMockProvider('core-mock-y', deletedRecords: 3, anonymizedRecords: 2);

        $eraser = $this->app->make(UserPrivacyEraser::class);
        $results = $eraser->erase(123, $mode, null);

        $byKey = [];
        foreach ($results as $r) {
            $byKey[$r->providerKey] = $r;
        }

        $this->assertArrayHasKey('core-mock-x', $byKey);
        $this->assertArrayHasKey('core-mock-y', $byKey);

        $this->assertSame($mode, $byKey['core-mock-x']->mode);
        $this->assertSame(5, $byKey['core-mock-x']->deletedRecords);
        $this->assertSame([], $byKey['core-mock-x']->errors);

        $this->assertSame(3, $byKey['core-mock-y']->deletedRecords);
        $this->assertSame(2, $byKey['core-mock-y']->anonymizedRecords);
    }

    public function test_provider_exception_does_not_abort_the_run(): void
    {
        $this->registerMockProvider('core-mock-survives', deletedRecords: 1);
        $this->registerThrowingProvider('core-mock-fails', new \RuntimeException('boom'));

        $eraser = $this->app->make(UserPrivacyEraser::class);
        $results = $eraser->erase(7, DeletionMode::HardDelete, null);

        $byKey = [];
        foreach ($results as $r) {
            $byKey[$r->providerKey] = $r;
        }

        $this->assertArrayHasKey('core-mock-survives', $byKey);
        $this->assertSame(1, $byKey['core-mock-survives']->deletedRecords);
        $this->assertSame([], $byKey['core-mock-survives']->errors);

        $this->assertArrayHasKey('core-mock-fails', $byKey);
        $this->assertNotEmpty($byKey['core-mock-fails']->errors);
        $this->assertStringContainsString('boom', $byKey['core-mock-fails']->errors[0]);
    }

    public function test_provider_receives_the_site_id_argument(): void
    {
        $captured = (object) ['siteId' => 'unset'];
        $this->registerCapturingProvider('core-mock-captures', $captured);

        $eraser = $this->app->make(UserPrivacyEraser::class);
        $eraser->erase(99, DeletionMode::Anonymize, 7);

        $this->assertSame(7, $captured->siteId);
    }

    private function registerMockProvider(
        string $key,
        int $deletedRecords = 0,
        int $anonymizedRecords = 0,
    ): void {
        $provider = new class($key, $deletedRecords, $anonymizedRecords) implements PrivacyDataProviderInterface
        {
            public function __construct(
                private readonly string $key,
                private readonly int $deletedRecords,
                private readonly int $anonymizedRecords,
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
                return ['en' => "Eraser mock {$this->key}"];
            }

            public function exportUserData(int $userId, ?int $siteId = null): UserDataExportDTO
            {
                return new UserDataExportDTO(providerKey: $this->key);
            }

            public function deleteUserData(int $userId, DeletionMode $mode, ?int $siteId = null): UserDataDeletionDTO
            {
                return new UserDataDeletionDTO(
                    providerKey: $this->key,
                    mode: $mode,
                    deletedRecords: $this->deletedRecords,
                    anonymizedRecords: $this->anonymizedRecords,
                );
            }
        };

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
                return ['en' => "Throwing eraser mock {$this->key}"];
            }

            public function exportUserData(int $userId, ?int $siteId = null): UserDataExportDTO
            {
                throw $this->exception;
            }

            public function deleteUserData(int $userId, DeletionMode $mode, ?int $siteId = null): UserDataDeletionDTO
            {
                throw $this->exception;
            }
        };

        $bindingKey = $provider::class.'#'.$key;
        $this->app->instance($bindingKey, $provider);
        $this->app->tag([$bindingKey], PluginServiceResolver::CAPABILITY_TAG);
    }

    private function registerCapturingProvider(string $key, object $sink): void
    {
        $provider = new class($key, $sink) implements PrivacyDataProviderInterface
        {
            public function __construct(
                private readonly string $key,
                private readonly object $sink,
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
                return ['en' => "Capturing mock {$this->key}"];
            }

            public function exportUserData(int $userId, ?int $siteId = null): UserDataExportDTO
            {
                return new UserDataExportDTO(providerKey: $this->key);
            }

            public function deleteUserData(int $userId, DeletionMode $mode, ?int $siteId = null): UserDataDeletionDTO
            {
                $this->sink->siteId = $siteId;

                return new UserDataDeletionDTO(providerKey: $this->key, mode: $mode);
            }
        };

        $bindingKey = $provider::class.'#'.$key;
        $this->app->instance($bindingKey, $provider);
        $this->app->tag([$bindingKey], PluginServiceResolver::CAPABILITY_TAG);
    }
}
