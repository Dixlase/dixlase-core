<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Deploy\DeployConfigService;
use Illuminate\Console\Command;

/**
 * List available deployment environments
 */
class DeployList extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deploy:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all available deployment environments';

    /**
     * Execute the console command.
     */
    public function handle(DeployConfigService $configService): int
    {
        if (!$configService->configExists()) {
            $this->error('Configuration file not found.');
            $this->line('Run: php artisan deploy:init');
            return Command::FAILURE;
        }

        try {
            $environments = $configService->getAvailableEnvironments();
            
            if (empty($environments)) {
                $this->warn('No environments configured.');
                return Command::SUCCESS;
            }

            $this->info('Available Environments');
            $this->line('======================');
            $this->newLine();

            // Local environment
            $local = $configService->getLocal();
            $this->info('Local:');
            $this->line("  URL:  {$local['vhost']}");
            $this->line("  Path: {$local['dixlase_path']}");
            $this->line("  DB:   {$local['database']['name']}@{$local['database']['host']}");
            $this->newLine();

            // Remote environments
            foreach ($environments as $env) {
                $envConfig = $configService->getEnvironment($env);
                $connectionType = $configService->getConnectionType($env);
                $connectionConfig = $configService->getConnectionConfig($env);
                
                $this->info(ucfirst($env) . ':');
                $this->line("  URL:        {$envConfig['vhost']}");
                $this->line("  Path:       {$envConfig['dixlase_path']}");
                $this->line("  Connection: {$connectionType}");
                
                if ($connectionType === 'ssh') {
                    $port = $connectionConfig['port'] ?? 22;
                    $this->line("  Host:       {$connectionConfig['user']}@{$connectionConfig['host']}:{$port}");
                } else {
                    $port = $connectionConfig['port'] ?? 21;
                    $scheme = $connectionConfig['scheme'] ?? 'ftp';
                    $this->line("  Host:       {$scheme}://{$connectionConfig['user']}@{$connectionConfig['host']}:{$port}");
                }
                
                if (!empty($envConfig['database'])) {
                    $db = $envConfig['database'];
                    $this->line("  DB:         {$db['name']}@{$db['host']}");
                }
                
                $this->newLine();
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to load configuration: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}
