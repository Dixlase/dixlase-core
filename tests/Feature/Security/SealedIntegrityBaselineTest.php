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

namespace Tests\Feature\Security;

use App\Models\AuditLog;
use App\Models\FileIntegrityAudit;
use App\Services\Core\CoreIntegrityBaselineRefresher;
use App\Services\FileIntegrityService;
use App\Services\Security\IntegrityKeyring;
use App\Services\Security\IntegritySeal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * The file-integrity baseline is sealed with the integrity key (#803).
 *
 * What is covered is a party that can write files but not run code: it must
 * not be able to replace, strip or delete the baseline without the next
 * scan saying so, and the scan must not adopt altered files on its own.
 *
 * Storage is redirected to `storage/framework/testing/`, so the real
 * baseline is never read or written.
 */
class SealedIntegrityBaselineTest extends TestCase
{
    use RefreshDatabase;

    private string $storage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storage = storage_path('framework/testing/sealed-baseline-'.uniqid());
        File::makeDirectory($this->storage, 0755, true);
        $this->app->useStoragePath($this->storage);
        config(['security.integrity_keys.current' => '', 'security.integrity_keys.previous' => '']);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->storage);

        parent::tearDown();
    }

    public function test_a_keyed_site_writes_a_sealed_baseline(): void
    {
        $this->useKey();
        $service = new FileIntegrityService();

        $this->assertTrue($service->saveBaselineArray($this->smallBaseline()));

        $this->assertSame(IntegritySeal::VALID, $service->baselineSealStatus());
        $this->assertSame(2, $this->readBaseline()['seal']['key_version']);
    }

    public function test_a_site_without_a_key_writes_the_baseline_as_before(): void
    {
        $service = new FileIntegrityService();
        $service->saveBaselineArray($this->smallBaseline());

        $this->assertArrayNotHasKey('seal', $this->readBaseline());
        $this->assertSame(IntegritySeal::UNSEALED, $service->baselineSealStatus());
    }

    public function test_an_edited_baseline_fails_its_seal_and_the_scan_does_not_compare(): void
    {
        $this->useKey();
        $service = new FileIntegrityService();
        $service->saveBaselineArray($this->smallBaseline());

        $data = $this->readBaseline();
        $data['files']['app/Altered.php'] = str_repeat('f', 64);
        $this->writeBaseline($data);

        $this->assertSame(IntegritySeal::INVALID, $service->baselineSealStatus());

        $audit = $service->scanCore();
        $this->assertSame(FileIntegrityAudit::STATUS_CRITICAL, $audit->status);
        $this->assertSame(__('admin/command/integrity.baseline_seal_invalid'), $audit->summary);
        $this->assertNull($audit->result_payload);
    }

    public function test_a_stripped_seal_is_never_better_than_a_warning(): void
    {
        $this->useKey();
        $service = new FileIntegrityService();
        $service->saveBaselineArray($this->smallBaseline());

        $data = $this->readBaseline();
        unset($data['seal']);
        $this->writeBaseline($data);

        $this->assertSame(IntegritySeal::UNSEALED, $service->baselineSealStatus());
        $assessment = $service->assessBaseline();
        $this->assertTrue($assessment['compare']);
        $this->assertSame(FileIntegrityAudit::STATUS_WARNING, $assessment['floor']);

        $audit = $service->scanCore();
        $this->assertNotSame(FileIntegrityAudit::STATUS_OK, $audit->status);
        $this->assertStringStartsWith(__('admin/command/integrity.baseline_unsealed'), (string) $audit->summary);
    }

    public function test_a_seal_naming_an_unconfigured_key_is_critical(): void
    {
        $this->useKey();
        $service = new FileIntegrityService();
        $service->saveBaselineArray($this->smallBaseline());

        $data = $this->readBaseline();
        $data['seal']['key_version'] = 9;
        $this->writeBaseline($data);

        $this->assertSame(IntegritySeal::KEY_UNAVAILABLE, $service->baselineSealStatus());
        $this->assertSame(FileIntegrityAudit::STATUS_CRITICAL, $service->scanCore()->status);
    }

    public function test_another_baseline_cannot_stand_in_for_cores(): void
    {
        $this->useKey();
        $service = new FileIntegrityService();
        $service->saveBaselineArray($this->smallBaseline(), 'plugin_Foo_hashes.json');

        File::copy($this->baselineDir().'/plugin_Foo_hashes.json', $this->baselineDir().'/core_hashes.json');

        $this->assertSame(IntegritySeal::INVALID, $service->baselineSealStatus());
    }

    public function test_a_keyed_site_does_not_recreate_a_missing_baseline(): void
    {
        $this->useKey();
        $service = new FileIntegrityService();

        $audit = $service->scanCore();

        $this->assertSame(FileIntegrityAudit::STATUS_WARNING, $audit->status);
        $this->assertSame(__('admin/command/integrity.baseline_missing_keyed'), $audit->summary);
        $this->assertFalse($service->hasBaseline());
    }

    public function test_a_site_without_a_key_still_creates_the_first_baseline_on_scan(): void
    {
        $service = new FileIntegrityService();

        $audit = $service->scanCore();

        $this->assertSame(FileIntegrityAudit::STATUS_OK, $audit->status);
        $this->assertTrue($service->hasBaseline());
    }

    public function test_sealing_an_existing_baseline_keeps_what_it_says(): void
    {
        $service = new FileIntegrityService();
        $service->saveBaselineArray($this->smallBaseline());
        $before = $this->readBaseline();

        $this->assertFalse($service->sealExistingBaseline(), 'nothing to seal with before a key exists');

        $this->useKey();
        $this->assertTrue($service->sealExistingBaseline());

        $after = $this->readBaseline();
        $this->assertSame(IntegritySeal::VALID, $service->baselineSealStatus());
        $this->assertSame($before['files'], $after['files']);
        $this->assertSame($before['meta'], $after['meta']);
        $this->assertFalse($service->sealExistingBaseline(), 'an already sealed baseline is left alone');
    }

    public function test_an_update_does_not_refresh_an_unsealed_baseline_on_a_keyed_site(): void
    {
        $service = new FileIntegrityService();
        $service->saveBaselineArray($service->generateCoreBaseline());
        $refresher = new CoreIntegrityBaselineRefresher($service);

        // Without a key the unsealed baseline is trusted, as before.
        $this->assertTrue($refresher->treeMatchesBaseline());

        $this->useKey();
        $this->assertFalse($refresher->treeMatchesBaseline());

        $service->sealExistingBaseline();
        $this->assertTrue($refresher->treeMatchesBaseline());
    }

    public function test_the_command_will_not_replace_a_baseline_without_force_and_a_reason(): void
    {
        $this->useKey();
        $service = new FileIntegrityService();
        $service->saveBaselineArray($this->smallBaseline());
        $before = File::get($this->baselineDir().'/core_hashes.json');

        $this->artisan('dls:integrity:generate-baseline')->assertFailed();
        $this->artisan('dls:integrity:generate-baseline', ['--force' => true])->assertFailed();
        $this->assertSame($before, File::get($this->baselineDir().'/core_hashes.json'));
        $this->assertSame(0, AuditLog::where('action', AuditLog::ACTION_INTEGRITY_BASELINE_REGENERATED)->count());

        $this->artisan('dls:integrity:generate-baseline', ['--force' => true, '--reason' => 'Reviewed the 0.2.17 update'])
            ->assertSuccessful();

        $this->assertSame(IntegritySeal::VALID, $service->baselineSealStatus());
        $log = AuditLog::where('action', AuditLog::ACTION_INTEGRITY_BASELINE_REGENERATED)->sole();
        $this->assertSame('Reviewed the 0.2.17 update', $log->context['reason']);
        $this->assertTrue($log->context['sealed']);
    }

    public function test_the_command_creates_a_first_baseline_without_a_reason(): void
    {
        $this->useKey();

        $this->artisan('dls:integrity:generate-baseline')->assertSuccessful();

        $this->assertSame(IntegritySeal::VALID, (new FileIntegrityService())->baselineSealStatus());
        $this->assertSame(0, AuditLog::where('action', AuditLog::ACTION_INTEGRITY_BASELINE_REGENERATED)->count());
    }

    private function useKey(): void
    {
        config(['security.integrity_keys.current' => IntegrityKeyring::generate(2)]);
    }

    /**
     * @return array<string, mixed>
     */
    private function smallBaseline(): array
    {
        return [
            'meta' => ['generated_at' => '2026-11-14T00:00:00+00:00', 'app_version' => '0.0.0-test', 'hash_algo' => 'sha256'],
            'files' => ['app/Example.php' => str_repeat('a', 64)],
        ];
    }

    private function baselineDir(): string
    {
        return $this->storage.'/app/dixlase/security';
    }

    /**
     * @return array<string, mixed>
     */
    private function readBaseline(string $filename = 'core_hashes.json'): array
    {
        return json_decode(File::get($this->baselineDir().'/'.$filename), true);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function writeBaseline(array $data): void
    {
        File::put($this->baselineDir().'/core_hashes.json', json_encode($data, JSON_PRETTY_PRINT));
    }
}
