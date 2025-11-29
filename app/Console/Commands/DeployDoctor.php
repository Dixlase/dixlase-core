<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Deploy\DeployConfigService;
use Illuminate\Console\Command;

/**
 * Validate deployment configuration and environment
 */
class DeployDoctor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deploy:doctor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check deployment configuration and system requirements';

    /**
     * Execute the console command.
     */
    public function handle(DeployConfigService $configService): int
    {
        $this->info('Dixlase Deploy Doctor');
        $this->line('=====================');
        $this->newLine();

        $hasErrors = false;

        // Check configuration file
        $this->info('Checking configuration...');
        
        if (!$configService->configExists()) {
            $this->error('  ✗ Configuration file not found');
            $this->line('    Run: php artisan deploy:init');
            return Command::FAILURE;
        }
        
        $this->line('  ✓ Configuration file exists');

        // Validate configuration
        $errors = $configService->validate();
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->error("  ✗ {$error}");
            }
            $hasErrors = true;
        } else {
            $this->line('  ✓ Configuration is valid');
        }

        $this->newLine();

        // Check system requirements
        $this->info('Checking system requirements...');

        // Check rsync
        $rsyncVersion = $this->checkCommand('rsync --version');
        if ($rsyncVersion) {
            $this->line("  ✓ rsync is installed");
        } else {
            $this->error('  ✗ rsync is not installed (required for SSH sync)');
            $hasErrors = true;
        }

        // Check lftp
        $lftpVersion = $this->checkCommand('lftp --version');
        if ($lftpVersion) {
            $this->line("  ✓ lftp is installed");
        } else {
            $this->warn('  ! lftp is not installed (required for FTP sync)');
        }

        // Check ssh
        $sshVersion = $this->checkCommand('ssh -V');
        if ($sshVersion) {
            $this->line("  ✓ ssh is installed");
        } else {
            $this->error('  ✗ ssh is not installed');
            $hasErrors = true;
        }

        // Check mysql/mysqldump
        $mysqlVersion = $this->checkCommand('mysql --version');
        if ($mysqlVersion) {
            $this->line("  ✓ mysql client is installed");
        } else {
            $this->error('  ✗ mysql client is not installed (required for database sync)');
            $hasErrors = true;
        }

        $mysqldumpVersion = $this->checkCommand('mysqldump --version');
        if ($mysqldumpVersion) {
            $this->line("  ✓ mysqldump is installed");
        } else {
            $this->error('  ✗ mysqldump is not installed (required for database sync)');
            $hasErrors = true;
        }

        // Check gzip
        $gzipVersion = $this->checkCommand('gzip --version');
        if ($gzipVersion) {
            $this->line("  ✓ gzip is installed");
        } else {
            $this->error('  ✗ gzip is not installed');
            $hasErrors = true;
        }

        $this->newLine();

        // Check environments
        if ($configService->configExists()) {
            try {
                $environments = $configService->getAvailableEnvironments();
                
                if (!empty($environments)) {
                    $this->info('Available environments:');
                    foreach ($environments as $env) {
                        $type = $configService->getConnectionType($env);
                        $vhost = $configService->getVhost($env);
                        $this->line("  • {$env} ({$type}): {$vhost}");
                    }
                }
            } catch (\Exception $e) {
                $this->error("  ✗ Failed to load environments: {$e->getMessage()}");
                $hasErrors = true;
            }
        }

        $this->newLine();

        if ($hasErrors) {
            $this->error('Some checks failed. Please fix the issues above.');
            return Command::FAILURE;
        }

        $this->info('All checks passed! Ready to deploy.');
        return Command::SUCCESS;
    }

    /**
     * Check if a command is available
     */
    protected function checkCommand(string $command): bool
    {
        $output = [];
        $returnCode = 0;
        exec($command . ' 2>&1', $output, $returnCode);
        return $returnCode === 0;
    }
}
