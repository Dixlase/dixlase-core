<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
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

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * フロントページ管理のフィーチャーテスト
 */

namespace Tests\Feature\Admin\Front;

use App\Contracts\TranslationResolver;
use App\Enums\ContentEditorType;
use App\Enums\ContentPublishMode;
use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Models\AuditLog;
use App\Models\FrontPage;
use App\Models\FrontPageRevision;
use App\Models\FrontSetting;
use App\Models\Member;
use App\Services\Content\FileContentPublisher;
use App\Services\Content\FileContentRegistry;
use App\Services\FrontPageContentService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * Publishing a file-stored front page through the admin (#555)
 *
 * Under the default mode a change made to the file outside the admin waits
 * to be published from the edit screen; under the file save mode it goes
 * live at once. Both are audited.
 */
class FrontFileContentPublishTest extends TestCase
{
    use RefreshDatabase;

    private Member $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(EnsureEmailIsVerified::class);

        $adminTheme = config('themes.admin_theme', 'admin');
        $customFilesDir = base_path(config('custom.custom_files_dir', 'custom'));
        View::addNamespace('admin', [
            base_path("{$customFilesDir}/resources/views/{$adminTheme}"),
            resource_path("views/{$adminTheme}"),
        ]);

        $_ENV['INSTALLED'] = 'true';
        $_SERVER['INSTALLED'] = 'true';

        Storage::fake('local');

        $this->admin = Member::create([
            'account_name' => 'testadmin',
            'display_name' => 'Test Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => MemberRole::SUPER_ADMIN,
            'status' => MemberStatus::Active,
        ]);
    }

    protected function tearDown(): void
    {
        $_ENV['INSTALLED'] = 'false';
        $_SERVER['INSTALLED'] = 'false';
        parent::tearDown();
    }

    public function test_an_admin_save_leaves_nothing_to_publish(): void
    {
        $page = $this->createFilePage();

        $this->assertNotNull($page->content_hash);
        $this->assertSame($page->content_hash, $page->content_file_hash);
        $this->assertSame([], app(FileContentPublisher::class)->pendingChanges($page));
    }

    public function test_by_default_a_file_change_waits_to_be_published(): void
    {
        $page = $this->createFilePage();
        Storage::disk('local')->put('core/front/content.html', '<h1>Edited on disk</h1>');

        $this->assertSame('<h1>Published</h1>', $this->content($page));
        $this->assertSame('body { color: red; }', $this->css($page));

        $log = AuditLog::where('action', 'front_page.file_changed')->sole();
        $this->assertSame(AuditLog::SEVERITY_WARNING, $log->severity);
        $this->assertSame(ContentPublishMode::ADMIN->value, $log->context['publish_mode']);
        $this->assertSame(['content'], $log->context['parts']);
        $this->assertFalse($log->context['published']);
        $this->assertSame(1, app(FileContentRegistry::class)->pendingCount());
    }

    public function test_a_change_is_recorded_once(): void
    {
        $page = $this->createFilePage();
        Storage::disk('local')->put('core/front/content.html', '<h1>Edited on disk</h1>');

        $this->content($page);
        $this->content($page);
        $this->css($page);

        $this->assertSame(1, AuditLog::where('action', 'front_page.file_changed')->count());
    }

    public function test_the_edit_screen_shows_the_change_and_publishes_it(): void
    {
        $page = $this->createFilePage();
        Storage::disk('local')->put('core/front/content.html', '<h1>Edited on disk</h1>');

        $this->actingAs($this->admin, 'member')
            ->get(route('admin.front.edit'))
            ->assertOk()
            ->assertSee(__('common.file_changes.title'))
            ->assertSee('Edited on disk')
            ->assertViewHas('fileChanges', fn (array $changes) => array_keys($changes) === ['content']);

        $this->actingAs($this->admin, 'member')
            ->post(route('admin.front.publish-file'))
            ->assertRedirect(route('admin.front.edit'))
            ->assertSessionHas('success');

        $page = $page->fresh();
        $this->assertSame('<h1>Edited on disk</h1>', $page->content);
        $this->assertSame('<h1>Edited on disk</h1>', $this->content($page));
        $this->assertSame(0, app(FileContentRegistry::class)->pendingCount());

        $log = AuditLog::where('action', 'front_page.published_from_file')->sole();
        $this->assertSame(['content'], $log->context['parts']);
        $this->assertSame($this->admin->id, $log->actor_id);

        $revision = FrontPageRevision::where('front_page_id', $page->id)->latest('id')->first();
        $this->assertSame(FrontPageRevision::TYPE_MANUAL, $revision->type);
        $this->assertSame('<h1>Edited on disk</h1>', $revision->snapshot['content']);
    }

    public function test_publishing_with_no_change_does_nothing(): void
    {
        $this->createFilePage();

        $this->actingAs($this->admin, 'member')
            ->post(route('admin.front.publish-file'))
            ->assertRedirect(route('admin.front.edit'))
            ->assertSessionHas('error');

        $this->assertSame(0, AuditLog::where('action', 'front_page.published_from_file')->count());
    }

    public function test_under_the_file_save_mode_a_change_goes_live(): void
    {
        FrontSetting::setValue(FrontPage::PUBLISH_MODE_SETTING, ContentPublishMode::FILE_SAVE->value);
        $page = $this->createFilePage();
        Storage::disk('local')->put('core/front/style.css', 'body { color: blue; }');

        $this->assertSame('body { color: blue; }', $this->css($page));
        $this->assertSame('body { color: blue; }', $page->fresh()->custom_css);

        $log = AuditLog::where('action', 'front_page.file_changed')->sole();
        $this->assertSame(['custom_css'], $log->context['parts']);
        $this->assertTrue($log->context['published']);
        $this->assertSame(0, app(FileContentRegistry::class)->pendingCount());

        $revision = FrontPageRevision::where('front_page_id', $page->id)->latest('id')->first();
        $this->assertSame(FrontPageRevision::TYPE_AUTO, $revision->type);
    }

    public function test_switching_to_the_file_save_mode_takes_in_a_waiting_change(): void
    {
        $page = $this->createFilePage();
        Storage::disk('local')->put('core/front/content.html', '<h1>Edited on disk</h1>');
        $this->assertSame('<h1>Published</h1>', $this->content($page));

        FrontSetting::setValue(FrontPage::PUBLISH_MODE_SETTING, ContentPublishMode::FILE_SAVE->value);

        $this->assertSame('<h1>Edited on disk</h1>', $this->content($page->fresh()));
    }

    public function test_a_missing_css_file_counts_as_empty(): void
    {
        $page = $this->createFilePage();
        Storage::disk('local')->delete('core/front/style.css');

        $this->assertSame(['custom_css'], array_keys(app(FileContentPublisher::class)->pendingChanges($page)));
    }

    public function test_a_translation_overlay_keeps_priority(): void
    {
        $page = $this->createFilePage();
        Storage::disk('local')->put('core/front/content.html', '<h1>Edited on disk</h1>');
        $this->app->instance(TranslationResolver::class, $this->overlay('<h1>Übersetzt</h1>'));

        $this->assertSame('<h1>Übersetzt</h1>', app(FrontPageContentService::class)->getContent($page, 'de'));
    }

    public function test_restoring_a_revision_writes_the_files_back(): void
    {
        $page = $this->createFilePage();
        $first = FrontPageRevision::where('front_page_id', $page->id)->sole();

        $this->actingAs($this->admin, 'member')->put(route('admin.front.edit.update'), [
            'content' => '<h1>Second</h1>',
            'custom_css' => 'body { color: green; }',
            'custom_js' => '',
        ])->assertRedirect(route('admin.front.edit'));
        Storage::disk('local')->assertMissing('core/front/script.js');

        $this->actingAs($this->admin, 'member')
            ->post(route('admin.front.revisions.restore', $first->id))
            ->assertRedirect();

        $this->assertSame('<h1>Published</h1>', Storage::disk('local')->get('core/front/content.html'));
        $this->assertSame('body { color: red; }', Storage::disk('local')->get('core/front/style.css'));
        $this->assertSame('console.log(1);', Storage::disk('local')->get('core/front/script.js'));
        $this->assertSame([], app(FileContentPublisher::class)->pendingChanges($page->fresh()));
    }

    public function test_the_scan_finds_a_change_nobody_has_viewed(): void
    {
        $this->createFilePage();
        Storage::disk('local')->put('core/front/content.html', '<h1>Edited on disk</h1>');

        $result = app(FileContentRegistry::class)->checkAll(app(FileContentPublisher::class));

        $this->assertSame(['checked' => 1, 'pending' => 1], $result);
        $this->assertSame(1, AuditLog::where('action', 'front_page.file_changed')->count());
    }

    public function test_the_publish_mode_setting_is_saved_and_audited(): void
    {
        $this->actingAs($this->admin, 'member')
            ->post(route('admin.front.settings.store'), ['publish_mode' => 'file_save'])
            ->assertRedirect(route('admin.front.settings'))
            ->assertSessionHas('success');

        $this->assertSame('file_save', FrontSetting::getValue(FrontPage::PUBLISH_MODE_SETTING));

        $log = AuditLog::where('action', AuditLog::ACTION_SETTINGS_UPDATED)
            ->where('target_label', 'front.settings')
            ->sole();
        $this->assertSame([FrontPage::PUBLISH_MODE_SETTING], $log->context['changed_keys']);
    }

    public function test_the_publish_mode_setting_rejects_an_unknown_value(): void
    {
        $this->actingAs($this->admin, 'member')
            ->post(route('admin.front.settings.store'), ['publish_mode' => 'anything'])
            ->assertSessionHasErrors('publish_mode');
    }

    private function createFilePage(): FrontPage
    {
        $this->actingAs($this->admin, 'member')->post(route('admin.front.store'), [
            'lang' => 'en',
            'editor_type' => ContentEditorType::HTML->slug(),
            'storage_type' => 'file',
            'content' => '<h1>Published</h1>',
            'custom_css' => 'body { color: red; }',
            'custom_js' => 'console.log(1);',
        ])->assertRedirect(route('admin.front.edit'));

        return FrontPage::findByType('main_content');
    }

    private function content(FrontPage $page): ?string
    {
        return app(FrontPageContentService::class)->getContent($page, 'en');
    }

    private function css(FrontPage $page): ?string
    {
        return app(FrontPageContentService::class)->getCssContent($page, 'en');
    }

    private function overlay(string $value): TranslationResolver
    {
        return new class($value) implements TranslationResolver
        {
            public function __construct(private readonly string $value) {}

            public function resolve(Model $model, string $field, string $locale): mixed
            {
                return $field === 'content' ? $this->value : null;
            }

            public function store(Model $model, string $field, mixed $value, string $locale): void {}

            public function all(Model $model, string $field): array
            {
                return [];
            }

            public function exists(Model $model, string $field, string $locale): bool
            {
                return false;
            }

            public function delete(Model $model, string $field, ?string $locale = null): void {}

            public function getAvailableLocales(Model $model): array
            {
                return [];
            }

            public function copy(Model $source, Model $target, ?array $fields = null, ?array $locales = null): void {}
        };
    }
}
