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
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Models\Media;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * The JSON list behind the media selector modal (x-media.selector). The
 * modal asks for one page at a time, so every item has to be reachable
 * through ?page=, and its search box and type filter send ?search= and
 * ?type=.
 */
class AdminMediaApiTest extends TestCase
{
    use RefreshDatabase;

    private Member $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            CheckInstallationReady::class,
            CheckMenuAccess::class,
            EnsureEmailIsVerified::class,
        ]);

        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';

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
        putenv('INSTALLED=false');
        $_ENV['INSTALLED'] = 'false';
        parent::tearDown();
    }

    public function test_every_item_is_reachable_page_by_page_newest_first(): void
    {
        $this->makeMedia(25);

        $first = $this->actingAs($this->admin, 'member')
            ->getJson(route('admin.media.api', ['page' => 1, 'per_page' => 20]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('media.current_page', 1)
            ->assertJsonPath('media.last_page', 2)
            ->assertJsonPath('media.total', 25)
            ->assertJsonCount(20, 'media.data');
        $this->assertSame('photo-25.jpg', $first->json('media.data.0.name'));

        $second = $this->actingAs($this->admin, 'member')
            ->getJson(route('admin.media.api', ['page' => 2, 'per_page' => 20]))
            ->assertOk()
            ->assertJsonPath('media.current_page', 2)
            ->assertJsonCount(5, 'media.data');
        $this->assertSame(
            ['photo-5.jpg', 'photo-4.jpg', 'photo-3.jpg', 'photo-2.jpg', 'photo-1.jpg'],
            array_column($second->json('media.data'), 'name'),
        );
    }

    public function test_each_item_carries_its_url(): void
    {
        $this->makeMedia(1);

        $this->actingAs($this->admin, 'member')
            ->getJson(route('admin.media.api'))
            ->assertOk()
            ->assertJsonPath('media.data.0.url', asset('storage/media/photo-1.jpg'));
    }

    public function test_search_narrows_the_list_by_name(): void
    {
        $this->makeFile('harbour-sunset.jpg', 'image/jpeg');
        $this->makeFile('harbour-map.pdf', 'application/pdf');
        $this->makeFile('team-photo.png', 'image/png');

        $response = $this->actingAs($this->admin, 'member')
            ->getJson(route('admin.media.api', ['search' => 'harbour']))
            ->assertOk()
            ->assertJsonPath('media.total', 2);

        $this->assertEqualsCanonicalizing(
            ['harbour-sunset.jpg', 'harbour-map.pdf'],
            array_column($response->json('media.data'), 'name'),
        );
    }

    /**
     * Authors search for what an image shows, which is what the alt text
     * says; the file name is often a camera number or a date.
     */
    public function test_search_matches_the_alt_text(): void
    {
        $this->makeFile('IMG_0412.jpg', 'image/jpeg');
        $this->makeFile('IMG_0413.jpg', 'image/jpeg');
        Media::where('name', 'IMG_0412.jpg')->update(['alt_text' => 'Lighthouse at dusk']);

        $response = $this->actingAs($this->admin, 'member')
            ->getJson(route('admin.media.api', ['search' => 'lighthouse']))
            ->assertOk()
            ->assertJsonPath('media.total', 1);

        $this->assertSame('IMG_0412.jpg', $response->json('media.data.0.name'));
    }

    public function test_type_narrows_the_list_by_mime_prefix(): void
    {
        $this->makeFile('a.jpg', 'image/jpeg');
        $this->makeFile('b.png', 'image/png');
        $this->makeFile('c.mp4', 'video/mp4');

        $response = $this->actingAs($this->admin, 'member')
            ->getJson(route('admin.media.api', ['type' => 'image']))
            ->assertOk()
            ->assertJsonPath('media.total', 2);

        $this->assertEqualsCanonicalizing(['a.jpg', 'b.png'], array_column($response->json('media.data'), 'name'));
    }

    public function test_document_type_matches_document_mime_types(): void
    {
        $this->makeFile('report.pdf', 'application/pdf');
        $this->makeFile('letter.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $this->makeFile('old-letter.doc', 'application/msword');
        $this->makeFile('notes.txt', 'text/plain');
        $this->makeFile('photo.jpg', 'image/jpeg');
        $this->makeFile('bundle.zip', 'application/zip');

        $response = $this->actingAs($this->admin, 'member')
            ->getJson(route('admin.media.api', ['type' => 'document']))
            ->assertOk()
            ->assertJsonPath('media.total', 4);

        $this->assertEqualsCanonicalizing(
            ['report.pdf', 'letter.docx', 'old-letter.doc', 'notes.txt'],
            array_column($response->json('media.data'), 'name'),
        );
    }

    public function test_search_and_type_combine_and_page_within_the_result(): void
    {
        for ($i = 1; $i <= 3; $i++) {
            $this->makeFile("harbour-{$i}.jpg", 'image/jpeg');
        }
        $this->makeFile('harbour-plan.pdf', 'application/pdf');
        $this->makeFile('market.jpg', 'image/jpeg');

        $this->actingAs($this->admin, 'member')
            ->getJson(route('admin.media.api', ['search' => 'harbour', 'type' => 'image', 'per_page' => 2, 'page' => 2]))
            ->assertOk()
            ->assertJsonPath('media.total', 3)
            ->assertJsonPath('media.last_page', 2)
            ->assertJsonCount(1, 'media.data');
    }

    private function makeFile(string $name, string $mime): void
    {
        Media::create([
            'site_id' => 1,
            'name' => $name,
            'path' => $name,
            'type' => $mime,
            'file_size' => 1024,
            'uploaded_by' => $this->admin->id,
        ]);
    }

    /**
     * Items photo-1 … photo-N, uploaded a minute apart in that order.
     */
    private function makeMedia(int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $media = Media::create([
                'site_id' => 1,
                'name' => "photo-{$i}.jpg",
                'path' => "photo-{$i}.jpg",
                'type' => 'image/jpeg',
                'file_size' => 1024,
                'uploaded_by' => $this->admin->id,
            ]);
            $media->forceFill(['created_at' => now()->subMinutes($count - $i)])->save();
        }
    }
}
