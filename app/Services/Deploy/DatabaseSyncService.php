<?php

declare(strict_types=1);

namespace App\Services\Deploy;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * Service for database synchronization between environments
 */
class DatabaseSyncService
{
    protected DeployConfigService $configService;
    protected ?ConnectionInterface $connection = null;
    protected string $tempDir;

    public function __construct(DeployConfigService $configService)
    {
        $this->configService = $configService;
        $this->tempDir = storage_path('app/deploy');
        
        if (!File::exists($this->tempDir)) {
            File::makeDirectory($this->tempDir, 0755, true);
        }
    }

    /**
     * Set connection for remote operations
     */
    public function setConnection(ConnectionInterface $connection): void
    {
        $this->connection = $connection;
    }

    /**
     * Push database from local to remote
     */
    public function push(string $environment, array $tables = []): bool
    {
        $localConfig = $this->configService->getLocal();
        $remoteConfig = $this->configService->getEnvironment($environment);
        
        $localDb = $localConfig['database'];
        $remoteDb = $remoteConfig['database'];
        
        // Dump local database
        $dumpFile = $this->dumpLocalDatabase($localDb, $tables);
        if (!$dumpFile) {
            return false;
        }
        
        // Process SQL for URL/path replacement
        $processedFile = $this->processSqlForPush(
            $dumpFile,
            $localConfig['vhost'],
            $remoteConfig['vhost'],
            $localConfig['dixlase_path'],
            $remoteConfig['dixlase_path']
        );
        
        // Upload to remote
        $remoteTempFile = '/tmp/dixlase_db_' . time() . '.sql.gz';
        if (!$this->connection->upload($processedFile, $remoteTempFile)) {
            Log::error("Failed to upload database dump");
            $this->cleanup([$dumpFile, $processedFile]);
            return false;
        }
        
        // Import on remote
        $success = $this->importRemoteDatabase($remoteDb, $remoteTempFile);
        
        // Cleanup
        $this->connection->delete($remoteTempFile);
        $this->cleanup([$dumpFile, $processedFile]);
        
        return $success;
    }

    /**
     * Pull database from remote to local
     */
    public function pull(string $environment, array $tables = []): bool
    {
        $localConfig = $this->configService->getLocal();
        $remoteConfig = $this->configService->getEnvironment($environment);
        
        $localDb = $localConfig['database'];
        $remoteDb = $remoteConfig['database'];
        
        // Dump remote database
        $remoteTempFile = '/tmp/dixlase_db_' . time() . '.sql.gz';
        if (!$this->dumpRemoteDatabase($remoteDb, $remoteTempFile, $tables)) {
            return false;
        }
        
        // Download dump
        $localTempFile = $this->tempDir . '/remote_dump_' . time() . '.sql.gz';
        if (!$this->connection->download($remoteTempFile, $localTempFile)) {
            Log::error("Failed to download database dump");
            $this->connection->delete($remoteTempFile);
            return false;
        }
        
        // Cleanup remote temp file
        $this->connection->delete($remoteTempFile);
        
        // Process SQL for URL/path replacement
        $processedFile = $this->processSqlForPull(
            $localTempFile,
            $remoteConfig['vhost'],
            $localConfig['vhost'],
            $remoteConfig['dixlase_path'],
            $localConfig['dixlase_path']
        );
        
        // Import locally
        $success = $this->importLocalDatabase($localDb, $processedFile);
        
        // Cleanup
        $this->cleanup([$localTempFile, $processedFile]);
        
        return $success;
    }

    /**
     * Dump local database
     */
    protected function dumpLocalDatabase(array $dbConfig, array $tables = []): ?string
    {
        $dumpFile = $this->tempDir . '/local_dump_' . time() . '.sql';
        $gzipFile = $dumpFile . '.gz';
        
        $command = $this->buildMysqldumpCommand($dbConfig, $tables);
        $command .= ' | gzip > ' . escapeshellarg($gzipFile);
        
        Log::debug("Dumping local database: {$command}");
        
        $output = [];
        $returnCode = 0;
        exec($command . ' 2>&1', $output, $returnCode);
        
        if ($returnCode !== 0) {
            Log::error("Failed to dump local database: " . implode("\n", $output));
            return null;
        }
        
        return $gzipFile;
    }

    /**
     * Dump remote database
     */
    protected function dumpRemoteDatabase(array $dbConfig, string $remotePath, array $tables = []): bool
    {
        $command = $this->buildMysqldumpCommand($dbConfig, $tables);
        $command .= ' | gzip > ' . escapeshellarg($remotePath);
        
        $result = $this->connection->execute($command);
        
        if (!$result['success']) {
            Log::error("Failed to dump remote database: " . $result['output']);
            return false;
        }
        
        return true;
    }

    /**
     * Build mysqldump command
     */
    protected function buildMysqldumpCommand(array $dbConfig, array $tables = []): string
    {
        $parts = ['mysqldump'];
        
        $parts[] = '-h' . escapeshellarg($dbConfig['host']);
        $parts[] = '-u' . escapeshellarg($dbConfig['user']);
        
        if (!empty($dbConfig['password'])) {
            $parts[] = '-p' . escapeshellarg($dbConfig['password']);
        }
        
        if (!empty($dbConfig['port']) && $dbConfig['port'] !== 3306) {
            $parts[] = '-P' . escapeshellarg((string)$dbConfig['port']);
        }
        
        $parts[] = '--single-transaction';
        $parts[] = '--quick';
        $parts[] = '--lock-tables=false';
        
        $parts[] = escapeshellarg($dbConfig['name']);
        
        // Specific tables
        foreach ($tables as $table) {
            $parts[] = escapeshellarg($table);
        }
        
        return implode(' ', $parts);
    }

