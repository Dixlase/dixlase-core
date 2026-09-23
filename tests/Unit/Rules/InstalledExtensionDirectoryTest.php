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

namespace Tests\Unit\Rules;

use App\Rules\InstalledExtensionDirectory;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The value this rule guards is interpolated into `base_path("plugins/$v")`
 * and handed to `dls:plugin:install`, which runs that directory's migrations.
 * The rule it replaced was `required|string`, so both a path outside
 * `plugins/` and a leftover move-aside copy reached the install command.
 *
 * Boots the framework for the translator only -- the rule itself just inspects
 * the string, and no test here touches the database.
 */
class InstalledExtensionDirectoryTest extends TestCase
{
    /**
     * @return list<string> the failure messages the rule produced
     */
    private function failures(mixed $value): array
    {
        $messages = [];

        (new InstalledExtensionDirectory())->validate(
            'directory',
            $value,
            function (string $message) use (&$messages): void {
                $messages[] = $message;
            }
        );

        return $messages;
    }

    /**
     * @return list<array{0: string}>
     */
    public static function installedNameProvider(): array
    {
        return [['DixlaseSEO'], ['DixlaseOnePage'], ['Blog'], ['A1']];
    }

    #[DataProvider('installedNameProvider')]
    public function test_an_installed_extension_name_passes(string $name): void
    {
        $this->assertSame([], $this->failures($name));
    }

    /**
     * Names produced by deploy tooling and operators when they set the
     * previous version aside instead of deleting it.
     *
     * @return list<array{0: string}>
     */
    public static function moveAsideNameProvider(): array
    {
        return [
            ['DixlaseOnePage.stale.20260817-211837'],
            ['DixlaseDeploy.stale.20260710-221107'],
            ['DixlaseSEO.bak'],
            ['_DixlaseOld'],
            ['.DixlaseHidden'],
        ];
    }

    #[DataProvider('moveAsideNameProvider')]
    public function test_a_move_aside_copy_is_rejected(string $name): void
    {
        $this->assertNotSame([], $this->failures($name), "accepted a copy: {$name}");
    }

    /**
     * The value used to reach `base_path("plugins/{$value}")` unchecked, so a
     * name that is not a bare directory name has to be refused before it can
     * become a path or a command argument.
     *
     * @return list<array{0: string}>
     */
    public static function nonBareNameProvider(): array
    {
        return [
            ['..'],
            ['../../etc'],
            ['Dixlase/SEO'],
            ['Dixlase\\SEO'],
            ['/etc/passwd'],
            ["Dixlase\x00SEO"],
            ['Dixlase SEO'],
            ['Dixlase-SEO'],
            ['0Dixlase'],
            [''],
        ];
    }

    #[DataProvider('nonBareNameProvider')]
    public function test_a_value_that_is_not_a_bare_name_is_rejected(string $value): void
    {
        $this->assertNotSame([], $this->failures($value), "accepted: {$value}");
    }

    public function test_a_non_string_is_rejected(): void
    {
        $this->assertNotSame([], $this->failures(null));
        $this->assertNotSame([], $this->failures(42));
        $this->assertNotSame([], $this->failures(['DixlaseSEO']));
    }

    /**
     * A copy and a malformed value fail for different reasons, so the operator
     * is told which one it is.
     */
    public function test_the_two_rejection_reasons_are_distinct(): void
    {
        $copy = $this->failures('DixlaseSEO.bak');
        $malformed = $this->failures('../etc');

        $this->assertCount(1, $copy);
        $this->assertCount(1, $malformed);
        $this->assertNotSame($copy[0], $malformed[0]);

        // A missing lang key would surface as the key itself, which is the
        // kind of message an operator cannot act on.
        foreach ([$copy[0], $malformed[0]] as $message) {
            $this->assertStringNotContainsString('validation.custom.', $message);
        }
    }
}
