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

use App\Actions\FrontPage\CreateFrontPageAction;
use App\Actions\FrontPage\UpdateFrontPageAction;
use App\Actors\MemberActor;
use App\Enums\ContentEditorType;
use App\Enums\ContentStorageType;
use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Models\FrontPage;
use App\Models\Member;
use App\Services\Content\ScriptAuthoringPolicy;
use App\Services\FrontPageContentService;
use App\Services\FrontPageRevisionService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Only ADMIN and above may author script-capable front-page content (#817).
 *
 * An HTML front page, and the custom CSS / JS of any front page, are
 * served from the site's own origin, so whatever is written there runs in
 * every visitor's browser -- an administrator's included, with their
 * session.
 *
 * The write path already refuses an editor: all three front-page actions
 * declare `Permission::SETTINGS_BASE`, which resolves to ADMIN, and have
 * since 2026-04-08. These tests pin that height and state the rule in the
 * policy's own terms, so the two cannot drift apart silently -- nothing
 * previously recorded that `SETTINGS_BASE` and "may author scripts" have
 * to stay the same height.
 *
 * An editor reaching the actions at all raises an AuthorizationException
 * before handle() runs, which is what the refusal tests assert.
 */
class FrontScriptAuthoringPolicyTest extends TestCase
{
    use RefreshDatabase;

    private Member $editor;

    private Member $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->editor = $this->member('ed', MemberRole::EDITOR);
        $this->admin = $this->member('ad', MemberRole::ADMIN);
    }

    private function member(string $name, MemberRole $role): Member
    {
        return Member::create([
            'account_name' => $name,
            'display_name' => $name,
            'email' => $name.'@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => $role,
            'status' => MemberStatus::Active,
        ]);
    }

    private function createAction(): CreateFrontPageAction
    {
        return app(CreateFrontPageAction::class);
    }

    private function updateAction(FrontPage $page): UpdateFrontPageAction
    {
        return new UpdateFrontPageAction(
            $page,
            app(FrontPageContentService::class),
            app(FrontPageRevisionService::class),
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function createData(array $overrides = []): array
    {
        return array_merge([
            'lang' => 'en',
            'editor_type' => 'markdown',
            'storage_type' => ContentStorageType::DATABASE->slug(),
            'content' => 'Body',
        ], $overrides);
    }

    // The policy itself

    public function test_the_policy_admits_admin_and_above_only(): void
    {
        $this->assertFalse(ScriptAuthoringPolicy::canAuthorScripts(null));
        $this->assertFalse(ScriptAuthoringPolicy::canAuthorScripts($this->editor));
        $this->assertTrue(ScriptAuthoringPolicy::canAuthorScripts($this->admin));
        $this->assertTrue(ScriptAuthoringPolicy::canAuthorScripts(
            $this->member('su', MemberRole::SUPER_ADMIN)
        ));
    }

    public function test_markdown_is_offered_to_everyone_and_html_only_above_the_bar(): void
    {
        $this->assertSame(['markdown'], ScriptAuthoringPolicy::creatableEditorSlugs($this->editor));

        // Only the role decides. Core's admin_mode defaults to simple, so
        // copying the Pages extension's simple-mode rule here would stop
        // every site creating an HTML front page.
        $this->assertSame(['markdown', 'html'], ScriptAuthoringPolicy::creatableEditorSlugs($this->admin));
    }

    public function test_only_an_html_body_needs_the_bar(): void
    {
        $this->assertTrue(ScriptAuthoringPolicy::canEditEditorType($this->editor, ContentEditorType::MARKDOWN));
        $this->assertFalse(ScriptAuthoringPolicy::canEditEditorType($this->editor, ContentEditorType::HTML));
        $this->assertTrue(ScriptAuthoringPolicy::canEditEditorType($this->admin, ContentEditorType::HTML));
    }

    // Creating

    public function test_an_editor_cannot_create_a_front_page_at_all(): void
    {
        // Not a policy refusal: the action's own required permission stops
        // an editor before handle() runs. Asserted so the day someone opens
        // the write path to a lower role, this test says what else has to
        // move with it.
        $this->expectException(AuthorizationException::class);

        $this->createAction()->execute(
            new MemberActor($this->editor),
            $this->createData(['editor_type' => 'html', 'custom_js' => 'alert(1)']),
        );
    }

    public function test_an_admin_can_create_a_markdown_front_page(): void
    {
        $result = $this->createAction()->execute(
            new MemberActor($this->admin),
            $this->createData(),
        );

        $this->assertTrue($result->success);
        $this->assertSame(1, FrontPage::query()->count());
    }

    public function test_script_submitted_with_a_markdown_page_is_not_stored(): void
    {
        // The editor type decides, not the field: a Markdown page never
        // carries JS, whoever submits it.
        $this->createAction()->execute(
            new MemberActor($this->admin),
            $this->createData(['custom_js' => 'alert(1)', 'custom_css' => 'p{}']),
        );

        $page = FrontPage::query()->firstOrFail();
        $this->assertNull($page->custom_js);
        $this->assertNull($page->custom_css);
    }

    // Updating

    public function test_an_editor_cannot_update_an_html_front_page(): void
    {
        $this->createAction()->execute(
            new MemberActor($this->admin),
            $this->createData(['editor_type' => 'html', 'content' => '<p>Admin body</p>', 'custom_js' => 'console.log(1)']),
        );
        $page = FrontPage::query()->firstOrFail();

        try {
            $this->updateAction($page)->execute(
                new MemberActor($this->editor),
                ['content' => '<p>Editor body</p>', 'custom_js' => 'alert(document.cookie)'],
            );
            $this->fail('An editor must not be able to update a front page.');
        } catch (AuthorizationException) {
            // Expected: the action's required permission refuses first.
        }

        // The administrator's content and script are left exactly as they were.
        $page->refresh();
        $this->assertSame('<p>Admin body</p>', $page->getAttributes()['content']);
        $this->assertSame('console.log(1)', $page->getAttributes()['custom_js']);
    }

    public function test_an_admin_can_update_an_html_front_page(): void
    {
        $this->createAction()->execute(
            new MemberActor($this->admin),
            $this->createData(['editor_type' => 'html', 'content' => '<p>One</p>']),
        );
        $page = FrontPage::query()->firstOrFail();

        $result = $this->updateAction($page)->execute(
            new MemberActor($this->admin),
            ['content' => '<p>Two</p>', 'custom_js' => 'console.log(2)'],
        );

        $this->assertTrue($result->success);
        $this->assertSame('console.log(2)', $page->refresh()->getAttributes()['custom_js']);
    }

    public function test_an_admin_can_still_update_a_markdown_front_page(): void
    {
        $this->createAction()->execute(new MemberActor($this->admin), $this->createData(['content' => 'One']));
        $page = FrontPage::query()->firstOrFail();

        $result = $this->updateAction($page)->execute(
            new MemberActor($this->admin),
            ['content' => 'Two'],
        );

        $this->assertTrue($result->success);
        $this->assertSame('Two', $page->refresh()->getAttributes()['content']);
    }
}
