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

use App\Actions\Content\RecordFileContentChangeAction;
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
use App\Presenters\Admin\DashboardPresenter;
use App\Services\Content\FileContentPublisher;
use App\Services\Content\FileContentRegistry;
use App\Services\FrontPageContentService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Publishing a file-stored front page through the admin (#555)
 *
 * Under the default mode a change made to the file outside the admin waits
 * to be published from the edit screen; under the file save mode it goes
 * live at once -- in a local environment only (#786). Both are audited.
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

    public function test_under_the_file_save_mode_a_markdown_body_shows_locally_and_waits(): void
    {
        FrontSetting::setValue(FrontPage::PUBLISH_MODE_SETTING, ContentPublishMode::FILE_SAVE->value);
        $page = $this->createMarkdownFilePage();
        $this->useLocalEnvironment();
        Storage::disk('local')->put('core/front/content.md', '# Edited on disk');

        // Shown at once, but the published copy stays as it was (#808).
        $this->assertSame('# Edited on disk', $this->content($page));
        $this->assertSame('# Published', $page->fresh()->content);

        $log = AuditLog::where('action', 'front_page.file_changed')->sole();
        $this->assertSame(['content'], $log->context['parts']);
        $this->assertSame(['content'], $log->context['held']);
        $this->assertFalse($log->context['published']);
        // Counted for the admin's banner, so it is not forgotten before a deploy.
        $this->assertSame(1, app(FileContentRegistry::class)->pendingCount());
        $this->assertTrue(app(FileContentRegistry::class)->usesFileSaveMode());

        $this->assertSame(0, FrontPageRevision::where('front_page_id', $page->id)
            ->where('type', FrontPageRevision::TYPE_AUTO)->count());
    }

    public function test_under_the_file_save_mode_every_part_shows_locally_and_waits(): void
    {
        FrontSetting::setValue(FrontPage::PUBLISH_MODE_SETTING, ContentPublishMode::FILE_SAVE->value);
        $page = $this->createFilePage();
        $this->useLocalEnvironment();
        Storage::disk('local')->put('core/front/content.html', '<h1>Edited on disk</h1><script>console.log(2);</script>');
        Storage::disk('local')->put('core/front/style.css', 'body { color: blue; }');
        Storage::disk('local')->put('core/front/script.js', 'console.log(3);');

        $service = app(FrontPageContentService::class);
        $this->assertSame('<h1>Edited on disk</h1><script>console.log(2);</script>', $this->content($page));
        $this->assertSame('body { color: blue; }', $service->getCssContent($page));
        $this->assertSame('console.log(3);', $service->getJsContent($page));

        $fresh = $page->fresh();
        $this->assertSame('body { color: red; }', $fresh->custom_css);
        $this->assertSame('console.log(1);', $fresh->custom_js);

        $log = AuditLog::where('action', 'front_page.file_changed')->sole();
        $this->assertSame(ContentPublishMode::FILE_SAVE->value, $log->context['publish_mode']);
        $this->assertSame(ContentPublishMode::FILE_SAVE->value, $log->context['selected_publish_mode']);
        $this->assertEqualsCanonicalizing(['content', 'custom_css', 'custom_js'], $log->context['parts']);
        $this->assertEqualsCanonicalizing(['content', 'custom_css', 'custom_js'], $log->context['held']);
        $this->assertFalse($log->context['published']);
        $this->assertSame(1, app(FileContentRegistry::class)->pendingCount());
    }

    public function test_under_the_file_save_mode_publishing_locally_takes_the_change_in(): void
    {
        FrontSetting::setValue(FrontPage::PUBLISH_MODE_SETTING, ContentPublishMode::FILE_SAVE->value);
        $page = $this->createMarkdownFilePage();
        $this->useLocalEnvironment();
        Storage::disk('local')->put('core/front/content.md', '# Edited on disk');
        $this->content($page);

        $this->artisan('dls:content:publish-files', ['--publish' => true])->assertSuccessful();

        $this->assertSame('# Edited on disk', $page->fresh()->content);
        $this->assertSame(0, app(FileContentRegistry::class)->pendingCount());
        $revision = FrontPageRevision::where('front_page_id', $page->id)->latest('id')->first();
        $this->assertSame(FrontPageRevision::TYPE_MANUAL, $revision->type);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function nonLocalEnvironments(): array
    {
        return [
            'testing' => ['testing'],
            'staging' => ['staging'],
            'production' => ['production'],
            'a mistyped value' => ['locale'],
        ];
    }

    #[DataProvider('nonLocalEnvironments')]
    public function test_outside_a_local_environment_the_file_save_mode_takes_nothing_in(string $environment): void
    {
        FrontSetting::setValue(FrontPage::PUBLISH_MODE_SETTING, ContentPublishMode::FILE_SAVE->value);
        $page = $this->createMarkdownFilePage();
        $this->app['env'] = $environment;
        Storage::disk('local')->put('core/front/content.md', '# Edited on disk');

        // Even a Markdown body, which cannot carry a script, waits.
        $this->assertSame('# Published', $this->content($page));
        $this->assertSame('# Published', $page->fresh()->content);
        $this->assertSame(ContentPublishMode::ADMIN, app(FileContentPublisher::class)->effectivePublishMode($page));
        // The setting is kept as chosen.
        $this->assertSame(ContentPublishMode::FILE_SAVE->value, FrontSetting::getValue(FrontPage::PUBLISH_MODE_SETTING));

        $log = AuditLog::where('action', 'front_page.file_changed')->sole();
        $this->assertSame(ContentPublishMode::ADMIN->value, $log->context['publish_mode']);
        $this->assertSame(ContentPublishMode::FILE_SAVE->value, $log->context['selected_publish_mode']);
        $this->assertSame(['content'], $log->context['held']);
        $this->assertFalse($log->context['published']);
        // Counted for the admin's banner of changes waiting to be published.
        $this->assertSame(1, app(FileContentRegistry::class)->pendingCount());
        $this->assertFalse(app(FileContentRegistry::class)->usesFileSaveMode());

        $this->assertSame(0, FrontPageRevision::where('front_page_id', $page->id)
            ->where('type', FrontPageRevision::TYPE_AUTO)->count());
    }

    public function test_a_held_part_is_recorded_once_however_often_the_page_is_rendered(): void
    {
        FrontSetting::setValue(FrontPage::PUBLISH_MODE_SETTING, ContentPublishMode::FILE_SAVE->value);
        $page = $this->createFilePage();
        Storage::disk('local')->put('core/front/script.js', 'console.log(2);');

        // A held part keeps the two hashes apart for as long as it is held,
        // and check() runs on every render of a public page.
        for ($i = 0; $i < 5; $i++) {
            $this->content($page->fresh());
        }

        $this->assertSame(1, AuditLog::where('action', 'front_page.file_changed')->count());
    }

    public function test_a_further_file_change_after_a_held_one_is_recorded(): void
    {
        FrontSetting::setValue(FrontPage::PUBLISH_MODE_SETTING, ContentPublishMode::FILE_SAVE->value);
        $page = $this->createFilePage();

        Storage::disk('local')->put('core/front/script.js', 'console.log(2);');
        $this->content($page->fresh());

        Storage::disk('local')->put('core/front/script.js', 'console.log(3);');
        $this->content($page->fresh());

        $this->assertSame(2, AuditLog::where('action', 'front_page.file_changed')->count());
    }

    public function test_switching_to_the_file_save_mode_elsewhere_leaves_a_change_waiting(): void
    {
        $page = $this->createMarkdownFilePage();
        Storage::disk('local')->put('core/front/content.md', '# Edited on disk');
        $this->assertSame('# Published', $this->content($page));

        FrontSetting::setValue(FrontPage::PUBLISH_MODE_SETTING, ContentPublishMode::FILE_SAVE->value);

        $this->assertSame('# Published', $this->content($page->fresh()));
        $this->assertSame(1, app(FileContentRegistry::class)->pendingCount());
    }

    public function test_switching_to_the_file_save_mode_locally_shows_a_waiting_markdown_body(): void
    {
        $page = $this->createMarkdownFilePage();
        $this->useLocalEnvironment();
        Storage::disk('local')->put('core/front/content.md', '# Edited on disk');
        $this->assertSame('# Published', $this->content($page));

        FrontSetting::setValue(FrontPage::PUBLISH_MODE_SETTING, ContentPublishMode::FILE_SAVE->value);

        $this->assertSame('# Edited on disk', $this->content($page->fresh()));
        // Still waiting, and recorded only once.
        $this->assertSame(1, app(FileContentRegistry::class)->pendingCount());
        $this->assertSame(1, AuditLog::where('action', 'front_page.file_changed')->count());
    }

    public function test_a_production_site_on_the_file_save_mode_is_not_warned_on_the_dashboard(): void
    {
        // The mode does not take effect there, so there is nothing to warn
        // about; the settings screen says why instead.
        FrontSetting::setValue(FrontPage::PUBLISH_MODE_SETTING, ContentPublishMode::FILE_SAVE->value);
        $this->createFilePage();
        $this->app->detectEnvironment(fn () => 'production');

        $this->assertNull($this->healthItem('file_content_mode'));
    }

    public function test_the_settings_screen_says_where_the_file_save_mode_applies(): void
    {
        $this->actingAs($this->admin, 'member')
            ->get(route('admin.front.settings'))
            ->assertOk()
            ->assertSee(__('common.content_publish_mode.not_in_effect'), false)
            ->assertDontSee(__('common.content_publish_mode.in_effect_local'), false);

        $this->useLocalEnvironment();

        $this->actingAs($this->admin, 'member')
            ->get(route('admin.front.settings'))
            ->assertOk()
            ->assertSee(__('common.content_publish_mode.in_effect_local'), false);
    }

    public function test_an_html_front_page_names_every_part_as_script_capable(): void
    {
        $this->assertSame(
            ['content', 'custom_css', 'custom_js'],
            app(FileContentPublisher::class)->scriptCapableParts($this->createFilePage()),
        );
    }

    public function test_a_markdown_front_page_names_no_part_as_script_capable(): void
    {
        // A Markdown body cannot carry a script, and such a page has no
        // custom CSS or JS part at all, so nothing of it is ever held back.
        $this->assertSame(
            [],
            app(FileContentPublisher::class)->scriptCapableParts($this->createMarkdownFilePage()),
        );
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

    public function test_a_change_found_by_a_render_says_where_it_came_from(): void
    {
        $page = $this->createFilePage();
        $publishedHash = $page->content_hash;
        Storage::disk('local')->put('core/front/content.html', '<h1>Edited on disk</h1>');

        $this->content($page);

        $log = AuditLog::where('action', 'front_page.file_changed')->sole();
        $this->assertSame(RecordFileContentChangeAction::SOURCE_OUTSIDE_ADMIN, $log->context['source']);
        $this->assertSame(FileContentPublisher::DETECTED_BY_RENDER, $log->context['detected_by']);

        // Before and after: what was published, and what the files held.
        $this->assertSame($publishedHash, $log->context['published_hash']);
        $this->assertSame($page->fresh()->content_file_hash, $log->context['file_hash']);
        $this->assertNotSame($log->context['published_hash'], $log->context['file_hash']);
    }

    public function test_a_change_found_by_the_scan_says_so(): void
    {
        $this->createFilePage();
        Storage::disk('local')->put('core/front/content.html', '<h1>Edited on disk</h1>');

        app(FileContentRegistry::class)->checkAll(app(FileContentPublisher::class));

        $log = AuditLog::where('action', 'front_page.file_changed')->sole();
        $this->assertSame(FileContentPublisher::DETECTED_BY_SCAN, $log->context['detected_by']);
        $this->assertSame(RecordFileContentChangeAction::SOURCE_OUTSIDE_ADMIN, $log->context['source']);
    }

    public function test_publishing_records_the_hash_it_published(): void
    {
        $page = $this->createFilePage();
        Storage::disk('local')->put('core/front/content.html', '<h1>Edited on disk</h1>');
        $this->content($page);

        $this->actingAs($this->admin, 'member')
            ->post(route('admin.front.publish-file'))
            ->assertRedirect(route('admin.front.edit'));

        $log = AuditLog::where('action', 'front_page.published_from_file')->sole();
        $fresh = $page->fresh();

        // The approver is the member, and the hash is the one now published.
        $this->assertSame($this->admin->id, $log->actor_id);
        $this->assertSame($fresh->content_hash, $log->context['published_hash']);

        // It is the hash the file_changed entry reported as the file's.
        $changed = AuditLog::where('action', 'front_page.file_changed')->sole();
        $this->assertSame($changed->context['file_hash'], $log->context['published_hash']);
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

    /**
     * A file-stored page whose only part is a body that cannot carry a script
     */
    private function createMarkdownFilePage(): FrontPage
    {
        $this->actingAs($this->admin, 'member')->post(route('admin.front.store'), [
            'lang' => 'en',
            'editor_type' => ContentEditorType::MARKDOWN->slug(),
            'storage_type' => 'file',
            'content' => '# Published',
        ])->assertRedirect(route('admin.front.edit'));

        return FrontPage::findByType('main_content');
    }

    /**
     * One item of the dashboard's site health list, or null when absent
     *
     * @return array<string, mixed>|null
     */
    /**
     * Run the rest of the test as a local environment
     *
     * Only after the pages are created: the test-only CSRF exemption holds
     * while the environment is `testing`, so a POST made after this is refused.
     */
    private function useLocalEnvironment(): void
    {
        $this->app['env'] = 'local';
    }

    private function healthItem(string $key): ?array
    {
        return collect(DashboardPresenter::siteHealth($this->admin))->firstWhere('key', $key);
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
