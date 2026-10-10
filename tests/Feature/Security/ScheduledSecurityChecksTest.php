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

namespace Tests\Feature\Security;

use App\DTO\Core\CoreIntegrityResult;
use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\CheckMenuAccess;
use App\Http\Middleware\CheckMenuEdit;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Models\AuditLog;
use App\Models\Member;
use App\Models\Plugin;
use App\Models\PluginAudit;
use App\Models\SiteSetting;
use App\Services\Core\CoreIntegrityVerifier;
use App\Services\Extension\ExtensionRescanService;
use App\Services\Security\ScheduledSecurityCheckMonitor;
use App\Services\SystemNotificationService;
use App\Services\SystemWarningService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Mockery;
use Tests\TestCase;

/**
 * Scheduled security checks (#493): the daily plugin/theme rescan, audit log
 * verification and core signed-manifest check are scheduled, a failing result
 * raises an admin banner, and a passing result clears it.
 */
class ScheduledSecurityChecksTest extends TestCase
{
    use RefreshDatabase;

    private string $stateDir;

    /** @var Mockery\MockInterface */
    private $notifications;

    protected function setUp(): void
    {
        parent::setUp();

        // Keep the alert state in a throwaway directory, never in the real storage/app.
        $this->stateDir = storage_path('framework/testing/scheduled-checks-'.uniqid());
        config(['security.scheduled_checks.state_file' => $this->stateDir.'/state.json']);

        $this->notifications = Mockery::mock(SystemNotificationService::class);
        $this->notifications->shouldReceive('sendAdminNotification')->andReturn(true)->byDefault();
        // The log channel's notification handler asks this; keep it quiet.
        $this->notifications->shouldReceive('isNotificationEnabled')->andReturn(false)->byDefault();
        $this->app->instance(SystemNotificationService::class, $this->notifications);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->stateDir);
        putenv('INSTALLED=false');
        $_ENV['INSTALLED'] = 'false';

