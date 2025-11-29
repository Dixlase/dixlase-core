<?php

declare(strict_types=1);

namespace App\Services\Deploy;

use Illuminate\Support\Facades\Log;

/**
 * FTP/SFTP connection handler using lftp for file sync
 */
class FtpConnection implements ConnectionInterface
{
    protected array $config;
    protected bool $connected = false;
    protected string $scheme;

    public function __construct(array $config)
    {
        $this->config = array_merge([
            'host' => '',
            'user' => '',
            'password' => '',
            'port' => 21,
            'passive' => true,
            'scheme' => 'ftp',
        ], $config);
        
        $this->scheme = $this->config['scheme'] === 'sftp' ? 'sftp' : 'ftp';
        
        if ($this->scheme === 'sftp' && $this->config['port'] === 21) {
            $this->config['port'] = 22;
        }
    }

    /**
     * Connect to remote server (test connection)
     */
    public function connect(): bool
    {
        $result = $this->executeLftp('ls');
        $this->connected = $result['success'];
        return $this->connected;
    }

    /**
     * Disconnect from remote server
     */
    public function disconnect(): void
    {
        $this->connected = false;
    }

    /**
     * Check if connected
     */
    public function isConnected(): bool
    {
        return $this->connected;
    }

    /**
     * Build lftp connection string
     */
    protected function buildLftpUrl(): string
    {
        $password = urlencode($this->config['password']);
        $user = urlencode($this->config['user']);
        
        return "{$this->scheme}://{$user}:{$password}@{$this->config['host']}:{$this->config['port']}";
    }

    /**
     * Build lftp settings
     */
    protected function buildLftpSettings(): string
    {
        $settings = [];
        
        if ($this->scheme === 'ftp') {
            $settings[] = 'set ftp:passive-mode ' . ($this->config['passive'] ? 'on' : 'off');
            $settings[] = 'set ftp:ssl-allow no';
        } else {
            $settings[] = 'set sftp:auto-confirm yes';
        }
        
        $settings[] = 'set net:timeout 30';
        $settings[] = 'set net:max-retries 3';
        
        return implode('; ', $settings);
    }

    /**
     * Execute lftp command
     */
    protected function executeLftp(string $lftpCommand): array
    {
        $url = $this->buildLftpUrl();
        $settings = $this->buildLftpSettings();
        
        $command = sprintf(
            'lftp -c "%s; open %s; %s"',
            $settings,
            escapeshellarg($url),
            $lftpCommand
        );
        
        Log::debug("LFTP Execute: {$command}");
        
        $output = [];
        $returnCode = 0;
        exec($command . ' 2>&1', $output, $returnCode);
        
        return [
            'success' => $returnCode === 0,
            'output' => implode("\n", $output),
            'code' => $returnCode,
        ];
    }

    /**
     * Execute command on remote server (limited support for FTP)
     */
    public function execute(string $command): array
    {
        // FTP doesn't support arbitrary command execution
        // For SFTP, we can use a workaround
        if ($this->scheme === 'sftp') {
            $sshConfig = [
                'host' => $this->config['host'],
                'user' => $this->config['user'],
                'port' => $this->config['port'],
                'password' => $this->config['password'],
            ];
            $ssh = new SshConnection($sshConfig);
            return $ssh->execute($command);
        }
        
        return [
            'success' => false,
            'output' => 'Command execution not supported over FTP',
            'code' => 1,
        ];
    }

    /**
     * Upload file to remote server
     */
    public function upload(string $localPath, string $remotePath): bool
    {
        $remoteDir = dirname($remotePath);
        $remoteFile = basename($remotePath);
        
        $lftpCommand = sprintf(
            'cd %s; put %s -o %s',
            escapeshellarg($remoteDir),
            escapeshellarg($localPath),
            escapeshellarg($remoteFile)
        );
        
        $result = $this->executeLftp($lftpCommand);
        return $result['success'];
    }

    /**
     * Download file from remote server
     */
    public function download(string $remotePath, string $localPath): bool
    {
        $localDir = dirname($localPath);
        $localFile = basename($localPath);
        
        $lftpCommand = sprintf(
            'get %s -o %s',
            escapeshellarg($remotePath),
            escapeshellarg($localPath)
        );
        
        $result = $this->executeLftp($lftpCommand);
        return $result['success'];
    }

    /**
     * Sync directory to remote using lftp mirror
     */
    public function syncToRemote(string $localPath, string $remotePath, array $excludes = []): bool
    {
        return $this->mirror($localPath, $remotePath, $excludes, 'push');
    }

    /**
     * Sync directory from remote using lftp mirror
     */
    public function syncFromRemote(string $remotePath, string $localPath, array $excludes = []): bool
    {
        return $this->mirror($localPath, $remotePath, $excludes, 'pull');
    }

    /**
     * Execute lftp mirror command
     */
    protected function mirror(string $localPath, string $remotePath, array $excludes, string $direction): bool
    {
        $mirrorParts = ['mirror'];
        
        if ($direction === 'push') {
            $mirrorParts[] = '--reverse';
        }
        
        $mirrorParts[] = '--delete';
        $mirrorParts[] = '--verbose';
        $mirrorParts[] = '--parallel=4';
        
        // Excludes
        foreach ($excludes as $exclude) {
            $mirrorParts[] = '--exclude=' . escapeshellarg($exclude);
        }
        
        if ($direction === 'push') {
            $mirrorParts[] = escapeshellarg(rtrim($localPath, '/'));
            $mirrorParts[] = escapeshellarg(rtrim($remotePath, '/'));
        } else {
            $mirrorParts[] = escapeshellarg(rtrim($remotePath, '/'));
            $mirrorParts[] = escapeshellarg(rtrim($localPath, '/'));
        }
        
        $lftpCommand = implode(' ', $mirrorParts);
        
        // For progress output, we need to run interactively
        $url = $this->buildLftpUrl();
        $settings = $this->buildLftpSettings();
        
        $command = sprintf(
            'lftp -c "%s; open %s; %s"',
            $settings,
            escapeshellarg($url),
            $lftpCommand
        );
        
        Log::debug("LFTP Mirror: {$command}");
        
        passthru($command, $returnCode);
        
        return $returnCode === 0;
    }

    /**
     * Check if remote path exists
     */
    public function exists(string $remotePath): bool
    {
        $result = $this->executeLftp('cls ' . escapeshellarg($remotePath));
        return $result['success'];
    }

    /**
     * Create directory on remote
     */
    public function mkdir(string $remotePath, bool $recursive = true): bool
    {
        $command = $recursive ? 'mkdir -p' : 'mkdir';
        $result = $this->executeLftp("{$command} " . escapeshellarg($remotePath));
        return $result['success'];
    }

    /**
     * Delete file or directory on remote
     */
    public function delete(string $remotePath, bool $recursive = false): bool
    {
        $command = $recursive ? 'rm -rf' : 'rm -f';
        $result = $this->executeLftp("{$command} " . escapeshellarg($remotePath));
        return $result['success'];
    }

    /**
     * Get connection info for display
     */
    public function getConnectionInfo(): string
    {
        $scheme = strtoupper($this->scheme);
        return "{$scheme}: {$this->config['user']}@{$this->config['host']}:{$this->config['port']}";
    }
}
