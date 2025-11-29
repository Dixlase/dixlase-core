<?php

declare(strict_types=1);

namespace App\Services\Deploy;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Main deployment service orchestrating file and database sync
 */
class DeployService
{
    protected DeployConfigService $configService;
    protected DatabaseSyncService $dbService;
    protected ?ConnectionInterface $connection = null;
    protected ?Command $command = null;

    public function __construct(
        DeployConfigService $configService,
        DatabaseSyncService $dbService
    ) {
        $this->configService = $configService;
        $this->dbService = $dbService;
    }

    /**
     * Set command for output
     */
    public function setCommand(Command $command): void
    {
        $this->command = $command;
    }

    /**
     * Get or create connection for environment
     */
    public function getConnection(string $environment): ConnectionInterface
    {
        if ($this->connection !== null) {
            return $this->connection;
        }

        $type = $this->configService->getConnectionType($environment);
        $config = $this->configService->getConnectionConfig($environment);

        $this->connection = match ($type) {
            DeployConfigService::CONNECTION_SSH => new SshConnection($config),
            DeployConfigService::CONNECTION_FTP,
            DeployConfigService::CONNECTION_SFTP => new FtpConnection($config),
            default => throw new \InvalidArgumentException("Unsupported connection type: {$type}"),
        };

        return $this->connection;
    }

    /**
     * Push to remote environment
     */
    public function push(
        string $environment,
        array $targets = [],
        array $dbTables = [],
        bool $dryRun = false
    ): bool {
        $this->info("Starting push to {$environment}...");

        // Get connection
        $connection = $this->getConnection($environment);
        
        $this->info("Connecting via " . $connection->getConnectionInfo());
        
        if (!$connection->connect()) {
            $this->error("Failed to connect to {$environment}");
            return false;
        }

        $this->info("Connected successfully");

        // Execute before hooks
        if (!$this->executeHooks($environment, 'push', 'before')) {
            return false;
        }

        $success = true;
        $excludes = $this->configService->getExcludePatterns($environment);

        // Sync files
        if (empty($targets) || $this->hasFileTargets($targets)) {
            $success = $this->syncFiles($environment, $targets, $excludes, 'push', $dryRun) && $success;
        }

        // Sync database
        if (in_array(DeployConfigService::TARGET_DATABASE, $targets) || 
            (empty($targets) && $this->shouldSyncDatabase($targets))) {
            if (!$dryRun) {
                $this->dbService->setConnection($connection);
                $success = $this->dbService->push($environment, $dbTables) && $success;
            } else {
                $this->info("[DRY RUN] Would sync database");
            }
        }

        // Execute after hooks
        if ($success && !$dryRun) {
            $this->executeHooks($environment, 'push', 'after');
        }

        $connection->disconnect();

        if ($success) {
            $this->info("Push to {$environment} completed successfully");
        } else {
            $this->error("Push to {$environment} completed with errors");
        }

        return $success;
    }

    /**
     * Pull from remote environment
     */
    public function pull(
        string $environment,
        array $targets = [],
        array $dbTables = [],
        bool $dryRun = false
    ): bool {
        $this->info("Starting pull from {$environment}...");

        // Get connection
        $connection = $this->getConnection($environment);
        
        $this->info("Connecting via " . $connection->getConnectionInfo());
        
        if (!$connection->connect()) {
            $this->error("Failed to connect to {$environment}");
            return false;
        }

        $this->info("Connected successfully");

        // Execute before hooks
        if (!$this->executeHooks($environment, 'pull', 'before')) {
            return false;
        }

        $success = true;
        $excludes = $this->configService->getExcludePatterns($environment);

        // Sync files
        if (empty($targets) || $this->hasFileTargets($targets)) {
            $success = $this->syncFiles($environment, $targets, $excludes, 'pull', $dryRun) && $success;
        }

        // Sync database
        if (in_array(DeployConfigService::TARGET_DATABASE, $targets) || 
            (empty($targets) && $this->shouldSyncDatabase($targets))) {
            if (!$dryRun) {
                $this->dbService->setConnection($connection);
                $success = $this->dbService->pull($environment, $dbTables) && $success;
            } else {
                $this->info("[DRY RUN] Would sync database");
            }
        }

        // Execute after hooks
        if ($success && !$dryRun) {
            $this->executeHooks($environment, 'pull', 'after');
        }

        $connection->disconnect();

        if ($success) {
            $this->info("Pull from {$environment} completed successfully");
        } else {
            $this->error("Pull from {$environment} completed with errors");
        }

        return $success;
    }

