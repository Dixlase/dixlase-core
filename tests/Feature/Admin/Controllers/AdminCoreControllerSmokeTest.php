<?php

namespace Tests\Feature\Admin\Controllers;

use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\ContentSecurityPolicy;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Models\Member;
use App\Models\SecuritySetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * 管理画面コアコントローラ（ダッシュボード、フロント、メディア、メンバー、プロフィール）のスモークテスト
 */
class AdminCoreControllerSmokeTest extends TestCase
{
    use RefreshDatabase;

    private Member $superAdmin;

    private Member $editor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            CheckInstallationReady::class,
            ContentSecurityPolicy::class,
            EnsureEmailIsVerified::class,
        ]);

        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';

        View::addNamespace('admin', [resource_path('views/'.config('themes.admin_theme', 'admin'))]);

        $this->superAdmin = Member::create([
            'account_name' => 'superadmin',
            'display_name' => 'Super Admin',
            'email' => 'super@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => MemberRole::SUPER_ADMIN,
            'status' => MemberStatus::Active,
            'two_fa_mode' => 0,
        ]);

        $this->editor = Member::create([
            'account_name' => 'editor',
            'display_name' => 'Editor',
            'email' => 'editor@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => MemberRole::EDITOR,
            'status' => MemberStatus::Active,
            'two_fa_mode' => 0,
        ]);

        SecuritySetting::setValue('two_fa_mode', 0);
    }

    protected function tearDown(): void
    {
        putenv('INSTALLED=false');
        $_ENV['INSTALLED'] = 'false';
        parent::tearDown();
    }

    // =========================================================================
    // ダッシュボード
    // =========================================================================

    public function test_dashboard_accessible(): void
    {
        $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.dashboard'))
            ->assertOk();
    }

    public function test_dashboard_accessible_by_editor(): void
    {
        $this->actingAs($this->editor, 'member')
            ->get(route('admin.dashboard'))
            ->assertOk();
    }

    // =========================================================================
    // フロントページ管理
    // =========================================================================

    public function test_front_index(): void
    {
        $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.front.index'))
            ->assertOk();
    }

    public function test_front_create(): void
    {
        $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.front.create'))
            ->assertOk();
    }

    public function test_front_settings(): void
    {
        $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.front.settings'))
            ->assertOk();
    }

    // =========================================================================
    // メディア
    // =========================================================================

    public function test_media_index(): void
    {
        $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.media.index'))
            ->assertOk();
    }

    public function test_media_upload_page(): void
    {
        $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.media.upload'))
            ->assertOk();
    }

    public function test_media_settings(): void
    {
        $response = $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.media.settings'));

        $this->assertContains($response->getStatusCode(), [200, 302]);
    }

    // =========================================================================
    // メンバー管理
    // =========================================================================

    public function test_members_index(): void
    {
        $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.members.index'))
            ->assertOk();
    }

    public function test_members_create(): void
    {
        $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.members.create'))
            ->assertOk();
    }

    public function test_members_edit(): void
    {
        $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.members.edit', $this->editor))
            ->assertOk();
    }

    public function test_members_roles(): void
    {
        $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.members.roles'))
            ->assertOk();
    }

    // =========================================================================
    // プロフィール
    // =========================================================================

    public function test_profile_index(): void
    {
        $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.profile'))
            ->assertOk();
    }

    public function test_profile_basic(): void
    {
        $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.profile.basic'))
            ->assertOk();
    }

    public function test_profile_password(): void
    {
        $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.profile.password'))
            ->assertOk();
    }

    public function test_profile_appearance(): void
    {
        $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.profile.appearance'))
            ->assertOk();
    }

    public function test_profile_notifications(): void
    {
        $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.profile.notifications'))
            ->assertOk();
    }

    // =========================================================================
    // エディターからのアクセス制限
    // =========================================================================

    public function test_editor_cannot_access_members(): void
    {
        $this->actingAs($this->editor, 'member')
            ->get(route('admin.members.index'))
            ->assertStatus(403);
    }

    public function test_editor_can_access_profile(): void
    {
        $this->actingAs($this->editor, 'member')
            ->get(route('admin.profile'))
            ->assertOk();
    }
}