    /**
     * Import database locally
     */
    protected function importLocalDatabase(array $dbConfig, string $sqlFile): bool
    {
        $command = $this->buildMysqlCommand($dbConfig);
        
        if (str_ends_with($sqlFile, '.gz')) {
            $command = 'gunzip -c ' . escapeshellarg($sqlFile) . ' | ' . $command;
        } else {
            $command .= ' < ' . escapeshellarg($sqlFile);
        }
        
        Log::debug("Importing local database: {$command}");
        
        $output = [];
        $returnCode = 0;
        exec($command . ' 2>&1', $output, $returnCode);
        
        if ($returnCode !== 0) {
            Log::error("Failed to import local database: " . implode("\n", $output));
            return false;
        }
        
        return true;
    }

    /**
     * Import database on remote
     */
    protected function importRemoteDatabase(array $dbConfig, string $remoteSqlFile): bool
    {
        $command = $this->buildMysqlCommand($dbConfig);
        
        if (str_ends_with($remoteSqlFile, '.gz')) {
            $command = 'gunzip -c ' . escapeshellarg($remoteSqlFile) . ' | ' . $command;
        } else {
            $command .= ' < ' . escapeshellarg($remoteSqlFile);
        }
        
        $result = $this->connection->execute($command);
        
        if (!$result['success']) {
            Log::error("Failed to import remote database: " . $result['output']);
            return false;
        }
        
        return true;
    }

    /**
     * Build mysql command
     */
    protected function buildMysqlCommand(array $dbConfig): string
    {
        $parts = ['mysql'];
        
        $parts[] = '-h' . escapeshellarg($dbConfig['host']);
        $parts[] = '-u' . escapeshellarg($dbConfig['user']);
        
        if (!empty($dbConfig['password'])) {
            $parts[] = '-p' . escapeshellarg($dbConfig['password']);
        }
        
        if (!empty($dbConfig['port']) && $dbConfig['port'] !== 3306) {
            $parts[] = '-P' . escapeshellarg((string)$dbConfig['port']);
        }
        
        $parts[] = escapeshellarg($dbConfig['name']);
        
        return implode(' ', $parts);
    }

    /**
     * Process SQL for push (replace local URLs/paths with remote)
     */
    protected function processSqlForPush(
        string $sqlFile,
        string $localVhost,
        string $remoteVhost,
        string $localPath,
        string $remotePath
    ): string {
        return $this->processSql($sqlFile, [
            $localVhost => $remoteVhost,
            $localPath => $remotePath,
        ]);
    }

    /**
     * Process SQL for pull (replace remote URLs/paths with local)
     */
    protected function processSqlForPull(
        string $sqlFile,
        string $remoteVhost,
        string $localVhost,
        string $remotePath,
        string $localPath
    ): string {
        return $this->processSql($sqlFile, [
            $remoteVhost => $localVhost,
            $remotePath => $localPath,
        ]);
    }

    /**
     * Process SQL file with replacements
     */
    protected function processSql(string $sqlFile, array $replacements): string
    {
        $outputFile = $this->tempDir . '/processed_' . time() . '.sql.gz';
        
        // Build sed command for replacements
        $sedParts = [];
        foreach ($replacements as $search => $replace) {
            // Escape special characters for sed
            $search = str_replace(['/', '&'], ['\/', '\&'], $search);
            $replace = str_replace(['/', '&'], ['\/', '\&'], $replace);
            $sedParts[] = "s/{$search}/{$replace}/g";
        }
        
        $sedCommand = implode('; ', $sedParts);
        
        if (str_ends_with($sqlFile, '.gz')) {
            $command = sprintf(
                'gunzip -c %s | sed "%s" | gzip > %s',
                escapeshellarg($sqlFile),
                $sedCommand,
                escapeshellarg($outputFile)
            );
        } else {
            $command = sprintf(
                'sed "%s" %s | gzip > %s',
                $sedCommand,
                escapeshellarg($sqlFile),
                escapeshellarg($outputFile)
            );
        }
        
        Log::debug("Processing SQL: {$command}");
        
        $output = [];
        $returnCode = 0;
        exec($command . ' 2>&1', $output, $returnCode);
        
        if ($returnCode !== 0) {
            Log::warning("SQL processing may have issues: " . implode("\n", $output));
        }
        
        return $outputFile;
    }

    /**
     * Get list of tables in local database
     */
    public function getLocalTables(): array
    {
        $localDb = $this->configService->getLocal()['database'];
        
        $command = sprintf(
            'mysql -h%s -u%s %s %s -N -e "SHOW TABLES"',
            escapeshellarg($localDb['host']),
            escapeshellarg($localDb['user']),
            !empty($localDb['password']) ? '-p' . escapeshellarg($localDb['password']) : '',
            escapeshellarg($localDb['name'])
        );
        
        $output = [];
        $returnCode = 0;
        exec($command . ' 2>&1', $output, $returnCode);
        
        if ($returnCode !== 0) {
            return [];
        }
        
        return array_filter($output);
    }

    /**
     * Cleanup temporary files
     */
    protected function cleanup(array $files): void
    {
        foreach ($files as $file) {
            if (File::exists($file)) {
                File::delete($file);
            }
        }
    }
}
