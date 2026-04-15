<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Front;

use App\Actions\FrontPage\RestoreFrontPageRevisionAction;
use App\Actions\FrontPage\ToggleFrontPageRevisionProtectionAction;
use App\Actions\FrontPage\UpdateFrontPageRevisionNoteAction;
use App\Actors\MemberActor;
use App\Enums\ContentEditorType;
use App\Enums\ContentStatus;
use App\Enums\ContentStorageType;
use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Models\AuditLog;
use App\Models\FrontPage;
use App\Models\FrontPageRevision;
use App\Models\Member;
use App\Services\RevisionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * リビジョン系 Action（復元・保護トグル・メモ更新）が監査ログに正しく記録されることを検証する
 */
class RevisionActionAuditTest extends TestCase
{
    use RefreshDatabase;

    private Member $admin;

    private FrontPage $frontPage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Member::create([
            'account_name' => 'revauditadmin',
            'display_name' => 'Audit Admin',
            'email' => 'audit@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => MemberRole::SUPER_ADMIN,
            'status' => MemberStatus::Active,
        ]);

        $this->frontPage = FrontPage::create([
            'page_type' => 'main_content',
            'lang' => 'en',
            'title' => 'Hello',
            'content' => 'v1',
            'editor_type' => ContentEditorType::HTML,
            'storage_type' => ContentStorageType::DATABASE,
            'status' => ContentStatus::PUBLISHED,
        ]);
    }

    private function actor(): MemberActor
    {
        return new MemberActor($this->admin);
    }

    public function test_restore_action_writes_audit_log(): void
    {
        /** @var FrontPageRevision $rev */
        $rev = app(RevisionService::class)->record($this->frontPage);
        $this->frontPage->update(['content' => 'v2']);

        (new RestoreFrontPageRevisionAction($rev, app(RevisionService::class)))
            ->execute($this->actor(), []);

        $log = AuditLog::query()
            ->where('action', 'front_page.revision.restored')
            ->first();

        $this->assertNotNull($log, '復元 Action で監査ログが記録されること');
        $this->assertSame('success', $log->outcome);
        $this->assertSame($rev->id, $log->context['revision_id'] ?? null);
        $this->assertSame('v1', $this->frontPage->fresh()->content);
    }

    public function test_toggle_protection_action_writes_audit_log(): void
    {
        /** @var FrontPageRevision $rev */
        $rev = app(RevisionService::class)->record($this->frontPage);

        (new ToggleFrontPageRevisionProtectionAction($rev))
            ->execute($this->actor(), []);

        $log = AuditLog::query()
            ->where('action', 'front_page.revision.protection_toggled')
            ->first();

        $this->assertNotNull($log);
        $this->assertTrue($log->context['is_protected'] ?? null);
        $this->assertTrue($rev->fresh()->is_protected);

        // 再度呼べば false に戻り、新たなログが残る
        (new ToggleFrontPageRevisionProtectionAction($rev->fresh()))
            ->execute($this->actor(), []);

        $count = AuditLog::query()
            ->where('action', 'front_page.revision.protection_toggled')
            ->count();

        $this->assertSame(2, $count);
        $this->assertFalse($rev->fresh()->is_protected);
    }

    public function test_update_note_action_writes_audit_log(): void
    {
        /** @var FrontPageRevision $rev */
        $rev = app(RevisionService::class)->record($this->frontPage);

        (new UpdateFrontPageRevisionNoteAction($rev))
            ->execute($this->actor(), ['note' => 'Released version']);

        $log = AuditLog::query()
            ->where('action', 'front_page.revision.note_updated')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame(16, $log->context['note_length'] ?? null);
        $this->assertSame('Released version', $rev->fresh()->note);
    }

    public function test_update_note_action_accepts_null_and_records_length_zero(): void
    {
        /** @var FrontPageRevision $rev */
        $rev = app(RevisionService::class)->record($this->frontPage);
        $rev->update(['note' => 'old note']);

        (new UpdateFrontPageRevisionNoteAction($rev->fresh()))
            ->execute($this->actor(), ['note' => null]);

        $log = AuditLog::query()
            ->where('action', 'front_page.revision.note_updated')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame(0, $log->context['note_length'] ?? null);
        $this->assertNull($rev->fresh()->note);
    }
}
