<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
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

namespace Tests\Feature\Notifications;

use App\Models\Member;
use App\Notifications\AdminMemberVerifiedNotification;
use App\Notifications\MemberVerifiedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Outgoing mail used to take the site name from env('APP_NAME', 'Dixlase').
 *
 * Laravel skips loading .env entirely once the config cache exists
 * (LoadEnvironmentVariables::bootstrap() returns early on
 * configurationIsCached()), so env() returns null there and the default wins.
 * Measured on this project with config:cache in place:
 *
 *     config('app.name')            = 'Dixlase Dev'
 *     env('APP_NAME', 'Dixlase')    = 'Dixlase'
 *
 * Every production deployment runs config:cache, so every notification was
 * signed with the literal string "Dixlase" instead of the site's own name.
 * It never reproduced in development, where the config cache is absent.
 *
 * config/app.php resolves 'name' from the same env var, but it is evaluated
 * while .env is still readable and the resulting value is written into
 * bootstrap/cache/config.php. Reading through config() is what makes the
 * value survive.
 *
 * These tests assert the value comes from config rather than from the
 * environment, which is exactly what the old code got wrong.
 */
class SiteNameFromConfigTest extends TestCase
{
    use RefreshDatabase;

    private const SITE_NAME = 'Site Name From Config';

    public function test_member_verification_mail_is_signed_with_the_configured_site_name(): void
    {
        config(['app.name' => self::SITE_NAME]);

        $member = Member::factory()->create();
        $mail = (new MemberVerifiedNotification())->toMail($member);

        $this->assertStringContainsString(
            self::SITE_NAME,
            $mail->salutation,
            'The signature must follow config(app.name); env() is null once the config is cached.'
        );
    }

    public function test_admin_notification_subject_and_signature_use_the_configured_site_name(): void
    {
        config(['app.name' => self::SITE_NAME]);

        $member = Member::factory()->create();
        $mail = (new AdminMemberVerifiedNotification($member, now()->toDateTimeString()))->toMail($member);

        $this->assertStringContainsString(
            self::SITE_NAME,
            $mail->subject,
            'The subject prefix must follow config(app.name).'
        );
        $this->assertStringContainsString(self::SITE_NAME, $mail->salutation);
    }

    /**
     * The behavioural tests above only cover the two notifications that are
     * cheap to construct. The property that actually matters is broader and
     * cannot be observed without building a config cache, so it is asserted
     * at the source level: nothing outside config/ may read APP_NAME.
     *
     * larastan reports this class of call, but the PHPStan job is advisory
     * (continue-on-error in test.yml), which is how the original defect
     * survived among ~650 other findings.
     */
    public function test_no_application_code_reads_app_name_from_the_environment(): void
    {
        $offenders = [];

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(app_path(), \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            foreach (file($file->getPathname()) as $index => $line) {
                // Skip comments, including the one in AdminInterfaceTrait that
                // explains why the call was removed.
                $trimmed = ltrim($line);
                if (str_starts_with($trimmed, '//') || str_starts_with($trimmed, '*')) {
                    continue;
                }

                if (str_contains($line, "env('APP_NAME'") || str_contains($line, 'env("APP_NAME"')) {
                    $offenders[] = str_replace(base_path().'/', '', $file->getPathname()).':'.($index + 1);
                }
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "APP_NAME must be read through config('app.name'). These call sites return the default once the config is cached:\n  ".implode("\n  ", $offenders)
        );
    }
}
