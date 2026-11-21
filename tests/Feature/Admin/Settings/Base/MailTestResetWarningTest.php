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

namespace Tests\Feature\Admin\Settings\Base;

use App\Enums\AuthenticationMode;
use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\CheckMenuAccess;
use App\Http\Middleware\CheckMenuEdit;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Models\Member;
use App\Models\MemberTwoFaRecoveryCode;
use App\Models\SecuritySetting;
use App\Models\SiteSetting;
use App\Services\Mail\MailTestStatusMonitor;
use App\Services\SystemWarningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Tests\Concerns\BypassesRecentAuthentication;
use Tests\TestCase;

/**
 * Resetting the mail tests is reported where the operator can act on it:
 * in the save result of the mail settings, and in a warning banner while
 * accounts depend on e-mailed sign-in codes.
 */
class MailTestResetWarningTest extends TestCase
{
    use BypassesRecentAuthentication;
    use RefreshDatabase;

    private Member $admin;

    private ?string $envBackup = null;

    protected function setUp(): void
    {
        parent::setUp();

        App::setLocale('en');

        // Mail is configured, so two-factor authentication is in effect.
        SiteSetting::setValue('mail_mailer', 'smtp');
        SiteSetting::setValue('mail_host', 'mailpit');
        SiteSetting::setValue('mail_port', '1025');
        SiteSetting::setValue('mail_from_address', 'system-from@example.org');
        SecuritySetting::setValue('two_fa_mode', (string) AuthenticationMode::UseProfileSetting->value);

        $this->admin = $this->member('mailadmin', AuthenticationMode::Disabled, MemberRole::SUPER_ADMIN);
    }

    protected function tearDown(): void
    {
        // The settings controller writes the delivery fields to .env.
        if ($this->envBackup !== null) {
            file_put_contents(base_path('.env'), $this->envBackup);
        }
        putenv('INSTALLED=false');
        $_ENV['INSTALLED'] = 'false';

        parent::tearDown();
    }

    public function test_counts_active_accounts_asked_for_a_second_step(): void
    {
        $withCodes = $this->member('withcodes', AuthenticationMode::Always);
        $this->giveRecoveryCode($withCodes);
        $usedUp = $this->member('usedup', AuthenticationMode::DifferentDevice);
        $this->giveRecoveryCode($usedUp, used: true);
        $this->member('inactive', AuthenticationMode::Always, status: MemberStatus::Inactive);

        $this->assertSame(
            ['two_fa' => 2, 'without_recovery_codes' => 1],
            $this->monitor()->affectedAccounts(),
        );

        // A site-wide mode asks every active account.
        SecuritySetting::setValue('two_fa_mode', (string) AuthenticationMode::Always->value);
        $this->assertSame(3, $this->monitor()->affectedAccounts()['two_fa']);

        SecuritySetting::setValue('two_fa_mode', (string) AuthenticationMode::Disabled->value);
        $this->assertSame(0, $this->monitor()->affectedAccounts()['two_fa']);
    }

    public function test_banner_is_a_warning_for_editors_of_the_mail_settings_only(): void
    {
        $this->member('nocodes', AuthenticationMode::Always);

        // Nobody signed in: nothing is shown.
        $this->assertSame([], $this->banners());

        $this->actingAs($this->admin, 'member');
        $banners = $this->banners();

        $this->assertCount(1, $banners);
        $this->assertSame('warning', $banners[0]['level']);
        $this->assertSame(__('admin/settings/base/mail.tests_reset.banner_title'), $banners[0]['title']);
        $this->assertStringContainsString(
            __('admin/settings/base/mail.tests_reset.accounts_without_codes', ['count' => 1]),
            $banners[0]['message'],
        );
        $this->assertSame(route('admin.settings.base.mail'), $banners[0]['actions'][0]['url']);
    }

    public function test_no_banner_once_the_tests_pass_or_when_no_account_uses_two_factor(): void
    {
        $this->actingAs($this->admin, 'member');

        // Only the admin, with two-factor authentication off.
        $this->assertSame([], $this->banners());

        $this->member('nocodes', AuthenticationMode::Always);
        $this->assertCount(1, $this->banners());

        foreach (['mail_connection_tested', 'mail_send_tested', 'mail_receive_tested'] as $key) {
            SiteSetting::setValue($key, 1);
        }
        $this->assertSame([], $this->banners());
    }

    public function test_saving_a_changed_delivery_field_reports_the_reset(): void
    {
        $this->prepareAdminRequest();
        $this->member('nocodes', AuthenticationMode::Always);
        foreach (['mail_connection_tested', 'mail_send_tested', 'mail_receive_tested'] as $key) {
            SiteSetting::setValue($key, 1);
        }

        $response = $this->actingAs($this->admin, 'member')
            ->post(route('admin.settings.base.mail.update'), [
                'mail_mailer' => 'smtp',
                'mail_host' => 'mailpit',
                'mail_port' => '1025',
                'mail_username' => 'rotated-user',
                'mail_password' => 'rotated-password-'.uniqid(),
                'mail_encryption' => 'tls',
                'mail_from_address' => 'system-from@example.org',
                'system_admin_email' => 'ops@example.org',
            ]);

        $response->assertRedirect(route('admin.settings.base.mail'));
        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');
        $response->assertSessionHas('warning', function (string $warning): bool {
            return str_contains($warning, __('admin/settings/base/mail.tests_reset.saved'))
                && str_contains($warning, __('admin/settings/base/mail.tests_reset.accounts', ['count' => 1]));
        });
        $this->assertFalse((bool) SiteSetting::getValue('mail_send_tested', false));
    }

    private function prepareAdminRequest(): void
    {
        $envPath = base_path('.env');
        if (! is_file($envPath)) {
            $this->markTestSkipped('The settings controller writes to .env, which this checkout does not have.');
        }
        $this->envBackup = (string) file_get_contents($envPath);

        $this->withoutMiddleware([
            CheckInstallationReady::class,
            CheckMenuAccess::class,
            CheckMenuEdit::class,
            EnsureEmailIsVerified::class,
            \App\Http\Middleware\SetAdminLocale::class,
        ]);

        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';

        $adminTheme = config('themes.admin_theme', 'admin');
        $customFilesDir = base_path(config('custom.custom_files_dir', 'custom'));
        View::addNamespace('admin', [
            base_path("{$customFilesDir}/resources/views/{$adminTheme}"),
            resource_path("views/{$adminTheme}"),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function banners(): array
    {
        return $this->app->make(SystemWarningService::class)->getActiveBanners();
    }

    private function monitor(): MailTestStatusMonitor
    {
        return $this->app->make(MailTestStatusMonitor::class);
    }

    private function member(
        string $name,
        AuthenticationMode $twoFaMode,
        MemberRole $role = MemberRole::EDITOR,
        MemberStatus $status = MemberStatus::Active,
    ): Member {
        return Member::create([
            'account_name' => $name,
            'display_name' => ucfirst($name),
            'email' => $name.'@example.org',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => $role,
            'status' => $status,
            'two_fa_mode' => $twoFaMode,
        ]);
    }

    private function giveRecoveryCode(Member $member, bool $used = false): void
    {
        MemberTwoFaRecoveryCode::create([
            'member_id' => $member->id,
            'code' => Hash::make('code-'.$member->id),
            'used_at' => $used ? now() : null,
            'disabled' => false,
        ]);
    }
}
