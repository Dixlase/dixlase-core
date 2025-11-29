<?php

declare(strict_types=1);

namespace App\Services\Deploy;

use Illuminate\Support\Facades\Log;

/**
 * SSH connection handler using rsync for file sync
 */
class SshConnection implements ConnectionInterface
{
    protected array $config;
    protected bool $connected = false;
    protected ?string $sshCommand = null;

    public function __construct(array $config)
    {
        $this->config = array_merge([
            'host' => '',
            'user' => '',
            'port' => 22,
            'key' => null,
            'password' => null,
        ], $config);
    }

    /**
     * Connect to remote server (test connection)
     */
    public function connect(): bool
    {
        $result = $this->execute('echo "connected"');
        $this->connected = $result['success'] && trim($result['output']) === 'connected';
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
     * Build SSH command base
     */
    protected function buildSshCommand(): string
    {
        if ($this->sshCommand !== null) {
            return $this->sshCommand;
        }

        $parts = ['ssh'];
        
        // Port
        if ($this->config['port'] !== 22) {
            $parts[] = '-p ' . escapeshellarg((string)$this->config['port']);
        }
        
        // Key file
        if (!empty($this->config['key'])) {
            $keyPath = $this->expandPath($this->config['key']);
            $parts[] = '-i ' . escapeshellarg($keyPath);
        }
        
        // Disable strict host key checking for automation
        $parts[] = '-o StrictHostKeyChecking=no';
        $parts[] = '-o BatchMode=yes';
        
        // User@host
        $parts[] = escapeshellarg($this->config['user'] . '@' . $this->config['host']);
        
        $this->sshCommand = implode(' ', $parts);
        return $this->sshCommand;
    }

    /**
     * Expand ~ in path
     */
    protected function expandPath(string $path): string
    {
        if (str_starts_with($path, '~/')) {
            $home = getenv('HOME') ?: posix_getpwuid(posix_getuid())['dir'];
            return $home . substr($path, 1);
        }
        return $path;
    }

    /**
     * Execute command on remote server
     */
    public function execute(string $command): array
    {
        $sshCommand = $this->buildSshCommand() . ' ' . escapeshellarg($command);
        
        Log::debug("SSH Execute: {$sshCommand}");
        
        $output = [];
        $returnCode = 0;
        exec($sshCommand . ' 2>&1', $output, $returnCode);
        
        return [
            'success' => $returnCode === 0,
            'output' => implode("\n", $output),
            'code' => $returnCode,
        ];
    }

    /**
     * Upload file to remote server using scp
     */
    public function upload(string $localPath, string $remotePath): bool
    {
        $scpParts = ['scp'];
        
        if ($this->config['port'] !== 22) {
            $scpParts[] = '-P ' . escapeshellarg((string)$this->config['port']);
        }
        
        if (!empty($this->config['key'])) {
            $keyPath = $this->expandPath($this->config['key']);
            $scpParts[] = '-i ' . escapeshellarg($keyPath);
        }
        
        $scpParts[] = '-o StrictHostKeyChecking=no';
        $scpParts[] = escapeshellarg($localPath);
        $scpParts[] = escapeshellarg(
            $this->config['user'] . '@' . $this->config['host'] . ':' . $remotePath
        );
        
        $command = implode(' ', $scpParts);
        Log::debug("SCP Upload: {$command}");
        
        $output = [];
        $returnCode = 0;
        exec($command . ' 2>&1', $output, $returnCode);
        
        return $returnCode === 0;
    }

    /**
     * Download file from remote server using scp
     */
    public function download(string $remotePath, string $localPath): bool
    {
        $scpParts = ['scp'];
        
        if ($this->config['port'] !== 22) {
            $scpParts[] = '-P ' . escapeshellarg((string)$this->config['port']);
        }
        
        if (!empty($this->config['key'])) {
            $keyPath = $this->expandPath($this->config['key']);
            $scpParts[] = '-i ' . escapeshellarg($keyPath);
        }
        
        $scpParts[] = '-o StrictHostKeyChecking=no';
        $scpParts[] = escapeshellarg(
            $this->config['user'] . '@' . $this->config['host'] . ':' . $remotePath
        );
        $scpParts[] = escapeshellarg($localPath);
        
        $command = implode(' ', $scpParts);
        Log::debug("SCP Download: {$command}");
        
        $output = [];
        $returnCode = 0;
        exec($command . ' 2>&1', $output, $returnCode);
        
        return $returnCode === 0;
    }

    /**
     * Sync directory to remote using rsync
     */
    public function syncToRemote(string $localPath, string $remotePath, array $excludes = []): bool
    {
        return $this->rsync($localPath, $remotePath, $excludes, 'push');
    }

    /**
     * Sync directory from remote using rsync
     */
    public function syncFromRemote(string $remotePath, string $localPath, array $excludes = []): bool
    {
        return $this->rsync($localPath, $remotePath, $excludes, 'pull');
    }

    /**
     * Execute rsync command
     */
    protected function rsync(string $localPath, string $remotePath, array $excludes, string $direction): bool
    {
        $rsyncParts = [
            'rsync',
            '-avz',
            '--delete',
            '--progress',
        ];
        
        // SSH options
        $sshOptions = 'ssh';
        if ($this->config['port'] !== 22) {
            $sshOptions .= ' -p ' . $this->config['port'];
        }
        if (!empty($this->config['key'])) {
            $keyPath = $this->expandPath($this->config['key']);
            $sshOptions .= ' -i ' . escapeshellarg($keyPath);
        }
        $sshOptions .= ' -o StrictHostKeyChecking=no';
        
        $rsyncParts[] = '-e ' . escapeshellarg($sshOptions);
        
        // Excludes
        foreach ($excludes as $exclude) {
            $rsyncParts[] = '--exclude=' . escapeshellarg($exclude);
        }
        
        // Ensure paths end with /
        $localPath = rtrim($localPath, '/') . '/';
        $remotePath = rtrim($remotePath, '/') . '/';
        
        $remoteSpec = $this->config['user'] . '@' . $this->config['host'] . ':' . $remotePath;
        
        if ($direction === 'push') {
            $rsyncParts[] = escapeshellarg($localPath);
            $rsyncParts[] = escapeshellarg($remoteSpec);
        } else {
            $rsyncParts[] = escapeshellarg($remoteSpec);
            $rsyncParts[] = escapeshellarg($localPath);
        }
        
        $command = implode(' ', $rsyncParts);
        Log::debug("Rsync: {$command}");
        
        // Execute with passthru for progress output
        passthru($command, $returnCode);
        
        return $returnCode === 0;
    }

    /**
     * Check if remote path exists
     */
    public function exists(string $remotePath): bool
    {
        $result = $this->execute("test -e " . escapeshellarg($remotePath) . " && echo 'exists'");
        return $result['success'] && trim($result['output']) === 'exists';
    }

    /**
     * Create directory on remote
     */
    public function mkdir(string $remotePath, bool $recursive = true): bool
    {
        $flag = $recursive ? '-p' : '';
        $result = $this->execute("mkdir {$flag} " . escapeshellarg($remotePath));
        return $result['success'];
    }

    /**
     * Delete file or directory on remote
     */
    public function delete(string $remotePath, bool $recursive = false): bool
    {
        $flag = $recursive ? '-rf' : '-f';
        $result = $this->execute("rm {$flag} " . escapeshellarg($remotePath));
        return $result['success'];
    }

    /**
     * Get connection info for display
     */
    public function getConnectionInfo(): string
    {
        $port = $this->config['port'] !== 22 ? ":{$this->config['port']}" : '';
        return "SSH: {$this->config['user']}@{$this->config['host']}{$port}";
    }
}