        parent::tearDown();
    }

    private function monitor(): ScheduledSecurityCheckMonitor
    {
        return $this->app->make(ScheduledSecurityCheckMonitor::class);
    }

    // ------------------------------------------------------------------
    // Schedule
    // ------------------------------------------------------------------

    public function test_the_three_checks_are_scheduled_daily_off_the_existing_slots(): void
    {
        $this->app->make(Kernel::class)->bootstrap();

        $expressions = [];
        foreach ($this->app->make(Schedule::class)->events() as $event) {
            foreach (['audit:integrity verify', 'dls:core:verify', 'dls:extensions:rescan', 'audit:integrity seal', 'dls:integrity:scan'] as $needle) {
                if (is_string($event->command) && str_contains($event->command, $needle)) {
                    $expressions[$needle] = $event->expression;
                    $this->assertTrue($event->withoutOverlapping, "{$needle} must not overlap itself");
                }
            }
        }

        $this->assertSame('45 3 * * *', $expressions['audit:integrity verify'] ?? null);
        $this->assertSame('15 4 * * *', $expressions['dls:core:verify'] ?? null);
        $this->assertSame('30 4 * * *', $expressions['dls:extensions:rescan'] ?? null);

        // The new jobs do not share a slot with the existing integrity jobs.
        $this->assertSame(
            count($expressions),
            count(array_unique($expressions)),
            'Each integrity job should run in its own time slot'
        );
    }

    // ------------------------------------------------------------------
    // Core manifest
    // ------------------------------------------------------------------

    public function test_a_failing_core_manifest_check_raises_a_banner_and_a_passing_one_clears_it(): void
    {
        $verifier = Mockery::mock(CoreIntegrityVerifier::class);
        $verifier->shouldReceive('verify')->once()->andReturn(CoreIntegrityResult::invalid('Core manifest signature is invalid.'));
        $this->app->instance(CoreIntegrityVerifier::class, $verifier);
        $this->notifications->shouldReceive('sendAdminNotification')->once()->andReturn(true);

        $this->artisan('dls:core:verify')->assertExitCode(1);

        $alerts = $this->monitor()->alerts();
        $this->assertArrayHasKey(ScheduledSecurityCheckMonitor::KEY_CORE_MANIFEST, $alerts);
        $this->assertSame(CoreIntegrityResult::STATUS_INVALID, $this->monitor()->latestCoreManifestCheck()['status'] ?? null);

        $banners = $this->monitor()->buildBanners($alerts, false);
        $this->assertCount(1, $banners);
        $this->assertSame('error', $banners[0]['level']);
        $this->assertSame(__('admin/settings/security/integrity.scheduled.core_manifest_title'), $banners[0]['title']);

        $verifier = Mockery::mock(CoreIntegrityVerifier::class);
        $verifier->shouldReceive('verify')->once()->andReturn(CoreIntegrityResult::genuine('key-1', 'official', null, '0.1.8'));
        $this->app->instance(CoreIntegrityVerifier::class, $verifier);

        $this->artisan('dls:core:verify')->assertExitCode(0);

        $this->assertSame([], $this->monitor()->alerts());
        $this->assertSame(CoreIntegrityResult::STATUS_GENUINE, $this->monitor()->latestCoreManifestCheck()['status'] ?? null);
    }

    public function test_a_locally_modified_core_is_recorded_but_does_not_raise_a_banner(): void
    {
        $verifier = Mockery::mock(CoreIntegrityVerifier::class);
        $verifier->shouldReceive('verify')->once()->andReturn(
            CoreIntegrityResult::modified('key-1', 'official', null, '0.1.8', ['app/Foo.php'], [], [])
        );
        $this->app->instance(CoreIntegrityVerifier::class, $verifier);
        $this->notifications->shouldNotReceive('sendAdminNotification');

        $this->artisan('dls:core:verify')->assertExitCode(0);

        $this->assertSame([], $this->monitor()->alerts());
        $this->assertSame(1, $this->monitor()->latestCoreManifestCheck()['changed_count'] ?? null);
    }

    // ------------------------------------------------------------------
    // Audit log
    // ------------------------------------------------------------------

    public function test_a_failing_audit_verification_raises_a_banner_and_a_passing_one_clears_it(): void
    {
        $logs = $this->chainedLogs(3);
        AuditLog::where('id', $logs[1]->id)->update(['action' => 'tampered']);

        $this->notifications->shouldReceive('sendAdminNotification')->once()->andReturn(true);

        $this->artisan('audit:integrity verify --all')->assertExitCode(1);
        // A second failing run keeps the alert but does not mail again.
        $this->artisan('audit:integrity verify --all')->assertExitCode(1);

        $alerts = $this->monitor()->alerts();
        $this->assertArrayHasKey(ScheduledSecurityCheckMonitor::KEY_AUDIT_CHAIN, $alerts);
        $this->assertGreaterThan(0, $alerts[ScheduledSecurityCheckMonitor::KEY_AUDIT_CHAIN]['tampered']);
        $this->assertSame('error', $this->monitor()->buildBanners($alerts, false)[0]['level']);

        // Resolution: a clean log verifies again and the alert clears.
        AuditLog::query()->delete();
        $this->chainedLogs(2);

        $this->artisan('audit:integrity verify --all')->assertExitCode(0);

        $this->assertArrayNotHasKey(ScheduledSecurityCheckMonitor::KEY_AUDIT_CHAIN, $this->monitor()->alerts());
    }

    public function test_a_partial_audit_verification_does_not_touch_the_alert(): void
    {
        $this->monitor()->recordAuditVerification(false, 1, 0);
        $logs = $this->chainedLogs(2);

        $this->artisan("audit:integrity verify --from={$logs[0]->id} --to={$logs[1]->id}")->assertExitCode(0);

        $this->assertArrayHasKey(ScheduledSecurityCheckMonitor::KEY_AUDIT_CHAIN, $this->monitor()->alerts());
    }

    // ------------------------------------------------------------------
    // Extension rescan
    // ------------------------------------------------------------------

    public function test_a_rescan_that_worsens_a_plugin_raises_a_banner_and_recovery_clears_it(): void
    {
        $this->enabledPlugin('scan-target');
        $this->audit('scan-target', 'healthy', 'valid');

        // First scheduled run: unchanged, establishes the snapshot.
        $this->fakeRescan('scan-target', 'healthy', 'valid');
        $this->artisan('dls:extensions:rescan')->assertExitCode(0);
        $this->assertSame([], $this->monitor()->alerts());

        // Second run: files changed — health and signature both got worse.
        $this->notifications->shouldReceive('sendAdminNotification')->once()->andReturn(true);
        $this->fakeRescan('scan-target', 'needs_attention', 'invalid');
        $this->artisan('dls:extensions:rescan')->assertExitCode(0);

        $alerts = $this->monitor()->extensionAlerts();
        $key = ScheduledSecurityCheckMonitor::extensionKey('plugin', 'scan-target');
        $this->assertArrayHasKey($key, $alerts);
        $this->assertSame(['health', 'signature'], $alerts[$key]['reasons']);

        $banners = $this->monitor()->buildBanners($this->monitor()->alerts(), true);
        $this->assertCount(1, $banners);
        $this->assertSame('error', $banners[0]['level'], 'A signature failure is an error, not a warning');
        $this->assertCount(2, $banners[0]['actions'], 'Editors get the acknowledge action');

        // Third run: still bad — the alert persists (no repeat mail, checked by once() above).
        $this->fakeRescan('scan-target', 'needs_attention', 'invalid');
        $this->artisan('dls:extensions:rescan')->assertExitCode(0);
        $this->assertArrayHasKey($key, $this->monitor()->extensionAlerts());

        // Fourth run: restored — the alert clears.
        $this->fakeRescan('scan-target', 'healthy', 'valid');
        $this->artisan('dls:extensions:rescan')->assertExitCode(0);
        $this->assertSame([], $this->monitor()->alerts());
    }

    public function test_a_manual_rescan_between_scheduled_runs_does_not_hide_a_worsening(): void
    {
        $this->enabledPlugin('scan-target');
        $this->audit('scan-target', 'healthy', 'valid');
        $this->fakeRescan('scan-target', 'healthy', 'valid');
        $this->artisan('dls:extensions:rescan');

        // The admin "Rescan" button already wrote the worse status to the audit row.
        $this->audit('scan-target', 'advisory', 'valid');
        $this->fakeRescan('scan-target', 'advisory', 'valid');
        $this->artisan('dls:extensions:rescan');

        $alerts = $this->monitor()->extensionAlerts();
        $this->assertSame(['health'], $alerts[ScheduledSecurityCheckMonitor::extensionKey('plugin', 'scan-target')]['reasons'] ?? null);
        $this->assertSame('warning', $this->monitor()->buildBanners($this->monitor()->alerts(), false)[0]['level']);
    }

    public function test_acknowledging_accepts_the_current_status_as_the_new_baseline(): void
    {
        $this->enabledPlugin('scan-target');
        $this->audit('scan-target', 'healthy', 'valid');
        $this->fakeRescan('scan-target', 'healthy', 'valid');
        $this->artisan('dls:extensions:rescan');
        $this->fakeRescan('scan-target', 'advisory', 'valid');
        $this->artisan('dls:extensions:rescan');
        $this->assertNotSame([], $this->monitor()->extensionAlerts());

        $this->monitor()->acknowledgeExtensions();
        $this->assertSame([], $this->monitor()->alerts());

        // The next run compares against the accepted status, so no new alert.
        $this->fakeRescan('scan-target', 'advisory', 'valid');
        $this->artisan('dls:extensions:rescan');
        $this->assertSame([], $this->monitor()->alerts());
    }

    public function test_alerts_for_extensions_that_are_no_longer_enabled_are_dropped(): void
    {
        $this->monitor()->recordExtensionRescan('plugin', 'gone', 'Gone', ['health_status' => 'healthy'], ['health_status' => 'needs_attention']);
        $this->assertNotSame([], $this->monitor()->extensionAlerts());

        $rescan = Mockery::mock(ExtensionRescanService::class);
        $rescan->shouldNotReceive('rescanPlugin');
        $this->app->instance(ExtensionRescanService::class, $rescan);

        $this->artisan('dls:extensions:rescan')->assertExitCode(0);

        $this->assertSame([], $this->monitor()->alerts());
    }

    // ------------------------------------------------------------------
    // Banner delivery
    // ------------------------------------------------------------------

    public function test_the_system_warning_service_shows_the_banner_only_to_authorized_members(): void
    {
        $this->monitor()->recordAuditVerification(false, 2, 0);

        // No one logged in: nothing is exposed.
        $this->assertSame([], $this->app->make(SystemWarningService::class)->getActiveBanners());

        $this->actingAs($this->superAdmin(), 'member');
        $banners = $this->app->make(SystemWarningService::class)->getActiveBanners();

        $this->assertCount(1, $banners);
        $this->assertSame(__('admin/settings/security/integrity.scheduled.audit_chain_title'), $banners[0]['title']);
    }

    public function test_the_integrity_screen_shows_the_core_manifest_check_and_the_banner(): void
    {
        $this->prepareAdminRequest();

        $this->monitor()->recordCoreManifest(CoreIntegrityResult::invalid('Core manifest signature is invalid.'));

        $response = $this->actingAs($this->superAdmin(), 'member')
            ->get(route('admin.settings.security.integrity'));

        $response->assertOk();
        $response->assertSee(__('admin/settings/security/integrity.scheduled.core_manifest_heading'));
        $response->assertSee(__('admin/settings/security/integrity.scheduled.core_status.invalid'));
        $response->assertSee(__('admin/settings/security/integrity.scheduled.core_manifest_title'));
    }

    public function test_the_acknowledge_action_clears_extension_alerts(): void
    {
        $this->prepareAdminRequest();
        $this->monitor()->recordExtensionRescan('plugin', 'scan-target', 'Scan Target', ['health_status' => 'healthy'], ['health_status' => 'advisory']);
        $this->monitor()->recordAuditVerification(false, 1, 0);

        $this->actingAs($this->superAdmin(), 'member')
            ->post(route('admin.settings.security.integrity.acknowledge-extensions'))
            ->assertRedirect(route('admin.settings.security.integrity'));

        $this->assertSame([], $this->monitor()->extensionAlerts());
        // Only extension alerts can be acknowledged; the audit alert stays until it verifies.
        $this->assertArrayHasKey(ScheduledSecurityCheckMonitor::KEY_AUDIT_CHAIN, $this->monitor()->alerts());
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function prepareAdminRequest(): void
    {
        $this->withoutMiddleware([
            CheckInstallationReady::class,
            CheckMenuAccess::class,
            CheckMenuEdit::class,
            EnsureEmailIsVerified::class,
        ]);
        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';
        $adminTheme = config('themes.admin_theme', 'admin');
        View::addNamespace('admin', [resource_path("views/{$adminTheme}")]);
        SiteSetting::setValue('site_name', 'Test Site');
    }

    private function enabledPlugin(string $slug): Plugin
    {
        return Plugin::create([
            'name' => 'Scan Target',
            'directory' => 'ScanTarget',
            'slug' => $slug,
            'namespace' => 'Plugins\\ScanTarget',
            'version' => '1.0.0',
            'installed_at' => now(),
            'enabled_at' => now(),
        ]);
    }

    private function audit(string $slug, string $health, string $signature): void
    {
        PluginAudit::updateOrCreate(
            ['plugin_slug' => $slug],
            ['health_status' => $health, 'signature_status' => $signature, 'audited_at' => now()],
        );
    }

    /**
     * Stand in for the full audit (which would read the real plugins/ tree):
     * the rescan just writes the given status to the audit row.
     */
    private function fakeRescan(string $slug, string $health, string $signature): void
    {
        $rescan = Mockery::mock(ExtensionRescanService::class);
        $rescan->shouldReceive('rescanPlugin')->with($slug)->andReturnUsing(function () use ($slug, $health, $signature): array {
            $this->audit($slug, $health, $signature);

            return [];
        });
        $this->app->instance(ExtensionRescanService::class, $rescan);
    }

    private function superAdmin(): Member
    {
        return Member::create([
            'account_name' => 'secadmin',
            'display_name' => 'Security Admin',
            'email' => 'secadmin@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => MemberRole::SUPER_ADMIN,
            'status' => MemberStatus::Active,
        ]);
    }

    /**
     * @return array<int, AuditLog>
     */
    private function chainedLogs(int $count): array
    {
        $logs = [];
        for ($i = 0; $i < $count; $i++) {
            $log = AuditLog::create([
                'occurred_at' => now()->addMinutes($i),
                'severity' => AuditLog::SEVERITY_INFO,
                'outcome' => AuditLog::OUTCOME_SUCCESS,
                'category' => AuditLog::CATEGORY_AUTH,
                'action' => AuditLog::ACTION_LOGIN,
                'ip_address' => '127.0.0.1',
                'context' => [],
            ]);
            $log->saveWithHashChain();
            $logs[] = $log;
        }

        return $logs;
    }
}
