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
 * Regression test for the admin mail send test:
 *
 * - The test mail must be delivered to the logged-in admin's own email
 *   address (the operator running the 3-stage test can always open it).
 * - The test mail must be sent FROM the configured "from" address, so the
 *   test exercises the real outgoing-mail identity.
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
            'mail_from_address' => 'system-from@example.com',
            'mail_from_name' => 'Test Sender',
        ], $overrides);
    }

    public function test_test_mail_is_sent_to_the_logged_in_account_from_the_configured_address(): void
    {
        $capturedTo = [];
        $capturedFrom = [];
        Event::listen(MessageSending::class, function (MessageSending $event) use (&$capturedTo, &$capturedFrom) {
            $capturedTo = array_map(fn ($address) => $address->getAddress(), $event->message->getTo());
            $capturedFrom = array_map(fn ($address) => $address->getAddress(), $event->message->getFrom());

            // Cancel the actual delivery — this test only verifies routing.
            return false;
        });

        $response = $this->actingAs($this->admin, 'member')
            ->postJson(route('admin.settings.base.mail.test-mail'), $this->mailSettings());

        $response->assertOk();
        $response->assertJson(['success' => true]);

        // To = the logged-in admin's own email, so the operator can open the
        // mail and complete the receive-confirmation test.
        $this->assertSame(['logged-in-admin@example.com'], $capturedTo);

        // From = the configured "from" address.
        $this->assertSame(['system-from@example.com'], $capturedFrom);
    }
}
