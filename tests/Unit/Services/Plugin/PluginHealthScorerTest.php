<?php

namespace Tests\Unit\Services\Plugin;

use App\DTO\Plugin\HealthScoreResult;
use App\Enums\ExtensionSecurityLevel;
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

        // 監査結果を作成（問題なし）
        PluginAudit::saveAuditResult('test-plugin', [
            'has_mismatches' => false,
            'mismatches' => [],
            'matches_count' => 0,
            'total_checked' => 5,
        ]);

        $result = $this->scorer->calculate('test-plugin');

        $this->assertInstanceOf(HealthScoreResult::class, $result);
        // 署名valid + 権限定義あり + 監査あり + 問題なし → 100点
        $this->assertEquals(100, $result->score);
        $this->assertEquals(PluginHealthStatus::Healthy, $result->status);
        $this->assertFalse($result->hasCriticalIssue);
    }

    /**
     * 署名未設定の場合の減点テスト（非本番環境）
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

        // 監査結果を作成（前提条件充足）
        PluginAudit::saveAuditResult('test-plugin', [
            'has_mismatches' => false,
            'mismatches' => [],
            'matches_count' => 0,
            'total_checked' => 5,
        ]);

        $result = $this->scorer->calculate('test-plugin');

        // signature_unsigned: -5 → score = 95
        $this->assertEquals(95, $result->score);
        $this->assertEquals(PluginHealthStatus::Healthy, $result->status);

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

        // 監査結果を作成（前提条件充足）
        PluginAudit::saveAuditResult('test-plugin', [
            'has_mismatches' => false,
            'mismatches' => [],
            'matches_count' => 0,
            'total_checked' => 5,
        ]);

        $result = $this->scorer->calculate('test-plugin');

        // signature_invalid: -50 → score = 50
        $this->assertEquals(50, $result->score);
        $this->assertEquals(PluginHealthStatus::NeedsAttention, $result->status);
        $this->assertTrue($result->hasCriticalIssue);

        $types = array_map(fn ($i) => $i->type, $result->issues);
        $this->assertContains('signature_invalid', $types);
    }

    /**
     * 権限未定義の場合はNotVerifiedを返すテスト
     */
    public function test_undefined_permissions_returns_not_verified(): void
    {
        $this->permissionService
            ->shouldReceive('getPermissions')
            ->with('test-plugin')
            ->andReturn(null);

        // 監査結果は存在するが、permissionsがnull → NotVerified
        PluginAudit::saveAuditResult('test-plugin', [
            'has_mismatches' => false,
            'mismatches' => [],
            'matches_count' => 0,
            'total_checked' => 5,
        ]);

        $result = $this->scorer->calculate('test-plugin');

        // permissions null → 即 NotVerified（score=0）
        $this->assertEquals(0, $result->score);
        $this->assertEquals(PluginHealthStatus::NotVerified, $result->status);
        $this->assertFalse($result->hasCriticalIssue);

        $types = array_map(fn ($i) => $i->type, $result->issues);
        $this->assertContains('not_verified_no_permissions', $types);
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
            ->andReturn(['database' => ['own_tables' => true]]);

        $this->permissionService
            ->shouldReceive('getOptionalPermissions')
            ->with('test-plugin')
            ->andReturn([]);

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
            ->andReturn(['database' => ['own_tables' => true]]);

        // 監査結果を作成（前提条件充足）
        PluginAudit::saveAuditResult('any-plugin', [
            'has_mismatches' => false,
            'mismatches' => [],
            'matches_count' => 0,
            'total_checked' => 5,
        ]);

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

    // ====================================================================
    // 新規テスト: NotVerified 前提条件
    // ====================================================================

    /**
     * 監査未実行の場合はNotVerifiedを返すテスト
     */
    public function test_not_verified_when_no_audit(): void
    {
        $this->permissionService
            ->shouldReceive('getPermissions')
            ->with('test-plugin')
            ->andReturn(['database' => ['own_tables' => true]]);

        // 監査レコードなし → NotVerified
        $result = $this->scorer->calculate('test-plugin');

        $this->assertEquals(0, $result->score);
        $this->assertEquals(PluginHealthStatus::NotVerified, $result->status);
        $this->assertFalse($result->hasCriticalIssue);
        $this->assertTrue($result->isNotVerified());

        $types = array_map(fn ($i) => $i->type, $result->issues);
        $this->assertContains('not_verified_no_scan', $types);
    }

    /**
     * permissions未定義の場合はNotVerifiedを返すテスト
     */
    public function test_not_verified_when_no_permissions(): void
    {
        $this->permissionService
            ->shouldReceive('getPermissions')
            ->with('test-plugin')
            ->andReturn(null);

        // 監査は存在するがpermissions null → NotVerified
        PluginAudit::saveAuditResult('test-plugin', [
            'has_mismatches' => false,
            'mismatches' => [],
            'matches_count' => 0,
            'total_checked' => 5,
        ]);

        $result = $this->scorer->calculate('test-plugin');

        $this->assertEquals(0, $result->score);
        $this->assertEquals(PluginHealthStatus::NotVerified, $result->status);
        $this->assertTrue($result->isNotVerified());

        $types = array_map(fn ($i) => $i->type, $result->issues);
        $this->assertContains('not_verified_no_permissions', $types);
    }

    // ====================================================================
    // 新規テスト: Trust Level 署名連動
    // ====================================================================

    /**
     * 署名無効時のTrustLevel降格テスト
     */
    public function test_trust_level_downgrade_on_invalid_signature(): void
    {
        $this->assertEquals(
            \App\Enums\PluginTrustLevel::Community,
            \App\Enums\PluginTrustLevel::fromSignatureVerification('official', 'invalid')
        );

        $this->assertEquals(
            \App\Enums\PluginTrustLevel::Community,
            \App\Enums\PluginTrustLevel::fromSignatureVerification('official', 'expired')
        );

        $this->assertEquals(
            \App\Enums\PluginTrustLevel::Community,
            \App\Enums\PluginTrustLevel::fromSignatureVerification('verified', 'error')
        );
    }

    /**
     * 署名未設定時のTrustLevel降格テスト
     */
    public function test_trust_level_local_on_unsigned_signature(): void
    {
        $this->assertEquals(
            \App\Enums\PluginTrustLevel::Local,
            \App\Enums\PluginTrustLevel::fromSignatureVerification('official', 'unsigned')
        );

        $this->assertEquals(
            \App\Enums\PluginTrustLevel::Local,
            \App\Enums\PluginTrustLevel::fromSignatureVerification('verified', 'pending_verification')
        );
    }

    /**
     * 署名有効時のTrustLevel維持テスト
     */
    public function test_trust_level_preserved_on_valid_signature(): void
    {
        $result = \App\Enums\PluginTrustLevel::fromSignatureVerification('official', 'valid');
        $this->assertNotEquals(\App\Enums\PluginTrustLevel::Community, $result);
        $this->assertNotEquals(\App\Enums\PluginTrustLevel::Local, $result);
    }

    // ====================================================================
    // 新規テスト: 環境依存ペナルティ
    // ====================================================================

    /**
     * 本番環境での署名未署名ペナルティ -15 のテスト
     */
    public function test_signature_unsigned_production_penalty(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->permissionService
            ->shouldReceive('getSignatureInfo')
            ->with('test-plugin')
            ->andReturn(['status' => 'unsigned']);

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

        // signature_unsigned_production: -15 → score = 85
        $this->assertEquals(85, $result->score);
        $this->assertEquals(PluginHealthStatus::Advisory, $result->status);

        $types = array_map(fn ($i) => $i->type, $result->issues);
        $this->assertContains('signature_unsigned_production', $types);
        $this->assertNotContains('signature_unsigned', $types);
    }

    /**
     * 非本番環境での署名未署名ペナルティ -5 のテスト
     */
    public function test_signature_unsigned_non_production_penalty(): void
    {
        $this->app->detectEnvironment(fn () => 'local');

        $this->permissionService
            ->shouldReceive('getSignatureInfo')
            ->with('test-plugin')
            ->andReturn(['status' => 'unsigned']);

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

        // signature_unsigned: -5 → score = 95
        $this->assertEquals(95, $result->score);
        $this->assertEquals(PluginHealthStatus::Healthy, $result->status);

        $types = array_map(fn ($i) => $i->type, $result->issues);
        $this->assertContains('signature_unsigned', $types);
        $this->assertNotContains('signature_unsigned_production', $types);
    }

    // ====================================================================
    // 新規テスト: CSP インラインCSS減点
    // ====================================================================

    /**
     * インラインCSS必須時の減点テスト
     */
    public function test_csp_inline_css_deduction(): void
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
            'csp_requires_inline_css' => true,
        ]);

        $result = $this->scorer->calculate('test-plugin');

        // csp_inline_css_required: -5 → score = 95
        $this->assertEquals(95, $result->score);

        $types = array_map(fn ($i) => $i->type, $result->issues);
        $this->assertContains('csp_inline_css_required', $types);
    }

    // ====================================================================
    // 新規テスト: EnableAction 判定
    // ====================================================================

    /**
     * セキュリティ設定で許可されていないステータスはBlockedを返すテスト
     */
    public function test_enable_action_blocked_when_status_not_allowed(): void
    {
        $result = new HealthScoreResult(
            score: 80,
            status: PluginHealthStatus::NeedsAttention,
            issues: [],
            hasCriticalIssue: true,
        );

        // Warning レベルでは NeedsAttention は許可されない → Blocked
        $action = $this->scorer->determineEnableAction($result, ExtensionSecurityLevel::Warning);

        $this->assertEquals(\App\Enums\PluginEnableAction::Blocked, $action);
    }

    /**
     * セキュリティ設定で許可されていない低スコアはBlockedを返すテスト
     */
    public function test_enable_action_blocked_on_low_score_when_status_not_allowed(): void
    {
        $result = new HealthScoreResult(
            score: 40,
            status: PluginHealthStatus::NeedsAttention,
            issues: [],
            hasCriticalIssue: false,
        );

        // Warning レベルでは NeedsAttention は許可されない → Blocked
        $action = $this->scorer->determineEnableAction($result, ExtensionSecurityLevel::Warning);

        $this->assertEquals(\App\Enums\PluginEnableAction::Blocked, $action);
    }

    /**
     * 健全時にAllowedを返すテスト
     */
    public function test_enable_action_allowed(): void
    {
        $result = new HealthScoreResult(
            score: 95,
            status: PluginHealthStatus::Healthy,
            issues: [],
            hasCriticalIssue: false,
        );

        $action = $this->scorer->determineEnableAction($result, ExtensionSecurityLevel::NotVerified);

        $this->assertEquals(\App\Enums\PluginEnableAction::Allowed, $action);
    }

    /**
     * 軽微問題時にWarningRequiredを返すテスト
     */
    public function test_enable_action_warning_required(): void
    {
        $result = new HealthScoreResult(
            score: 80,
            status: PluginHealthStatus::Advisory,
            issues: [],
            hasCriticalIssue: false,
        );

        $action = $this->scorer->determineEnableAction($result, ExtensionSecurityLevel::NotVerified);

        $this->assertEquals(\App\Enums\PluginEnableAction::WarningRequired, $action);
    }

    /**
     * 中程度の問題時にAcknowledgementRequiredを返すテスト
     */
    public function test_enable_action_acknowledgement_required(): void
    {
        $result = new HealthScoreResult(
            score: 55,
            status: PluginHealthStatus::NeedsAttention,
            issues: [],
            hasCriticalIssue: false,
        );

        $action = $this->scorer->determineEnableAction($result, ExtensionSecurityLevel::NotVerified);

        $this->assertEquals(\App\Enums\PluginEnableAction::AcknowledgementRequired, $action);
    }

    /**
     * 開発モード（NotVerified許可）では致命的問題があってもAcknowledgementRequiredを返すテスト
     */
    public function test_enable_action_development_mode_allows_critical(): void
    {
        $result = new HealthScoreResult(
            score: 80,
            status: PluginHealthStatus::NeedsAttention,
            issues: [],
            hasCriticalIssue: true,
        );

        // 開発モード（NotVerified）ではすべてのステータスが許可される
        $action = $this->scorer->determineEnableAction($result, ExtensionSecurityLevel::NotVerified);

        // 致命的問題があっても Blocked ではなく AcknowledgementRequired
        $this->assertEquals(\App\Enums\PluginEnableAction::AcknowledgementRequired, $action);
    }

    /**
     * 開発モード（NotVerified許可）では低スコアでもAcknowledgementRequiredを返すテスト
     */
    public function test_enable_action_development_mode_allows_low_score(): void
    {
        $result = new HealthScoreResult(
            score: 40,
            status: PluginHealthStatus::NeedsAttention,
            issues: [],
            hasCriticalIssue: false,
        );

        // 開発モード（NotVerified）ではすべてのステータスが許可される
        $action = $this->scorer->determineEnableAction($result, ExtensionSecurityLevel::NotVerified);

        // 低スコアでも Blocked ではなく AcknowledgementRequired
        $this->assertEquals(\App\Enums\PluginEnableAction::AcknowledgementRequired, $action);
    }

    /**
     * 厳格モード（Healthyのみ）ではAdvisoryもBlockedになるテスト
     */
    public function test_enable_action_strict_blocks_advisory(): void
    {
        $result = new HealthScoreResult(
            score: 80,
            status: PluginHealthStatus::Advisory,
            issues: [],
            hasCriticalIssue: false,
        );

        // 厳格モード（Healthy のみ）では Advisory は許可されない → Blocked
        $action = $this->scorer->determineEnableAction($result, ExtensionSecurityLevel::Healthy);

        $this->assertEquals(\App\Enums\PluginEnableAction::Blocked, $action);
    }

    // ====================================================================
    // 新規テスト: ファイルハッシュ再スキャン判定
    // ====================================================================

    /**
     * 監査結果がない場合はneedsRescanがtrueを返すテスト
     */
    public function test_needs_rescan_when_no_audit(): void
    {
        $this->assertTrue($this->scorer->needsRescan('nonexistent-plugin'));
    }

    /**
     * files_hashがnullの場合はneedsRescanがtrueを返すテスト
     */
    public function test_needs_rescan_when_no_hash(): void
    {
        PluginAudit::saveAuditResult('test-plugin', [
            'has_mismatches' => false,
            'mismatches' => [],
            'matches_count' => 0,
            'total_checked' => 5,
            'files_hash' => null,
        ]);

        $this->assertTrue($this->scorer->needsRescan('test-plugin'));
    }

    /**
     * ファイルハッシュ不一致の場合はneedsRescanがtrueを返すテスト
     */
    public function test_needs_rescan_detects_file_changes(): void
    {
        PluginAudit::saveAuditResult('test-plugin', [
            'has_mismatches' => false,
            'mismatches' => [],
            'matches_count' => 0,
            'total_checked' => 5,
            'files_hash' => 'old_hash_value_that_does_not_match',
        ]);

        // プラグインディレクトリが存在しない場合、computeFilesHash は空文字を返す
        // 'old_hash_value...' !== '' → true
        $this->assertTrue($this->scorer->needsRescan('test-plugin'));
    }
}
