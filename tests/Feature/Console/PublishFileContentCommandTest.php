<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
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

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Enums\ContentEditorType;
use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Models\AuditLog;
use App\Models\FrontPage;
use App\Models\FrontPageRevision;
use App\Models\Member;
use App\Services\Content\FileContentRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * `dls:content:publish-files` lists and publishes file changes (#805)
 *
 * Exercised with the core front page, which is registered with the
 * file-content registry like any extension's model.
 */
class PublishFileContentCommandTest extends TestCase
{
    use RefreshDatabase;

    private Member $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(EnsureEmailIsVerified::class);

        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';
        config()->set('app.installed', true);

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
        putenv('INSTALLED=false');
        $_ENV['INSTALLED'] = 'false';
        parent::tearDown();
    }

    public function test_with_nothing_waiting_it_says_so(): void
    {
        $this->createFilePage();

        $this->artisan('dls:content:publish-files', ['--fail-if-pending' => true])
            ->expectsOutputToContain('No file-stored content is waiting to be published.')
            ->assertSuccessful();
    }

    public function test_without_options_it_lists_and_publishes_nothing(): void
    {
        $page = $this->createFilePage();
        Storage::disk('local')->put('core/front/content.md', '# Edited on disk');

        $this->artisan('dls:content:publish-files')
            ->expectsOutputToContain('FrontPage #'.$page->id)
            ->expectsOutputToContain('1 record(s) have file changes waiting to be published.')
            ->assertSuccessful();

        $this->assertSame('# Published', $page->fresh()->content);
        $this->assertSame(1, app(FileContentRegistry::class)->pendingCount());
        // The change it found is recorded, as the scheduled scan would.
        $log = AuditLog::where('action', 'front_page.file_changed')->sole();
        $this->assertSame('scan', $log->context['detected_by']);
        $this->assertSame(0, AuditLog::where('action', 'front_page.published_from_file')->count());
    }

    public function test_listing_fails_when_asked_to_and_something_is_waiting(): void
    {
        $this->createFilePage();
        Storage::disk('local')->put('core/front/content.md', '# Edited on disk');

        $this->artisan('dls:content:publish-files', ['--fail-if-pending' => true])
            ->assertFailed();
    }

    public function test_publish_takes_the_files_in_with_a_revision_and_an_audit_entry(): void
    {
        $page = $this->createFilePage();
        Storage::disk('local')->put('core/front/content.md', '# Edited on disk');

        $this->artisan('dls:content:publish-files', ['--publish' => true, '--fail-if-pending' => true])
            ->expectsOutputToContain('— published')
            ->expectsOutputToContain('1 record(s) published, 0 failed.')
            ->assertSuccessful();

        $this->assertSame('# Edited on disk', $page->fresh()->content);
        $this->assertSame(0, app(FileContentRegistry::class)->pendingCount());

        $log = AuditLog::where('action', 'front_page.published_from_file')->sole();
        $this->assertSame('console', $log->actor_name);
        $this->assertSame(['content'], $log->context['parts']);

        $revision = FrontPageRevision::where('front_page_id', $page->id)->latest('id')->first();
        $this->assertSame(FrontPageRevision::TYPE_MANUAL, $revision->type);
    }

    public function test_model_narrows_the_run_by_its_short_or_full_class_name(): void
    {
        $page = $this->createFilePage();
        Storage::disk('local')->put('core/front/content.md', '# Edited on disk');

        $this->artisan('dls:content:publish-files', ['--model' => 'FrontPage', '--id' => (string) $page->id])
            ->expectsOutputToContain('FrontPage #'.$page->id)
            ->assertSuccessful();

        $this->artisan('dls:content:publish-files', ['--model' => FrontPage::class, '--publish' => true])
            ->assertSuccessful();

        $this->assertSame('# Edited on disk', $page->fresh()->content);
    }

    public function test_an_unknown_model_is_refused(): void
    {
        $this->artisan('dls:content:publish-files', ['--model' => 'NoSuchModel'])
            ->expectsOutputToContain('No registered model matches "NoSuchModel".')
            ->assertFailed();
    }

    public function test_id_needs_a_model(): void
    {
        $this->artisan('dls:content:publish-files', ['--id' => '1'])
            ->expectsOutputToContain('--id needs --model.')
            ->assertFailed();
    }

    private function createFilePage(): FrontPage
    {
        $this->actingAs($this->admin, 'member')->post(route('admin.front.store'), [
            'lang' => 'en',
            'editor_type' => ContentEditorType::MARKDOWN->slug(),
            'storage_type' => 'file',
            'content' => '# Published',
        ])->assertRedirect(route('admin.front.edit'));

        return FrontPage::findByType('main_content');
    }
}
