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

use App\Console\Commands\SyncGitExclude;
use App\Console\Commands\SyncGitIgnore;
use App\Helpers\GitExcludeHelper;
use App\Helpers\GitIgnoreHelper;
use App\Support\ExtensionReincludeRules;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

/**
 * Core must not re-include extension directories in its own git (#626).
 *
 * Older versions wrote `!plugins/<Name>/` / `!themes/<Name>/` into the
 * tracked .gitignore and `!plugins/<Name>` into .git/info/exclude on every
 * install. Nothing writes them any more, and `dls:sync-gitignore` /
 * `dls:sync-git-exclude` remove the ones left behind.
 *
 * The commands run against throwaway files under storage/framework/testing/,
 * never against the repository's own .gitignore or .git.
 */
class ExtensionReincludeRulesCleanupTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = base_path('storage/framework/testing/reinclude-'.uniqid('', true));
        mkdir($this->root, 0777, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->root.'/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->root);

        parent::tearDown();
    }

    private function gitignoreWithRules(): string
    {
        return implode("\n", [
            '!public/assets/images/',
            '!custom/README.md',
            'plugins/*',
            '!plugins/AcmeBlog/',
            '!plugins/AcmeShop/',
            'themes/*',
            '!themes/AcmeTheme/',
            '',
        ]);
    }

    private function excludeWithSections(): string
    {
        return implode("\n", [
            '# Git exclude rules',
            '',
            '# === Plugin exclusions (auto-managed) ===',
            '# Do not edit this section manually',
            '!plugins/AcmeBlog',
            '# === End plugins exclusions ===',
            '',
            '# === Theme exclusions (auto-managed) ===',
            '# Do not edit this section manually',
            '!themes/AcmeTheme',
            'my-local-rule',
            '# === End themes exclusions ===',
            '',
        ]);
    }

    /**
     * @param  class-string<SyncGitIgnore|SyncGitExclude>  $class
     * @param  array<string, mixed>  $options
     * @return array{0: int, 1: string}
     */
    private function runCleanup(string $class, string $path, array $options = []): array
    {
        $command = $class === SyncGitIgnore::class
            ? new class extends SyncGitIgnore
            {
                public string $path = '';

                protected function rulesFilePath(): string
                {
                    return $this->path;
                }
            }
        : new class extends SyncGitExclude
        {
            public string $path = '';

            protected function rulesFilePath(): string
            {
                return $this->path;
            }
        };

        $command->path = $path;
        $command->setLaravel($this->app);

        $output = new BufferedOutput();
        $exitCode = $command->run(new ArrayInput($options), $output);

        return [$exitCode, $output->fetch()];
    }

    public function test_strip_removes_only_extension_rules_from_a_gitignore(): void
    {
        $result = ExtensionReincludeRules::strip($this->gitignoreWithRules());

        $this->assertSame(['!plugins/AcmeBlog/', '!plugins/AcmeShop/', '!themes/AcmeTheme/'], $result['removed']);
        $this->assertSame(
            implode("\n", ['!public/assets/images/', '!custom/README.md', 'plugins/*', 'themes/*', '']),
            $result['content']
        );
    }

    public function test_strip_can_limit_itself_to_one_type_or_one_directory(): void
    {
        $plugins = ExtensionReincludeRules::strip($this->gitignoreWithRules(), ['plugins']);
        $this->assertSame(['!plugins/AcmeBlog/', '!plugins/AcmeShop/'], $plugins['removed']);
        $this->assertStringContainsString('!themes/AcmeTheme/', $plugins['content']);

        $one = ExtensionReincludeRules::strip($this->gitignoreWithRules(), ['plugins'], 'AcmeShop');
        $this->assertSame(['!plugins/AcmeShop/'], $one['removed']);
        $this->assertStringContainsString('!plugins/AcmeBlog/', $one['content']);
    }

    public function test_strip_drops_an_auto_managed_section_only_when_nothing_else_is_in_it(): void
    {
        $result = ExtensionReincludeRules::strip($this->excludeWithSections());

        $this->assertSame(
            implode("\n", [
                '# Git exclude rules',
                '',
                '# === Theme exclusions (auto-managed) ===',
                '# Do not edit this section manually',
                'my-local-rule',
                '# === End themes exclusions ===',
                '',
            ]),
            $result['content']
        );
        $this->assertContains('!plugins/AcmeBlog', $result['removed']);
        $this->assertContains('!themes/AcmeTheme', $result['removed']);
    }

    public function test_strip_leaves_dot_files_and_other_negations_alone(): void
    {
        $content = "plugins/*\n!plugins/.gitkeep\n!plugins/AcmeBlog/**\n";

        $this->assertSame([], ExtensionReincludeRules::strip($content)['removed']);
    }

    public function test_the_gitignore_cleanup_previews_by_default_and_writes_with_confirm(): void
    {
        $path = $this->root.'/gitignore';
        file_put_contents($path, $this->gitignoreWithRules());

        [$exitCode, $output] = $this->runCleanup(SyncGitIgnore::class, $path);
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('!plugins/AcmeBlog/', $output);
        $this->assertSame($this->gitignoreWithRules(), file_get_contents($path), 'A run without --confirm must not write.');

        [$exitCode] = $this->runCleanup(SyncGitIgnore::class, $path, ['--confirm' => true, '--dry-run' => true]);
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertSame($this->gitignoreWithRules(), file_get_contents($path), '--dry-run wins over --confirm.');

        [$exitCode] = $this->runCleanup(SyncGitIgnore::class, $path, ['--confirm' => true]);
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringNotContainsString('!plugins/', (string) file_get_contents($path));
        $this->assertStringNotContainsString('!themes/', (string) file_get_contents($path));
        $this->assertStringContainsString('!custom/README.md', (string) file_get_contents($path));
    }

    public function test_force_is_still_accepted_as_confirm(): void
    {
        $path = $this->root.'/gitignore';
        file_put_contents($path, $this->gitignoreWithRules());

        [$exitCode] = $this->runCleanup(SyncGitIgnore::class, $path, ['--remove-plugin' => 'AcmeShop', '--force' => true]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringNotContainsString('!plugins/AcmeShop/', (string) file_get_contents($path));
        $this->assertStringContainsString('!plugins/AcmeBlog/', (string) file_get_contents($path));
    }

    public function test_the_git_exclude_cleanup_removes_the_auto_managed_section(): void
    {
        $path = $this->root.'/exclude';
        file_put_contents($path, $this->excludeWithSections());

        [$exitCode] = $this->runCleanup(SyncGitExclude::class, $path, ['--confirm' => true]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $content = (string) file_get_contents($path);
        $this->assertStringNotContainsString('Plugin exclusions', $content);
        $this->assertStringNotContainsString('!plugins/', $content);
        $this->assertStringNotContainsString('!themes/', $content);
        $this->assertStringContainsString('my-local-rule', $content);
    }

    public function test_a_missing_file_is_nothing_to_clean_up(): void
    {
        [$exitCode] = $this->runCleanup(SyncGitExclude::class, $this->root.'/missing', ['--confirm' => true]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertFileDoesNotExist($this->root.'/missing');
    }

    public function test_the_add_options_no_longer_write_anything(): void
    {
        $path = $this->root.'/gitignore';
        file_put_contents($path, "plugins/*\nthemes/*\n");

        [$exitCode] = $this->runCleanup(SyncGitIgnore::class, $path, ['--add-plugin' => 'AcmeBlog', '--confirm' => true]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertSame("plugins/*\nthemes/*\n", file_get_contents($path));
    }

    public function test_the_deprecated_add_helpers_are_no_ops_that_report_success(): void
    {
        $before = (string) file_get_contents(base_path('.gitignore'));

        $this->assertTrue(GitIgnoreHelper::addPluginExclusion('AcmeBlog'));
        $this->assertTrue(GitIgnoreHelper::addThemeExclusion('AcmeTheme'));
        $this->assertTrue(GitExcludeHelper::addPluginExclusion('AcmeBlog'));
        $this->assertTrue(GitExcludeHelper::addThemeExclusion('AcmeTheme'));

        $this->assertSame($before, file_get_contents(base_path('.gitignore')));
    }

    public function test_the_tracked_gitignore_carries_no_extension_rules(): void
    {
        $result = ExtensionReincludeRules::strip((string) file_get_contents(base_path('.gitignore')));

        $this->assertSame([], $result['removed']);
    }

    public function test_no_install_create_or_delete_path_calls_the_helpers(): void
    {
        foreach ([
            'Http/Controllers/Admin/Settings/AdminPluginsSettingsController.php',
            'Http/Controllers/Admin/Settings/AdminThemesSettingsController.php',
            'Services/Install/InstallRunner.php',
            'Console/Commands/PluginInstall.php',
            'Console/Commands/PluginDelete.php',
            'Console/Commands/ThemeDelete.php',
        ] as $relative) {
            $source = (string) file_get_contents(app_path($relative));

            $this->assertStringNotContainsString('GitIgnoreHelper::', $source, $relative);
            $this->assertStringNotContainsString('GitExcludeHelper::', $source, $relative);
        }
    }
}
