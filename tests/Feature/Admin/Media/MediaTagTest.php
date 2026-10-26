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

namespace Tests\Feature\Admin\Media;

use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\CheckMenuAccess;
use App\Http\Middleware\CheckMenuEdit;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Models\AuditLog;
use App\Models\Media;
use App\Models\MediaTag;
use App\Models\Member;
use App\Models\Site;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * Tags of the media library (#766): tagging one file from its page, bulk
 * tagging from the selection mode, renaming and deleting a tag, and the
 * tag filters of the library, the selector's API and the search.
 */
class MediaTagTest extends TestCase
{
    use RefreshDatabase;

    private Member $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            CheckInstallationReady::class,
            CheckMenuAccess::class,
            CheckMenuEdit::class,
            EnsureEmailIsVerified::class,
        ]);

        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';

        $adminTheme = config('themes.admin_theme', 'admin');
        $customFilesDir = base_path(config('custom.custom_files_dir', 'custom'));
        View::addNamespace('admin', [
            base_path("{$customFilesDir}/resources/views/{$adminTheme}"),
            resource_path("views/{$adminTheme}"),
        ]);

        SiteSetting::setValue('site_name', 'Test Site');

        $this->admin = $this->member(MemberRole::SUPER_ADMIN);

        Storage::fake(config('admin.files.storageDisk', 'public'));
    }

    protected function tearDown(): void
    {
        putenv('INSTALLED=false');
        $_ENV['INSTALLED'] = 'false';
        parent::tearDown();
    }

    public function test_the_media_page_sets_the_tags_of_a_file_creating_new_ones(): void
    {
        $media = $this->makeMedia('card.png');
        MediaTag::create(['name' => 'blog']);

        $this->actingAs($this->admin, 'member')
            ->put(route('admin.media.update', $media), [
                'alt_text' => 'Card',
                'tags_submitted' => '1',
                'tags' => ['blog', '  写真　 2026 ', 'blog'],
            ])
            ->assertRedirect(route('admin.media.preview', $media));

        $this->assertSame(['blog', '写真 2026'], $media->fresh()->tags->pluck('name')->sort()->values()->all());
        $this->assertSame(2, MediaTag::count());
        $this->assertSame('Card', $media->fresh()->alt_text);
    }

    public function test_an_emptied_tag_input_clears_the_tags_and_a_form_without_it_keeps_them(): void
    {
        $media = $this->makeMedia();
        $media->tags()->attach(MediaTag::create(['name' => 'blog']));

        $this->actingAs($this->admin, 'member')
            ->put(route('admin.media.update', $media), ['alt_text' => 'x']);
        $this->assertCount(1, $media->fresh()->tags);

        $this->actingAs($this->admin, 'member')
            ->put(route('admin.media.update', $media), ['alt_text' => 'x', 'tags_submitted' => '1']);
        $this->assertCount(0, $media->fresh()->tags);
    }

    public function test_a_change_of_tags_is_audited_and_an_unchanged_set_is_not(): void
    {
        $media = $this->makeMedia();

        $this->actingAs($this->admin, 'member')
            ->put(route('admin.media.update', $media), ['tags_submitted' => '1', 'tags' => ['blog']]);
        $this->actingAs($this->admin, 'member')
            ->put(route('admin.media.update', $media), ['tags_submitted' => '1', 'tags' => ['blog']]);

        $logs = AuditLog::where('action', 'media.tags_updated')->get();
        $this->assertCount(1, $logs);
        $this->assertSame(['blog'], $logs->first()->context['added']);
        $this->assertSame([], $logs->first()->context['removed']);
    }

    public function test_bulk_tagging_adds_a_tag_to_every_selected_file_and_removes_one(): void
    {
        $a = $this->makeMedia('a.png');
        $b = $this->makeMedia('b.png');
        $untouched = $this->makeMedia('c.png');

        $this->actingAs($this->admin, 'member')
            ->post(route('admin.media.tags.bulk'), ['ids' => [$a->id, $b->id], 'add' => ['ogp', 'ja']])
            ->assertRedirect();

        $this->assertSame(2, MediaTag::count());
        $this->assertSame(['ja', 'ogp'], $a->fresh()->tags->pluck('name')->all());
        $this->assertSame(['ja', 'ogp'], $b->fresh()->tags->pluck('name')->all());
        $this->assertCount(0, $untouched->fresh()->tags);

        $ja = MediaTag::where('name', 'ja')->firstOrFail();
        $this->actingAs($this->admin, 'member')
            ->post(route('admin.media.tags.bulk'), ['ids' => [$a->id], 'remove' => [$ja->id]]);

        $this->assertSame(['ogp'], $a->fresh()->tags->pluck('name')->all());
        $this->assertSame(['ja', 'ogp'], $b->fresh()->tags->pluck('name')->all());
    }

    public function test_bulk_tagging_with_no_tag_changes_nothing(): void
    {
        $a = $this->makeMedia();

        $this->actingAs($this->admin, 'member')
            ->post(route('admin.media.tags.bulk'), ['ids' => [$a->id]])
            ->assertSessionHas('error');

        $this->assertSame(0, MediaTag::count());
    }

    public function test_a_tag_can_be_renamed_but_not_to_a_name_another_tag_has(): void
    {
        $tag = MediaTag::create(['name' => 'photo']);
        MediaTag::create(['name' => '画像']);

        $this->actingAs($this->admin, 'member')
            ->put(route('admin.media.tags.rename', $tag), ['name' => ' 写真 '])
            ->assertSessionHasNoErrors();
        $this->assertSame('写真', $tag->fresh()->name);

        $this->actingAs($this->admin, 'member')
            ->put(route('admin.media.tags.rename', $tag), ['name' => '画像'])
            ->assertSessionHasErrors('name');
        $this->assertSame('写真', $tag->fresh()->name);

        $this->assertSame(1, AuditLog::where('action', 'media.tag_renamed')->count());
    }

    /**
     * The database's default collation compares は and ば (and ハハ and
     * パパ) as equal; the tag name column must not.
     */
    public function test_names_that_differ_only_in_kana_marks_are_different_tags(): void
    {
        $media = $this->makeMedia();

        $this->actingAs($this->admin, 'member')
            ->put(route('admin.media.update', $media), ['tags_submitted' => '1', 'tags' => ['ハハ', 'パパ']]);

        $this->assertSame(2, MediaTag::count());
    }

    public function test_deleting_a_tag_leaves_its_files(): void
    {
        $media = $this->makeMedia();
        $tag = MediaTag::create(['name' => 'old']);
        $media->tags()->attach($tag);

        $this->actingAs($this->admin, 'member')
            ->delete(route('admin.media.tags.destroy', $tag))
            ->assertRedirect();

        $this->assertNull(MediaTag::find($tag->id));
        $this->assertNotNull(Media::find($media->id));
        $this->assertCount(0, $media->fresh()->tags);
        $log = AuditLog::where('action', 'media.tag_deleted')->firstOrFail();
        $this->assertSame(1, $log->context['media_count']);
    }

    public function test_a_member_who_may_not_upload_media_cannot_change_tags(): void
    {
        $tag = MediaTag::create(['name' => 'keep']);

        $this->actingAs($this->member(MemberRole::GUEST), 'member')
            ->put(route('admin.media.tags.rename', $tag), ['name' => 'changed'])
            ->assertForbidden();

        $this->assertSame('keep', $tag->fresh()->name);
    }

    public function test_the_tag_list_carries_only_id_name_and_a_count_of_live_files_of_this_site(): void
    {
        $tag = MediaTag::create(['name' => 'blog']);
        $live = $this->makeMedia('live.png');
        $trashed = $this->makeMedia('trashed.png');
        $live->tags()->attach($tag);
        $trashed->tags()->attach($tag);
        $trashed->delete();

        $other = Site::factory()->create();
        MediaTag::create(['site_id' => $other->id, 'name' => 'elsewhere']);

        $response = $this->actingAs($this->admin, 'member')
            ->getJson(route('admin.media.tags.list'))
            ->assertOk();

        $this->assertSame([['id' => $tag->id, 'name' => 'blog', 'count' => 1]], $response->json('tags'));
    }

    public function test_the_selector_api_filters_by_tag_and_its_search_matches_tag_names(): void
    {
        $tagged = $this->makeMedia('a.png');
        $this->makeMedia('b.png');
        $tag = MediaTag::create(['name' => 'security']);
        $tagged->tags()->attach($tag);

        $byTag = $this->actingAs($this->admin, 'member')
            ->getJson(route('admin.media.api', ['tag' => $tag->id]))
            ->assertOk();
        $this->assertSame([$tagged->id], array_column($byTag->json('media.data'), 'id'));

        $bySearch = $this->actingAs($this->admin, 'member')
            ->getJson(route('admin.media.api', ['search' => 'secur']))
            ->assertOk();
        $this->assertSame([$tagged->id], array_column($bySearch->json('media.data'), 'id'));
    }

    public function test_the_library_lists_tags_escaped_and_narrows_to_one(): void
    {
        $tagged = $this->makeMedia('tagged.png');
        $this->makeMedia('other.png');
        $tag = MediaTag::create(['name' => '<script>alert(1)</script>']);
        $tagged->tags()->attach($tag);

        $response = $this->actingAs($this->admin, 'member')
            ->get(route('admin.media.index', ['tag' => $tag->id]))
            ->assertOk();

        $response->assertDontSee('<script>alert(1)</script>', false);
        $response->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
        $response->assertSee('tagged.png');
        $response->assertDontSee('other.png');
        // The search form's tag select keeps the narrowing
        $response->assertSee('value="'.$tag->id.'" selected', false);
    }

    public function test_the_tag_page_lists_tags_escaped_with_their_counts(): void
    {
        $tag = MediaTag::create(['name' => '<b>x</b>']);
        $this->makeMedia()->tags()->attach($tag);

        $response = $this->actingAs($this->admin, 'member')
            ->get(route('admin.media.tags'))
            ->assertOk();

        $response->assertDontSee('<b>x</b>', false);
        $response->assertSee('&lt;b&gt;x&lt;/b&gt;', false);
        $response->assertSee(route('admin.media.index', ['tag' => $tag->id]), false);
        $response->assertSee(route('admin.media.tags.destroy', ['mediaTag' => '__ID__']), false);
    }

    public function test_a_tag_can_be_added_on_the_tag_page_but_not_twice(): void
    {
        $this->actingAs($this->admin, 'member')
            ->post(route('admin.media.tags.store'), ['name' => '  お知らせ '])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame(['お知らせ'], MediaTag::pluck('name')->all());
        $this->assertSame('お知らせ', AuditLog::where('action', 'media.tag_created')->firstOrFail()->context['name']);

        $this->actingAs($this->admin, 'member')
            ->post(route('admin.media.tags.store'), ['name' => 'お知らせ'])
            ->assertSessionHasErrors('name');

        $this->assertSame(1, MediaTag::count());
    }

    public function test_a_member_who_may_not_upload_media_cannot_add_a_tag(): void
    {
        $this->actingAs($this->member(MemberRole::GUEST), 'member')
            ->post(route('admin.media.tags.store'), ['name' => 'nope'])
            ->assertForbidden();

        $this->assertSame(0, MediaTag::count());
    }

    private function makeMedia(string $name = 'sample.png'): Media
    {
        $disk = config('admin.files.storageDisk', 'public');
        $mediaPath = config('admin.files.mediaPath', 'media');
        Storage::disk($disk)->put("{$mediaPath}/{$name}", 'stub');

        return Media::create([
            'name' => $name,
            'path' => $name,
            'type' => 'image/png',
            'file_size' => 4,
            'uploaded_by' => $this->admin->id,
        ]);
    }

    private function member(MemberRole $role): Member
    {
        return Member::create([
            'account_name' => 'member-'.$role->value,
            'display_name' => 'Member '.$role->value,
            'email' => 'member-'.$role->value.'@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => $role,
            'status' => MemberStatus::Active,
        ]);
    }
}
