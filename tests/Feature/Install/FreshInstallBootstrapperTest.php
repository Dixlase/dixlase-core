<?php

declare(strict_types=1);

namespace Tests\Feature\Install;

use App\Contracts\Install\FreshInstallBootstrapperInterface;
use App\Models\FileIntegrityAudit;
use App\Services\AuditLogIntegrityService;
use App\Services\FileIntegrityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Covers the fresh-install bootstrap @api that a provisioner uses to bring a
 * freshly-copied install to a clean audit + integrity state without reaching
 * past the @api boundary into core tables.
 */
class FreshInstallBootstrapperTest extends TestCase
{
    use RefreshDatabase;

    private FreshInstallBootstrapperInterface $bootstrapper;

    private string $baselineFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bootstrapper = app(FreshInstallBootstrapperInterface::class);
        $this->baselineFile = storage_path('app/dixlase/security/core_hashes.json');

        // Start every case without a baseline file; the cases that need one
        // write it explicitly.
        if (File::exists($this->baselineFile)) {
            File::delete($this->baselineFile);
        }
    }

    public function test_it_is_bound_to_the_default_implementation(): void
    {
        $this->assertInstanceOf(
            \App\Services\Install\FreshInstallBootstrapper::class,
            $this->bootstrapper,
        );
    }

    public function test_reset_audit_trail_empties_every_trail_table(): void
    {
        $this->seedAuditTrail();

        // Sanity: the trail is populated before the reset.
        $this->assertGreaterThan(0, DB::table('audit_logs')->count());
        $this->assertGreaterThan(0, DB::table('audit_log_daily_seals')->count());
        $this->assertGreaterThan(0, DB::table('audit_log_key_epochs')->count());
        $this->assertGreaterThan(0, DB::table('audit_log_retention_points')->count());

        $this->bootstrapper->resetAuditTrail();

        $this->assertSame(0, DB::table('audit_logs')->count());
        $this->assertSame(0, DB::table('audit_log_daily_seals')->count());
        $this->assertSame(0, DB::table('audit_log_key_epochs')->count());
        $this->assertSame(0, DB::table('audit_log_retention_points')->count());
    }

    public function test_reset_audit_trail_resets_the_chain_lock_to_a_fresh_install_value(): void
    {
        DB::table('audit_log_chain_lock')->updateOrInsert(['id' => 1], ['generation' => 42]);

        $this->bootstrapper->resetAuditTrail();

        $row = DB::table('audit_log_chain_lock')->where('id', 1)->first();
        $this->assertNotNull($row, 'The single chain-lock row must remain.');
        $this->assertSame(0, (int) $row->generation);
    }

    public function test_reset_audit_trail_leaves_a_state_core_reports_as_healthy(): void
    {
        $this->seedAuditTrail();

        $this->bootstrapper->resetAuditTrail();

        $health = (new AuditLogIntegrityService())->getHealth();
        $this->assertSame(AuditLogIntegrityService::HEALTH_EMPTY, $health['state']);
    }

    public function test_record_core_baseline_audit_writes_a_clean_core_row_from_the_baseline_file(): void
    {
        app(FileIntegrityService::class)->saveBaselineArray([
            'meta' => ['app_version' => '9.9.9', 'hash_algo' => 'sha256'],
            'files' => ['app/A.php' => 'hashA', 'app/B.php' => 'hashB', 'app/C.php' => 'hashC'],
        ]);

        $this->bootstrapper->recordCoreBaselineAudit();

        $row = FileIntegrityAudit::getLatestCore();
        $this->assertNotNull($row);
        $this->assertSame(FileIntegrityAudit::SCOPE_CORE, $row->scope);
        $this->assertSame(FileIntegrityAudit::STATUS_OK, $row->status);
        $this->assertSame('9.9.9', $row->baseline_version);
        $this->assertSame(3, $row->total_files_scanned);
        $this->assertSame(0, $row->duration_ms);
    }

    public function test_record_core_baseline_audit_does_nothing_without_a_baseline_file(): void
    {
        $this->assertFalse(File::exists($this->baselineFile));

        $this->bootstrapper->recordCoreBaselineAudit();

        $this->assertSame(0, FileIntegrityAudit::query()->count());
    }

    /**
     * Populate every audit-trail table with a minimal row via raw inserts,
     * so the test is independent of the audit model's chain-building hooks.
     */
    private function seedAuditTrail(): void
    {
        DB::table('audit_logs')->insert([
            'occurred_at' => now(),
            'category' => 'auth',
            'action' => 'login',
        ]);

        DB::table('audit_log_daily_seals')->insert([
            'seal_date' => now()->toDateString(),
            'first_log_id' => 1,
            'last_log_id' => 1,
            'log_count' => 1,
            'final_hash' => str_repeat('a', 64),
            'daily_signature' => str_repeat('b', 64),
        ]);

        DB::table('audit_log_key_epochs')->insert([
            'key_version' => 1,
            'first_log_id' => 1,
            'signature' => str_repeat('c', 64),
        ]);

        DB::table('audit_log_retention_points')->insert([
            'first_log_id' => 1,
            'previous_hash' => str_repeat('d', 64),
            'deleted_rows' => 0,
            'key_version' => 1,
            'signature' => str_repeat('e', 64),
        ]);
    }
}
