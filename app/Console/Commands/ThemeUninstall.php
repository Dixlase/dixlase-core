<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Theme;
use Illuminate\Support\Facades\File;

class ThemeUninstall extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'theme:uninstall 
                            {themeName : ' . 'command.theme_uninstall.theme_name_prompt' . '} 
                            {--force : ' . 'command.theme_uninstall.force_option' . '} 
                            {--delete : ' . 'command.theme_uninstall.delete_option' . '}';

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
        $force = $this->option('force');
        
        // Find the theme
        $theme = Theme::where('name', $themeName)
                     ->orWhere('slug', $themeName)
                     ->first();

        if (!$theme) {
            $this->error(__('command.theme_uninstall.theme_not_found', ['themeName' => $themeName]));
            return Command::FAILURE;
        }

        // Check if theme is active
        if ($theme->is_active && !$force) {
            $this->error(__('command.theme_uninstall.cannot_uninstall_active', ['themeName' => $theme->name]));
            return Command::FAILURE;
        }

        // Ask for confirmation
        if (!$this->confirm(__('command.theme_uninstall.confirmation', ['themeName' => $theme->name]))) {
            $this->info(__('command.theme_uninstall.cancelled'));
            return Command::SUCCESS;
        }

        // Deactivate the theme if it's active and force is used
        if ($theme->is_active) {
            $theme->update(['is_active' => false]);
            $this->info(__('command.theme_uninstall.deactivated', ['themeName' => $theme->name]));
        }

        // Get theme directory path
        $themeDir = base_path('themes/' . $theme->directory);
        $deleteFiles = false;
        
        // Check if we should delete theme files
        if ($this->option('delete')) {
            $deleteFiles = true;
        } elseif (is_dir($themeDir) && $this->confirm(__('command.theme_uninstall.confirm_delete', ['themeDir' => $themeDir]))) {
            $deleteFiles = true;
        }
        
        // Delete theme files if requested and directory exists
        if ($deleteFiles && is_dir($themeDir)) {
            try {
                // Delete the theme directory recursively
                File::deleteDirectory($themeDir);
                
                // Check if directory was deleted
                if (!is_dir($themeDir)) {
                    $this->info(__('command.theme_uninstall.deleted_directory', ['themeDir' => $themeDir]));
                } else {
                    $this->warn(__('command.theme_uninstall.delete_failed', ['themeDir' => $themeDir]));
                }
            } catch (\Exception $e) {
                $this->error(__('command.theme_uninstall.delete_error', ['error' => $e->getMessage()]));
                $this->warn(__('command.theme_uninstall.files_not_deleted'));
            }
        } elseif ($deleteFiles && !is_dir($themeDir)) {
            $this->warn(__('command.theme_uninstall.directory_not_found', ['themeDir' => $themeDir]));
        }
        
        // Delete the theme from database
        $theme->delete();
        $this->info(__('command.theme_uninstall.uninstalled', ['themeName' => $theme->name]));
        
        if (!$deleteFiles) {
            $this->info(__('command.theme_uninstall.files_not_removed', ['themeDir' => $themeDir]));
        }
        
        return Command::SUCCESS;
    }
}
