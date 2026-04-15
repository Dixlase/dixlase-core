<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\ContentEditorType;
use App\Enums\ContentStatus;
use App\Enums\ContentStorageType;
use App\Models\BaseSetting;
use App\Models\FrontPage;
use App\Models\FrontPageRevision;
use App\Services\FrontPageRevisionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FrontPageRevisionService の動作を検証するユニットテスト
 *
 * 保存・スキップ判定・上限による削除・復元・保持件数のクランプを網羅する。
 */
class FrontPageRevisionServiceTest extends TestCase
{
    use RefreshDatabase;

    private FrontPageRevisionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(FrontPageRevisionService::class);
    }

    private function makeFrontPage(string $content = 'initial'): FrontPage
    {
        return FrontPage::create([
            'page_type' => 'main_content',
            'lang' => 'en',
            'title' => 'Title',
            'content' => $content,
            'editor_type' => ContentEditorType::HTML,
            'storage_type' => ContentStorageType::DATABASE,
            'status' => ContentStatus::PUBLISHED,
        ]);
    }

    public function test_record_creates_revision_with_snapshot(): void
    {
        $page = $this->makeFrontPage('hello');

        $rev = $this->service->record($page);

        $this->assertNotNull($rev);
        $this->assertSame($page->id, $rev->front_page_id);
        $this->assertSame('hello', $rev->snapshot['content']);
        $this->assertSame(FrontPageRevision::TYPE_AUTO, $rev->type);
    }

    public function test_record_skips_when_snapshot_matches_latest(): void
    {
        $page = $this->makeFrontPage('same');

        $first = $this->service->record($page);
        $second = $this->service->record($page);

        $this->assertNotNull($first);
        $this->assertNull($second);
        $this->assertSame(1, FrontPageRevision::count());
    }

    public function test_record_creates_new_revision_when_content_changes(): void
    {
        $page = $this->makeFrontPage('v1');
        $this->service->record($page);

        $page->update(['content' => 'v2']);
        $second = $this->service->record($page->fresh());

        $this->assertNotNull($second);
        $this->assertSame(2, FrontPageRevision::count());
    }

    public function test_record_prunes_old_revisions_beyond_retention(): void
    {
        BaseSetting::setValue(FrontPageRevisionService::SETTING_KEY_RETENTION, 3);

        $page = $this->makeFrontPage('v0');
        for ($i = 1; $i <= 5; $i++) {
            $page->update(['content' => "v{$i}"]);
            $this->service->record($page->fresh());
        }

        $this->assertSame(3, FrontPageRevision::where('front_page_id', $page->id)->count());
    }

    public function test_record_returns_null_when_retention_is_zero(): void
    {
        BaseSetting::setValue(FrontPageRevisionService::SETTING_KEY_RETENTION, 0);
        $page = $this->makeFrontPage('hello');

        $rev = $this->service->record($page);

        $this->assertNull($rev);
        $this->assertSame(0, FrontPageRevision::count());
    }

    public function test_restore_applies_snapshot_fields(): void
    {
        $page = $this->makeFrontPage('old');
        $old = $this->service->record($page);

        $page->update(['content' => 'new']);

        $this->service->restore($old);

        $this->assertSame('old', $page->fresh()->content);
    }

    public function test_restore_creates_restore_backup_when_current_differs_from_latest(): void
    {
        $page = $this->makeFrontPage('v1');
        $v1 = $this->service->record($page);

        // 現状を未保存で直接編集（直前リビジョンが 'v1' のまま、現状が 'v2' で差分あり）
        $page->update(['content' => 'v2']);

        $this->service->restore($v1->fresh());

        $this->assertTrue(
            FrontPageRevision::where('front_page_id', $page->id)
                ->where('type', FrontPageRevision::TYPE_RESTORE_BACKUP)
                ->exists()
        );
    }

    public function test_restore_skips_backup_when_current_matches_latest(): void
    {
        $page = $this->makeFrontPage('same');
        $rev = $this->service->record($page);

        $before = FrontPageRevision::count();
        $this->service->restore($rev);
        $after = FrontPageRevision::count();

        $this->assertSame($before, $after);
    }

    public function test_retention_count_is_clamped(): void
    {
        BaseSetting::setValue(FrontPageRevisionService::SETTING_KEY_RETENTION, 99999);
        $this->assertSame(FrontPageRevisionService::MAX_RETENTION, $this->service->getRetentionCount());

        BaseSetting::setValue(FrontPageRevisionService::SETTING_KEY_RETENTION, -5);
        $this->assertSame(0, $this->service->getRetentionCount());
    }
}
