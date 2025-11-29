<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Deploy\DatabaseSyncService;
use App\Services\Deploy\DeployConfigService;
use App\Services\Deploy\DeployService;
use Illuminate\Console\Command;

/**
 * Push local changes to remote environment
 */
class DeployPush extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deploy:push
                            {environment : Target environment (staging, production, etc.)}
                            {--core : Sync core files}
                            {--plugins : Sync plugins directory}
                            {--themes : Sync themes directory}
                            {--custom : Sync custom directory}
                            {--uploads : Sync uploads (storage/app/public)}
                            {--database : Sync database}
                            {--all : Sync everything including database}
                            {--tables=* : Specific database tables to sync}
                            {--dry-run : Show what would be synced without actually syncing}
                            {--force : Skip confirmation prompts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Push local Dixlase data to a remote environment';

    /**
     * Execute the console command.
     */
    public function handle(
        DeployConfigService $configService,
        DeployService $deployService,
        DatabaseSyncService $dbService
    ): int {
        $environment = $this->argument('environment');
        $dryRun = $this->option('dry-run');

        // Validate configuration
        if (!$configService->configExists()) {
            $this->error('Configuration file not found.');
            $this->line('Run: php artisan deploy:init');
            return Command::FAILURE;
        }

        // Check environment exists
        try {
            $configService->getEnvironment($environment);
        } catch (\Exception $e) {
            $this->error("Environment '{$environment}' not found in configuration.");
            $this->line('Available environments: ' . implode(', ', $configService->getAvailableEnvironments()));
            return Command::FAILURE;
        }

        // Determine targets
        $targets = $this->determineTargets();
        $tables = $this->option('tables');

        if (empty($targets)) {
            $this->error('No sync targets specified.');
            $this->line('Use --plugins, --themes, --custom, --uploads, --database, or --all');
            return Command::FAILURE;
        }

        // Show what will be synced
        $this->info("Push to {$environment}");
        $this->line('Targets: ' . implode(', ', $targets));
        
        if (!empty($tables)) {
            $this->line('Tables: ' . implode(', ', $tables));
        }
        
        if ($dryRun) {
            $this->warn('[DRY RUN MODE]');
        }

        $this->newLine();

        // Confirmation
        if (!$dryRun && !$this->option('force')) {
            $vhost = $configService->getVhost($environment);
            
            if (in_array(DeployConfigService::TARGET_DATABASE, $targets)) {
                $this->warn("WARNING: This will overwrite the database on {$vhost}");
            }
            
            if (!$this->confirm("Are you sure you want to push to {$environment}?")) {
                $this->info('Operation cancelled.');
                return Command::SUCCESS;
            }
        }

        // Execute push
        $deployService->setCommand($this);
        
        $success = $deployService->push(
            $environment,
            $targets,
            $tables,
            $dryRun
        );

        return $success ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * Determine which targets to sync based on options
     */
    protected function determineTargets(): array
    {
        $targets = [];

        if ($this->option('all')) {
            return [
                DeployConfigService::TARGET_CORE,
                DeployConfigService::TARGET_PLUGINS,
                DeployConfigService::TARGET_THEMES,
                DeployConfigService::TARGET_CUSTOM,
                DeployConfigService::TARGET_UPLOADS,
                DeployConfigService::TARGET_DATABASE,
            ];
        }

        if ($this->option('core')) {
            $targets[] = DeployConfigService::TARGET_CORE;
        }
        if ($this->option('plugins')) {
            $targets[] = DeployConfigService::TARGET_PLUGINS;
        }
        if ($this->option('themes')) {
            $targets[] = DeployConfigService::TARGET_THEMES;
        }
        if ($this->option('custom')) {
            $targets[] = DeployConfigService::TARGET_CUSTOM;
        }
        if ($this->option('uploads')) {
            $targets[] = DeployConfigService::TARGET_UPLOADS;
        }
        if ($this->option('database')) {
            $targets[] = DeployConfigService::TARGET_DATABASE;
        }

        // Default targets if none specified
        if (empty($targets)) {
            $targets = [
                DeployConfigService::TARGET_PLUGINS,
                DeployConfigService::TARGET_THEMES,
                DeployConfigService::TARGET_CUSTOM,
                DeployConfigService::TARGET_UPLOADS,
            ];
        }

        return $targets;
    }
}
