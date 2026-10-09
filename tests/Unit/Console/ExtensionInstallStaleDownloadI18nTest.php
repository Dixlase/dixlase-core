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

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The stale-download warning added in #456 shipped as a literal English
 * sentence in both install commands, so a Japanese operator saw one
 * English line in the middle of an otherwise translated run — and it is
 * the line that asks them to decide whether to install the older copy
 * (#487). The admin panel had the same message translated all along.
 *
 * This pins both halves: the commands go through `__()`, and the key
 * resolves in English and in Japanese.
 */
class ExtensionInstallStaleDownloadI18nTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function commandProvider(): array
    {
        return [
            'plugin' => ['Console/Commands/PluginInstall.php', 'admin/command/plugin-install.stale_download'],
            'theme' => ['Console/Commands/ThemeInstall.php', 'admin/command/theme-install.stale_download'],
        ];
    }

    #[DataProvider('commandProvider')]
    public function test_the_install_command_prints_the_stale_download_warning_through_a_translation_key(string $relative, string $key): void
    {
        $source = (string) file_get_contents(app_path($relative));

        $this->assertStringContainsString(
            "__('{$key}'",
            $source,
            "The stale-download warning must go through {$key}, not a literal string."
        );
        $this->assertStringNotContainsString(
            'has been released; this download is v',
            $source,
            'The English sentence must not be built inline any more, or the locale is ignored again.'
        );
    }

    #[DataProvider('commandProvider')]
    public function test_the_key_resolves_in_both_locales_with_every_placeholder_filled(string $relative, string $key): void
    {
        $replace = ['latest' => '0.1.2', 'slug' => 'dixlase-seo', 'version' => '0.1.1'];

        foreach (['en', 'ja'] as $locale) {
            $message = trans($key, $replace, $locale);

            $this->assertIsString($message);
            $this->assertNotSame($key, $message, "{$key} is not defined for the {$locale} locale.");

            foreach ($replace as $placeholder => $value) {
                $this->assertStringNotContainsString(
                    ':'.$placeholder,
                    $message,
                    "The {$locale} string leaves :{$placeholder} unsubstituted."
                );
                $this->assertStringContainsString(
                    $value,
                    $message,
                    "The {$locale} string drops the {$placeholder} value, so the operator cannot tell which versions are involved."
                );
            }
        }

        $this->assertNotSame(
            trans($key, $replace, 'en'),
            trans($key, $replace, 'ja'),
            'The Japanese string is still the English one.'
        );
    }
}
