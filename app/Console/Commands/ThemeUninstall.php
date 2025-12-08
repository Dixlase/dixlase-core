<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Theme;
use Illuminate\Support\Facades\File;
use App\Services\ThemeMigrator;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Filesystem\Filesystem;

class ThemeUninstall extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dls:theme:uninstall 
                            {themeName : ' . 'command.theme_uninstall.theme_name_prompt' . '}
                            {--rollback : Rollback database migrations}
                            {--force : Force uninstall even if theme is enabled}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'command.theme_uninstall.description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $themeName = $this->argument('themeName');
        
        // Find the theme
        $theme = Theme::where('name', $themeName)
                     ->orWhere('slug', $themeName)
                     ->first();

        if (!$theme) {
            $this->error(__('command.theme_uninstall.theme_not_found', ['themeName' => $themeName]));
            return Command::FAILURE;
        }

        // Check if theme is enabled (unless --force is specified)
        if ($theme->isEnabled() && !$this->option('force')) {
            $this->error(__('command.theme_uninstall.cannot_uninstall_enabled', ['themeName' => $theme->name]));
            $this->warn(__('command.theme_uninstall.disable_first'));
            return Command::FAILURE;
        }

        // Ask for confirmation (unless --no-interaction is specified)
        if (!$this->option('no-interaction')) {
            if (!$this->confirm(__('command.theme_uninstall.confirmation', ['themeName' => $theme->name]))) {
                $this->info(__('command.theme_uninstall.cancelled'));
                return Command::SUCCESS;
            }
        }

        // マイグレーションのロールバック
        if ($this->option('rollback')) {
            $this->info('Rolling back theme migrations...');
            try {
                $migrator = new ThemeMigrator(
                    app(Filesystem::class),
                    app(ConnectionResolverInterface::class),
                    'theme_migrations',
                    $theme->slug
                );
                // 全てのマイグレーションをロールバックするため、stepを大きな値に設定
                $migrator->rollback($theme->directory, ['step' => 999]);
                $this->info('Theme migrations rolled back successfully');
            } catch (\Exception $e) {
                $this->warn("Failed to rollback migrations: " . $e->getMessage());
            }
        } else if (!$this->option('no-interaction') && $this->confirm('Do you want to rollback database migrations?', false)) {
            $this->info('Rolling back theme migrations...');
            try {
                $migrator = new ThemeMigrator(
                    app(Filesystem::class),
                    app(ConnectionResolverInterface::class),
                    'theme_migrations',
                    $theme->slug
                );
                // 全てのマイグレーションをロールバックするため、stepを大きな値に設定
                $migrator->rollback($theme->directory, ['step' => 999]);
                $this->info('Theme migrations rolled back successfully');
            } catch (\Exception $e) {
                $this->warn("Failed to rollback migrations: " . $e->getMessage());
            }
        } else {
            $this->info('Skipping migration rollback');
        }

        // Note: .git/info/exclude and composer.local.json are updated in ThemeDelete,
        // not here because uninstall does not delete files

        // Delete theme from database
        $theme->delete();
        $this->info(__('command.theme_uninstall.uninstalled', ['themeName' => $theme->name]));
        $this->info(__('command.theme_uninstall.files_preserved'));
        $this->info(__('command.theme_uninstall.delete_hint'));
        
        return Command::SUCCESS;
    }
}
