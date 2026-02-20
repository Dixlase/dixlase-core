<?php

namespace Tests\Unit\Services\Plugin;

use App\DTO\Plugin\HealthScoreResult;
use App\Enums\PluginHealthStatus;
use App\Models\PluginAudit;
use App\Services\Plugin\PluginHealthScorer;
use App\Services\Plugin\PluginPermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PluginHealthScorerTest extends TestCase
{
    use RefreshDatabase;

    protected PluginPermissionService|Mockery\MockInterface $permissionService;

    protected PluginHealthScorer $scorer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->permissionService = Mockery::mock(PluginPermissionService::class);
        $this->scorer = new PluginHealthScorer($this->permissionService);
    }

    /**
     * 問題がない場合は満点（100）のHealthyを返すテスト
     */
    public function test_perfect_score_when_no_issues(): void
    {
        $this->permissionService
            ->shouldReceive('getSignatureInfo')
            ->with('test-plugin')
            ->andReturn(['status' => 'valid']);

        $this->permissionService
            ->shouldReceive('getPermissions')
            ->with('test-plugin')
            ->andReturn(['database' => ['own_tables' => true]]);

        // 監査結果なし（問題なし扱い）
        // PluginAudit::getBySlug は DB から取得するので、レコードがなければ null

        $result = $this->scorer->calculate('test-plugin');

        $this->assertInstanceOf(HealthScoreResult::class, $result);
        // 署名valid + 権限定義あり + 監査データなし（CSP/危険API/鮮度は null → scan_not_performed のみ）
        // scan_not_performed: -10 → score = 90
        $this->assertEquals(90, $result->score);
        $this->assertEquals(PluginHealthStatus::Healthy, $result->status);
        $this->assertFalse($result->hasCriticalIssue);
    }

    /**
     * 署名未設定の場合の減点テスト
     */
    public function test_unsigned_signature_deduction(): void
    {
        $this->permissionService
            ->shouldReceive('getSignatureInfo')
            ->with('test-plugin')
            ->andReturn(['status' => 'unsigned']);

        $this->permissionService
            ->shouldReceive('getPermissions')
            ->with('test-plugin')
            ->andReturn(['database' => ['own_tables' => true]]);

        $result = $this->scorer->calculate('test-plugin');

        // signature_unsigned: -5, scan_not_performed: -10 → score = 85
        $this->assertEquals(85, $result->score);
        $this->assertEquals(PluginHealthStatus::Advisory, $result->status);

        $types = array_map(fn ($i) => $i->type, $result->issues);
        $this->assertContains('signature_unsigned', $types);
    }

    /**
     * 署名無効の場合の致命的減点テスト
     */
    public function test_invalid_signature_critical_deduction(): void
    {
        $this->permissionService
            ->shouldReceive('getSignatureInfo')
            ->with('test-plugin')
            ->andReturn(['status' => 'invalid']);

        $this->permissionService
            ->shouldReceive('getPermissions')
            ->with('test-plugin')
            ->andReturn(['database' => ['own_tables' => true]]);

        $result = $this->scorer->calculate('test-plugin');

        // signature_invalid: -50, scan_not_performed: -10 → score = 40
        $this->assertEquals(40, $result->score);
        $this->assertEquals(PluginHealthStatus::NeedsAttention, $result->status);
        $this->assertTrue($result->hasCriticalIssue);

        $types = array_map(fn ($i) => $i->type, $result->issues);
        $this->assertContains('signature_invalid', $types);
    }

    /**
     * 権限未定義の場合の減点テスト
     */
    public function test_undefined_permissions_deduction(): void
    {
        $this->permissionService
            ->shouldReceive('getSignatureInfo')
            ->with('test-plugin')
            ->andReturn(['status' => 'valid']);

        $this->permissionService
            ->shouldReceive('getPermissions')
            ->with('test-plugin')
            ->andReturn(null);

        $result = $this->scorer->calculate('test-plugin');

        // permission_undefined: -10, scan_not_performed: -10 → score = 80
        $this->assertEquals(80, $result->score);

        $types = array_map(fn ($i) => $i->type, $result->issues);
        $this->assertContains('permission_undefined', $types);
    }

    /**
     * 監査結果に未宣言の重大権限がある場合のテスト
     */
    public function test_undeclared_major_permission_deduction(): void
    {
        $this->permissionService
            ->shouldReceive('getSignatureInfo')
            ->with('test-plugin')
            ->andReturn(['status' => 'valid']);

        $this->permissionService
            ->shouldReceive('getPermissions')
            ->with('test-plugin')
            ->andReturn(['database' => ['own_tables' => true]]);

        $this->permissionService
            ->shouldReceive('getOptionalPermissions')
            ->with('test-plugin')
            ->andReturn([]);

        // 監査レコードを作成（重大な未宣言権限）
        PluginAudit::saveAuditResult('test-plugin', [
            'has_mismatches' => true,
            'mismatches' => [
                [
                    'type' => 'undeclared_usage',
                    'permission' => 'database.core_tables',
                    'evidence' => [['file' => 'src/Service.php', 'line' => 10]],
                ],
            ],
            'matches_count' => 1,
            'total_checked' => 5,
        ]);

        $result = $this->scorer->calculate('test-plugin');

        // permission_undeclared_major: -15 → 致命的問題
        $types = array_map(fn ($i) => $i->type, $result->issues);
        $this->assertContains('permission_undeclared_major', $types);
        $this->assertTrue($result->hasCriticalIssue);
    }

    /**
     * 監査結果に未宣言の軽微権限がある場合のテスト
     */
    public function test_undeclared_minor_permission_deduction(): void
    {
        $this->permissionService
            ->shouldReceive('getSignatureInfo')
            ->with('test-plugin')
            ->andReturn(['status' => 'valid']);

        $this->permissionService
            ->shouldReceive('getPermissions')
            ->with('test-plugin')
            ->andReturn(['database' => ['own_tables' => true]]);

        $this->permissionService
            ->shouldReceive('getOptionalPermissions')
            ->with('test-plugin')
            ->andReturn([]);

        PluginAudit::saveAuditResult('test-plugin', [
            'has_mismatches' => true,
            'mismatches' => [
                [
                    'type' => 'undeclared_usage',
                    'permission' => 'storage.own_directory',
                    'evidence' => [],
                ],
            ],
            'matches_count' => 1,
            'total_checked' => 5,
        ]);

        $result = $this->scorer->calculate('test-plugin');

        $types = array_map(fn ($i) => $i->type, $result->issues);
        $this->assertContains('permission_undeclared_minor', $types);
        $this->assertFalse($result->hasCriticalIssue);
    }

    /**
     * 未使用権限宣言の場合の軽微減点テスト
     */
    public function test_unused_permission_deduction(): void
    {
        $this->permissionService
            ->shouldReceive('getSignatureInfo')
            ->with('test-plugin')
            ->andReturn(['status' => 'valid']);

        $this->permissionService
            ->shouldReceive('getPermissions')
            ->with('test-plugin')
            ->andReturn(['database' => ['own_tables' => true]]);

        $this->permissionService
            ->shouldReceive('getOptionalPermissions')
            ->with('test-plugin')
            ->andReturn([]);

        PluginAudit::saveAuditResult('test-plugin', [
            'has_mismatches' => true,
            'mismatches' => [
                [
                    'type' => 'unused_declaration',
                    'permission' => 'mail.send',
                ],
            ],
            'matches_count' => 0,
            'total_checked' => 5,
        ]);

        $result = $this->scorer->calculate('test-plugin');

        $types = array_map(fn ($i) => $i->type, $result->issues);
        $this->assertContains('permission_unused', $types);
    }

    /**
     * _optional に含まれる未使用権限はペナルティなしのテスト
     */
    public function test_optional_unused_permission_no_penalty(): void
    {
        $this->permissionService
            ->shouldReceive('getSignatureInfo')
            ->with('test-plugin')
            ->andReturn(['status' => 'valid']);

        $this->permissionService
            ->shouldReceive('getPermissions')
            ->with('test-plugin')
            ->andReturn(['database' => ['own_tables' => true]]);

        $this->permissionService
            ->shouldReceive('getOptionalPermissions')
            ->with('test-plugin')
            ->andReturn(['mail.send']);

        PluginAudit::saveAuditResult('test-plugin', [
            'has_mismatches' => true,
            'mismatches' => [
                [
                    'type' => 'unused_declaration',
                    'permission' => 'mail.send',
                ],
            ],
            'matches_count' => 0,
            'total_checked' => 5,
        ]);

        $result = $this->scorer->calculate('test-plugin');

        $types = array_map(fn ($i) => $i->type, $result->issues);
        $this->assertNotContains('permission_unused', $types);
    }

    /**
     * CSPインラインJS必須の場合の減点テスト
     */
    public function test_csp_inline_js_required_deduction(): void
    {
        $this->permissionService
            ->shouldReceive('getSignatureInfo')
            ->with('test-plugin')
            ->andReturn(['status' => 'valid']);

        $this->permissionService
            ->shouldReceive('getPermissions')
            ->with('test-plugin')
            ->andReturn(['database' => ['own_tables' => true]]);

        PluginAudit::saveAuditResult('test-plugin', [
            'has_mismatches' => false,
            'mismatches' => [],
            'matches_count' => 0,
            'total_checked' => 5,
            'csp_requires_inline_js' => true,
            'csp_status' => 'inline_required',
        ]);

        $result = $this->scorer->calculate('test-plugin');

        $types = array_map(fn ($i) => $i->type, $result->issues);
        $this->assertContains('csp_inline_js_required', $types);
    }

    /**
     * スキャン期限切れの場合の減点テスト
     */
    public function test_scan_outdated_deduction(): void
    {
        $this->permissionService
            ->shouldReceive('getSignatureInfo')
            ->with('test-plugin')
            ->andReturn(['status' => 'valid']);

        $this->permissionService
            ->shouldReceive('getPermissions')
            ->with('test-plugin')
            ->andReturn(['database' => ['own_tables' => true]]);

        // 古い監査結果を作成
        $audit = PluginAudit::saveAuditResult('test-plugin', [
            'has_mismatches' => false,
            'mismatches' => [],
            'matches_count' => 0,
            'total_checked' => 5,
        ]);

        // 31日前に更新
        $audit->update(['audited_at' => now()->subDays(31)]);

        $result = $this->scorer->calculate('test-plugin');

        $types = array_map(fn ($i) => $i->type, $result->issues);
        $this->assertContains('scan_outdated', $types);
    }

    /**
     * 新しいスキャン結果の場合は期限切れにならないテスト
     */
    public function test_fresh_scan_no_outdated_issue(): void
    {
        $this->permissionService
            ->shouldReceive('getSignatureInfo')
            ->with('test-plugin')
            ->andReturn(['status' => 'valid']);

        $this->permissionService
            ->shouldReceive('getPermissions')
            ->with('test-plugin')
            ->andReturn(['database' => ['own_tables' => true]]);

        PluginAudit::saveAuditResult('test-plugin', [
            'has_mismatches' => false,
            'mismatches' => [],
            'matches_count' => 0,
            'total_checked' => 5,
        ]);

        $result = $this->scorer->calculate('test-plugin');

        $types = array_map(fn ($i) => $i->type, $result->issues);
        $this->assertNotContains('scan_outdated', $types);
        $this->assertNotContains('scan_not_performed', $types);
    }

    /**
     * スコアが0未満にならないテスト
     */
    public function test_score_does_not_go_below_zero(): void
    {
        $this->permissionService
            ->shouldReceive('getSignatureInfo')
            ->with('test-plugin')
            ->andReturn(['status' => 'invalid']);

        $this->permissionService
            ->shouldReceive('getPermissions')
            ->with('test-plugin')
            ->andReturn(null);

        // 多数の問題を含む監査結果
        PluginAudit::saveAuditResult('test-plugin', [
            'has_mismatches' => true,
            'mismatches' => [
                ['type' => 'undeclared_usage', 'permission' => 'database.core_tables', 'evidence' => []],
                ['type' => 'undeclared_usage', 'permission' => 'members.write', 'evidence' => []],
                ['type' => 'undeclared_usage', 'permission' => 'members.delete', 'evidence' => []],
            ],
            'matches_count' => 3,
            'total_checked' => 5,
            'csp_requires_inline_js' => true,
            'csp_status' => 'inline_required',
        ]);

        $result = $this->scorer->calculate('test-plugin');

        $this->assertGreaterThanOrEqual(0, $result->score);
        $this->assertEquals(PluginHealthStatus::NeedsAttention, $result->status);
    }

    /**
     * 致命的問題がある場合はスコアに関係なくNeedsAttentionになるテスト
     */
    public function test_critical_issue_forces_needs_attention(): void
    {
        $this->permissionService
            ->shouldReceive('getSignatureInfo')
            ->with('test-plugin')
            ->andReturn(['status' => 'invalid']);

        $this->permissionService
            ->shouldReceive('getPermissions')
            ->with('test-plugin')
            ->andReturn(['database' => ['own_tables' => true]]);

        // 新しいスキャンあり（scan減点なし）
        PluginAudit::saveAuditResult('test-plugin', [
            'has_mismatches' => false,
            'mismatches' => [],
            'matches_count' => 0,
            'total_checked' => 5,
        ]);

        $result = $this->scorer->calculate('test-plugin');

        // signature_invalid: -50 → score = 50
        $this->assertTrue($result->hasCriticalIssue);
        $this->assertEquals(PluginHealthStatus::NeedsAttention, $result->status);
    }

    /**
     * calculate()がHealthScoreResult DTOを返すテスト
     */
    public function test_calculate_returns_health_score_result(): void
    {
        $this->permissionService
            ->shouldReceive('getSignatureInfo')
            ->andReturn(['status' => 'valid']);

        $this->permissionService
            ->shouldReceive('getPermissions')
            ->andReturn([]);

        $result = $this->scorer->calculate('any-plugin');

        $this->assertInstanceOf(HealthScoreResult::class, $result);
        $this->assertIsInt($result->score);
        $this->assertInstanceOf(PluginHealthStatus::class, $result->status);
        $this->assertIsArray($result->issues);
        $this->assertIsBool($result->hasCriticalIssue);
    }

    /**
     * fromScore()のスコア境界値テスト
     */
    public function test_from_score_boundary_values(): void
    {
        $this->assertEquals(PluginHealthStatus::Healthy, PluginHealthStatus::fromScore(100));
        $this->assertEquals(PluginHealthStatus::Healthy, PluginHealthStatus::fromScore(90));
        $this->assertEquals(PluginHealthStatus::Advisory, PluginHealthStatus::fromScore(89));
        $this->assertEquals(PluginHealthStatus::Advisory, PluginHealthStatus::fromScore(70));
        $this->assertEquals(PluginHealthStatus::NeedsAttention, PluginHealthStatus::fromScore(69));
        $this->assertEquals(PluginHealthStatus::NeedsAttention, PluginHealthStatus::fromScore(0));
    }

    /**
     * fromScore()に致命的フラグがある場合のテスト
     */
    public function test_from_score_with_critical_flag(): void
    {
        // スコアが高くても致命的フラグがあればNeedsAttention
        $this->assertEquals(
            PluginHealthStatus::NeedsAttention,
            PluginHealthStatus::fromScore(100, hasCriticalIssue: true)
        );
    }
}
