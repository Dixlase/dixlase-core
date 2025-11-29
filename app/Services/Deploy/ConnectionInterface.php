<?php

declare(strict_types=1);

namespace App\Services\Deploy;

/**
 * Interface for remote connection handlers
 */
interface ConnectionInterface
{
    /**
     * Connect to remote server
     */
    public function connect(): bool;

    /**
     * Disconnect from remote server
     */
    public function disconnect(): void;

    /**
     * Check if connected
     */
    public function isConnected(): bool;

    /**
     * Execute command on remote server
     */
    public function execute(string $command): array;

    /**
     * Upload file to remote server
     */
    public function upload(string $localPath, string $remotePath): bool;

    /**
     * Download file from remote server
     */
    public function download(string $remotePath, string $localPath): bool;

    /**
     * Sync directory to remote (push)
     */
    public function syncToRemote(string $localPath, string $remotePath, array $excludes = []): bool;

    /**
     * Sync directory from remote (pull)
     */
    public function syncFromRemote(string $remotePath, string $localPath, array $excludes = []): bool;

    /**
     * Check if remote path exists
     */
    public function exists(string $remotePath): bool;

    /**
     * Create directory on remote
     */
    public function mkdir(string $remotePath, bool $recursive = true): bool;

    /**
     * Delete file or directory on remote
     */
    public function delete(string $remotePath, bool $recursive = false): bool;

    /**
     * Get connection info for display
     */
    public function getConnectionInfo(): string;
}
