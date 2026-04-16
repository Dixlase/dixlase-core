<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Contracts\Revisionable;
use App\Enums\ContentEditorType;
use App\Enums\ContentStatus;
use App\Enums\ContentStorageType;
use App\Models\FrontPage;
use App\Models\FrontPageRevision;
use App\Services\RevisionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * RevisionService::loadTarget のリレーション名解決テスト
 *
 * 既存 frontPage リレーション経由での解決が動くこと、および
 * 不明なリレーション構造の場合は明確なエラーが出ることを確認する。
 */
class RevisionLoadTargetTest extends TestCase
{
    use RefreshDatabase;

    public function test_restore_resolves_parent_through_front_page_relation(): void
    {
        $page = FrontPage::create([
            'page_type' => 'main_content',
            'lang' => 'en',
            'title' => 'Title',
            'content' => 'v1',
            'editor_type' => ContentEditorType::HTML,
            'storage_type' => ContentStorageType::DATABASE,
            'status' => ContentStatus::PUBLISHED,
        ]);

        $service = app(RevisionService::class);
        /** @var FrontPageRevision $rev */
        $rev = $service->record($page);

        $page->update(['content' => 'v2']);

        $result = $service->restore($rev);

        $this->assertInstanceOf(Revisionable::class, $result);
        $this->assertSame('v1', $page->fresh()->content);
    }

    public function test_load_target_throws_when_no_revisionable_relation_defined(): void
    {
        // loadTarget は private のためリフレクションで呼び出し、
        // 親リレーションを持たないリビジョンモデルで例外発生を確認する。
        $service = app(RevisionService::class);
        $reflection = new \ReflectionClass(RevisionService::class);
        $method = $reflection->getMethod('loadTarget');
        $method->setAccessible(true);

        // 無関係なモデル（親リレーションなし）を渡す
        $stub = new class extends \Illuminate\Database\Eloquent\Model
        {
            protected $table = 'nonexistent';
        };

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Could not resolve Revisionable target');

        $method->invoke($service, $stub);
    }
}
