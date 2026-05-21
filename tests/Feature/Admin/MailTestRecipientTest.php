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

namespace Tests\Feature\Admin;

use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\CheckMenuAccess;
use App\Http\Middleware\CheckMenuEdit;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Models\Member;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * Regression test: the admin mail send test must deliver the test mail to
 * the configured "from" address, matching the UI description, instead of
 * the logged-in member's own email address.
 */
class MailTestRecipientTest extends TestCase
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

        // The mail send test is gated behind a completed connection test.
        SiteSetting::setValue('mail_connection_tested', 1);

        $this->admin = Member::create([
            'account_name' => 'testadmin',
            'display_name' => 'Test Admin',
            'email' => 'logged-in-admin@example.com',
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

    /**
     * @return array<string, string> Default valid mail settings payload.
     */
    private function mailSettings(array $overrides = []): array
    {
        return array_merge([
            'mail_mailer' => 'smtp',
            'mail_host' => 'mailpit',
            'mail_port' => '1025',
            'mail_username' => '',
            'mail_password' => '',
            'mail_encryption' => 'tls',
            'mail_from_address' => 'from-address@example.com',
            'mail_from_name' => 'Test Sender',
        ], $overrides);
    }

    public function test_test_mail_is_sent_to_the_configured_from_address(): void
    {
        $capturedRecipients = [];
        Event::listen(MessageSending::class, function (MessageSending $event) use (&$capturedRecipients) {
            $capturedRecipients = array_map(
                fn ($address) => $address->getAddress(),
                $event->message->getTo()
            );

            // Cancel the actual delivery — this test only verifies routing.
            return false;
        });

        $response = $this->actingAs($this->admin, 'member')
            ->postJson(route('admin.settings.base.mail.test-mail'), $this->mailSettings());

        $response->assertOk();
        $response->assertJson(['success' => true]);

        // The test mail must go to the form's "from" address, NOT the
        // logged-in admin's own email address.
        $this->assertSame(['from-address@example.com'], $capturedRecipients);
        $this->assertNotContains('logged-in-admin@example.com', $capturedRecipients);
    }

    public function test_test_mail_fails_when_from_address_is_empty(): void
    {
        $response = $this->actingAs($this->admin, 'member')
            ->postJson(route('admin.settings.base.mail.test-mail'), $this->mailSettings([
                'mail_from_address' => '',
            ]));

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => __('mail-server/test.test_functions.from_address_required'),
        ]);
    }
}
