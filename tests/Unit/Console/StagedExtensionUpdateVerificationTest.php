<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

namespace Tests\Unit\Console;

use App\Console\Traits\AutoScansExtensionAfterUpdate;
use App\DTO\Plugin\SignatureVerificationResult;
use App\Enums\ExtensionSecurityPreset;
use App\Exceptions\ExtensionUpdateBlockedException;
use App\Services\Plugin\CoreSignatureVerifier;
use App\Services\SecuritySettingsRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * An update is checked while it is still staged.
 *
 * The staged tree sits outside plugins/ and themes/, so nothing loads it
 * while the check runs: a tree whose files do not match its signed manifest
 * never reaches the directory the application reads. The verdict follows the
 * enable gate -- a signature that does not hold is refused outright, and an
 * unsigned tree is refused only where the settings require a signature.
 */
class StagedExtensionUpdateVerificationTest extends TestCase
{
    use RefreshDatabase;

    private object $subject;

    protected function setUp(): void
    {
        parent::setUp();

        // The gate lives on a trait both update commands use; an anonymous
        // holder exercises it without booting a whole command.
        $this->subject = new class
        {
            use AutoScansExtensionAfterUpdate;

            public function gate(string $kind, string $slug, string $path): void
            {
                $this->refuseUnverifiedStagedTree($kind, $slug, $path);
            }
        };
    }

    private function verifierReturns(SignatureVerificationResult $result): void
    {
        $this->mock(CoreSignatureVerifier::class, function ($mock) use ($result): void {
            $mock->shouldReceive('verifyDirectory')->andReturn($result);
        });
    }

    public function test_a_signature_that_does_not_hold_is_refused(): void
    {
        $this->verifierReturns(SignatureVerificationResult::invalid('files do not match'));

        $this->expectException(ExtensionUpdateBlockedException::class);

        $this->subject->gate('plugin', 'dixlase-cookie', '/tmp/staged');
    }

    public function test_a_theme_whose_signature_does_not_hold_is_refused_too(): void
    {
        $this->verifierReturns(SignatureVerificationResult::invalid('files do not match'));

        $this->expectException(ExtensionUpdateBlockedException::class);

        $this->subject->gate('theme', 'dixlase-onepage', '/tmp/staged');
    }

    public function test_an_unsigned_tree_passes_under_the_default_preset(): void
    {
        SecuritySettingsRegistry::set('extension_security_preset', ExtensionSecurityPreset::default()->value);
        $this->verifierReturns(SignatureVerificationResult::unsigned());

        $this->subject->gate('plugin', 'dixlase-cookie', '/tmp/staged');

        $this->assertTrue(true, 'An unsigned extension keeps updating where the settings allow it.');
    }

    public function test_an_unsigned_tree_is_refused_where_a_signature_is_required(): void
    {
        SecuritySettingsRegistry::set('extension_require_signature', '1');
        $this->verifierReturns(SignatureVerificationResult::unsigned());

        $this->expectException(ExtensionUpdateBlockedException::class);

        $this->subject->gate('plugin', 'dixlase-cookie', '/tmp/staged');
    }

    public function test_a_valid_signature_passes(): void
    {
        $this->verifierReturns(SignatureVerificationResult::valid('key-1'));

        $this->subject->gate('plugin', 'dixlase-cookie', '/tmp/staged');

        $this->assertTrue(true);
    }

    public function test_a_verifier_failure_is_refused_rather_than_ignored(): void
    {
        $this->mock(CoreSignatureVerifier::class, function ($mock): void {
            $mock->shouldReceive('verifyDirectory')->andThrow(new \RuntimeException('boom'));
        });

        $this->expectException(ExtensionUpdateBlockedException::class);

        $this->subject->gate('plugin', 'dixlase-cookie', '/tmp/staged');
    }

    public function test_both_update_commands_check_before_they_replace_the_live_tree(): void
    {
        foreach (['PluginUpdate', 'ThemeUpdate'] as $command) {
            $source = (string) file_get_contents(app_path("Console/Commands/{$command}.php"));

            $check = strpos($source, 'refuseUnverifiedStagedTree(');
            $delete = strpos($source, 'File::deleteDirectory($destinationDir)');
            $move = strpos($source, 'File::move($newSource, $destinationDir)');

            $this->assertNotFalse($check, "{$command} no longer checks the staged tree.");
            $this->assertNotFalse($delete, "{$command}: the replace step moved.");
            $this->assertNotFalse($move, "{$command}: the move step moved.");
            $this->assertLessThan($delete, $check, "{$command} must check before it deletes the live tree.");
            $this->assertLessThan($move, $check, "{$command} must check before it moves the staged tree in.");
        }
    }
}
