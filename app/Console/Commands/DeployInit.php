<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Deploy\DeployConfigService;
use Illuminate\Console\Command;

/**
 * Initialize deployment configuration
 */
class DeployInit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deploy:init
                            {--force : Overwrite existing configuration}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a new dixlasemove.yml configuration file';

    /**
     * Execute the console command.
     */
    public function handle(DeployConfigService $configService): int
    {
        $configPath = base_path(DeployConfigService::CONFIG_FILE);

        if ($configService->configExists() && !$this->option('force')) {
            $this->error("Configuration file already exists: {$configPath}");
            $this->info("Use --force to overwrite");
            return Command::FAILURE;
        }

        try {
            $path = $configService->generateConfig();
            $this->info("Configuration file generated: {$path}");
            $this->newLine();
            $this->info("Next steps:");
            $this->line("  1. Edit {$path} with your environment settings");
            $this->line("  2. Set up environment variables for sensitive data (use \${VAR_NAME} format)");
            $this->line("  3. Run 'php artisan deploy:doctor' to validate configuration");
            $this->line("  4. Run 'php artisan deploy:list' to see available environments");
            $this->newLine();
            $this->warn("Important: Add 'dixlase-deploy.json' to your .gitignore to protect credentials");
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to generate configuration: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}