    /**
     * Sync files between environments
     */
    protected function syncFiles(
        string $environment,
        array $targets,
        array $excludes,
        string $direction,
        bool $dryRun
    ): bool {
        $localPath = $this->configService->getDixlasePath('local');
        $remotePath = $this->configService->getDixlasePath($environment);
        $paths = $this->configService->getPaths($environment);
        
        $connection = $this->getConnection($environment);
        $success = true;

        // Determine what to sync
        $syncTargets = $this->determineSyncTargets($targets, $paths);

        foreach ($syncTargets as $target => $relativePath) {
            $localDir = $localPath . '/' . $relativePath;
            $remoteDir = $remotePath . '/' . $relativePath;

            $this->info("Syncing {$target}: {$relativePath}");

            if ($dryRun) {
                $this->info("[DRY RUN] Would sync {$localDir} <-> {$remoteDir}");
                continue;
            }

            if ($direction === 'push') {
                $result = $connection->syncToRemote($localDir, $remoteDir, $excludes);
            } else {
                $result = $connection->syncFromRemote($remoteDir, $localDir, $excludes);
            }

            if (!$result) {
                $this->error("Failed to sync {$target}");
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Determine what targets to sync
     */
    protected function determineSyncTargets(array $targets, array $paths): array
    {
        $allTargets = [
            DeployConfigService::TARGET_CORE => '',
            DeployConfigService::TARGET_PLUGINS => $paths['plugins'],
            DeployConfigService::TARGET_THEMES => $paths['themes'],
            DeployConfigService::TARGET_CUSTOM => $paths['custom'],
            DeployConfigService::TARGET_UPLOADS => $paths['uploads'],
        ];

        if (empty($targets)) {
            // Default: sync plugins, themes, custom, uploads (not core)
            unset($allTargets[DeployConfigService::TARGET_CORE]);
            return $allTargets;
        }

        // Filter to requested targets
        $result = [];
        foreach ($targets as $target) {
            if (isset($allTargets[$target])) {
                $result[$target] = $allTargets[$target];
            }
        }

        return $result;
    }

    /**
     * Check if targets include file targets
     */
    protected function hasFileTargets(array $targets): bool
    {
        $fileTargets = [
            DeployConfigService::TARGET_CORE,
            DeployConfigService::TARGET_PLUGINS,
            DeployConfigService::TARGET_THEMES,
            DeployConfigService::TARGET_CUSTOM,
            DeployConfigService::TARGET_UPLOADS,
        ];

        return !empty(array_intersect($targets, $fileTargets));
    }

    /**
     * Check if database should be synced
     */
    protected function shouldSyncDatabase(array $targets): bool
    {
        // If no targets specified, don't sync database by default
        // User must explicitly request database sync
        return false;
    }

    /**
     * Execute hooks
     */
    protected function executeHooks(string $environment, string $action, string $timing): bool
    {
        $hooks = $this->configService->getHooks($environment, $action);
        $hookList = $hooks[$timing] ?? [];

        if (empty($hookList)) {
            return true;
        }

        $this->info("Executing {$timing} hooks...");

        foreach ($hookList as $hook) {
            $command = $hook['command'] ?? '';
            $where = $hook['where'] ?? 'local';
            $raise = $hook['raise'] ?? true;

            if (empty($command)) {
                continue;
            }

            $this->info("  Running: {$command} ({$where})");

            if ($where === 'remote') {
                $result = $this->getConnection($environment)->execute($command);
                $success = $result['success'];
                $output = $result['output'];
            } else {
                $output = [];
                $returnCode = 0;
                exec($command . ' 2>&1', $output, $returnCode);
                $success = $returnCode === 0;
                $output = implode("\n", $output);
            }

            if (!$success) {
                $this->error("  Hook failed: {$output}");
                if ($raise) {
                    return false;
                }
            } else {
                if (!empty($output)) {
                    $this->info("  Output: {$output}");
                }
            }
        }

        return true;
    }

    /**
     * Output info message
     */
    protected function info(string $message): void
    {
        if ($this->command) {
            $this->command->info($message);
        } else {
            Log::info("[Deploy] {$message}");
        }
    }

    /**
     * Output error message
     */
    protected function error(string $message): void
    {
        if ($this->command) {
            $this->command->error($message);
        } else {
            Log::error("[Deploy] {$message}");
        }
    }
}
