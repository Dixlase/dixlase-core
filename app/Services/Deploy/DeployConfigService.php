<?php

declare(strict_types=1);

namespace App\Services\Deploy;

use Illuminate\Support\Facades\File;

/**
 * Service for managing deployment configuration
 */
class DeployConfigService
{
    /**
     * Default config file name
     */
    public const CONFIG_FILE = 'dixlase-deploy.json';

    /**
     * Supported connection types
     */
    public const CONNECTION_SSH = 'ssh';
    public const CONNECTION_FTP = 'ftp';
    public const CONNECTION_SFTP = 'sftp';

    /**
     * Sync targets
     */
    public const TARGET_CORE = 'core';
    public const TARGET_PLUGINS = 'plugins';
    public const TARGET_THEMES = 'themes';
    public const TARGET_CUSTOM = 'custom';
    public const TARGET_UPLOADS = 'uploads';
    public const TARGET_DATABASE = 'database';

    protected ?array $config = null;
    protected string $configPath;

    public function __construct(?string $configPath = null)
    {
        $this->configPath = $configPath ?? base_path(self::CONFIG_FILE);
    }

    /**
     * Check if config file exists
     */
    public function configExists(): bool
    {
        return File::exists($this->configPath);
    }

    /**
     * Load and parse configuration
     */
    public function load(): array
    {
        if ($this->config !== null) {
            return $this->config;
        }

        if (!$this->configExists()) {
            throw new \RuntimeException("Configuration file not found: {$this->configPath}");
        }

        $content = File::get($this->configPath);
        
        // Remove JSON comments (keys starting with _)
        $this->config = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("Invalid JSON in configuration file: " . json_last_error_msg());
        }
        
        // Process environment variables
        $this->config = $this->processEnvironmentVariables($this->config);
        
        // Remove comment keys
        $this->config = $this->removeCommentKeys($this->config);
        
        return $this->config;
    }

    /**
     * Process environment variables in config array (${VAR_NAME} format)
     */
    protected function processEnvironmentVariables(array $config): array
    {
        array_walk_recursive($config, function (&$value) {
            if (is_string($value)) {
                $value = preg_replace_callback(
                    '/\$\{([^}]+)\}/',
                    function ($matches) {
                        $envVar = $matches[1];
                        return env($envVar, getenv($envVar) ?: '');
                    },
                    $value
                );
            }
        });
        
        return $config;
    }

    /**
     * Remove keys starting with underscore (comments)
     */
    protected function removeCommentKeys(array $config): array
    {
        foreach ($config as $key => $value) {
            if (str_starts_with($key, '_')) {
                unset($config[$key]);
            } elseif (is_array($value)) {
                $config[$key] = $this->removeCommentKeys($value);
            }
        }
        
        return $config;
    }

    /**
     * Get local configuration
     */
    public function getLocal(): array
    {
        $config = $this->load();
        return $config['local'] ?? [];
    }

    /**
     * Get environment configuration
     */
    public function getEnvironment(string $environment): array
    {
        $config = $this->load();
        
        if (!isset($config[$environment])) {
            throw new \InvalidArgumentException("Environment '{$environment}' not found in configuration");
        }
        
        return $config[$environment];
    }

    /**
     * Get available environments (excluding local and global)
     */
    public function getAvailableEnvironments(): array
    {
        $config = $this->load();
        
        return array_filter(
            array_keys($config),
            fn($key) => !in_array($key, ['local', 'global'])
        );
    }

    /**
     * Get global configuration
     */
    public function getGlobal(): array
    {
        $config = $this->load();
        return $config['global'] ?? [];
    }

    /**
     * Get connection type for environment
     */
    public function getConnectionType(string $environment): string
    {
        $envConfig = $this->getEnvironment($environment);
        
        if (isset($envConfig['ssh'])) {
            return self::CONNECTION_SSH;
        }
        
        if (isset($envConfig['ftp'])) {
            $scheme = $envConfig['ftp']['scheme'] ?? 'ftp';
            return $scheme === 'sftp' ? self::CONNECTION_SFTP : self::CONNECTION_FTP;
        }
        
        $global = $this->getGlobal();
        return $global['default_connection'] ?? self::CONNECTION_SSH;
    }

    /**
     * Get connection configuration for environment
     */
    public function getConnectionConfig(string $environment): array
    {
        $envConfig = $this->getEnvironment($environment);
        $type = $this->getConnectionType($environment);
        
        return match ($type) {
            self::CONNECTION_SSH => $envConfig['ssh'] ?? [],
            self::CONNECTION_FTP, self::CONNECTION_SFTP => $envConfig['ftp'] ?? [],
            default => [],
        };
    }

    /**
     * Get database configuration for environment
     */
    public function getDatabaseConfig(string $environment): array
    {
        $envConfig = $this->getEnvironment($environment);
        return $envConfig['database'] ?? [];
    }

    /**
     * Get exclude patterns for environment
     */
    public function getExcludePatterns(string $environment): array
    {
        $envConfig = $this->getEnvironment($environment);
        return $envConfig['exclude'] ?? $this->getDefaultExcludes();
    }

    /**
     * Get default exclude patterns
     */
    public function getDefaultExcludes(): array
    {
        return [
            '.git/',
            '.gitignore',
            'node_modules/',
            'vendor/',
            '.env',
            '.env.*',
            'dixlasemove.yml',
            'storage/logs/*',
            'storage/framework/cache/*',
            'storage/framework/sessions/*',
            'storage/framework/views/*',
            'bootstrap/cache/*',
            '*.sql',
            '*.sql.gz',
        ];
    }

    /**
     * Get hooks for environment and action
     */
    public function getHooks(string $environment, string $action): array
    {
        $envConfig = $this->getEnvironment($environment);
        return $envConfig['hooks'][$action] ?? [];
    }

    /**
     * Get paths configuration
     */
    public function getPaths(string $environment): array
    {
        $envConfig = $environment === 'local' 
            ? $this->getLocal() 
            : $this->getEnvironment($environment);
            
        return array_merge([
            'plugins' => 'plugins',
            'themes' => 'themes',
            'custom' => 'custom',
            'uploads' => 'storage/app/public',
        ], $envConfig['paths'] ?? []);
    }

    /**
     * Get Dixlase path for environment
     */
    public function getDixlasePath(string $environment): string
    {
        $envConfig = $environment === 'local' 
            ? $this->getLocal() 
            : $this->getEnvironment($environment);
            
        return $envConfig['dixlase_path'] ?? '';
    }

    /**
     * Get vhost for environment
     */
    public function getVhost(string $environment): string
    {
        $envConfig = $environment === 'local' 
            ? $this->getLocal() 
            : $this->getEnvironment($environment);
            
        return $envConfig['vhost'] ?? '';
    }

    /**
     * Validate configuration
     */
    public function validate(): array
    {
        $errors = [];
        
        try {
            $config = $this->load();
        } catch (\Exception $e) {
            return ["Failed to parse configuration: {$e->getMessage()}"];
        }
        
        // Validate local configuration
        if (!isset($config['local'])) {
            $errors[] = "Missing 'local' configuration";
        } else {
            if (empty($config['local']['dixlase_path'])) {
                $errors[] = "Missing 'local.dixlase_path'";
            }
            if (empty($config['local']['database'])) {
                $errors[] = "Missing 'local.database' configuration";
            }
        }
        
        // Validate environments
        foreach ($this->getAvailableEnvironments() as $env) {
            $envConfig = $config[$env];
            
            if (empty($envConfig['dixlase_path'])) {
                $errors[] = "Missing '{$env}.dixlase_path'";
            }
            
            if (empty($envConfig['ssh']) && empty($envConfig['ftp'])) {
                $errors[] = "Missing connection configuration for '{$env}' (ssh or ftp required)";
            }
            
            if (isset($envConfig['ssh'])) {
                if (empty($envConfig['ssh']['host'])) {
                    $errors[] = "Missing '{$env}.ssh.host'";
                }
                if (empty($envConfig['ssh']['user'])) {
                    $errors[] = "Missing '{$env}.ssh.user'";
                }
            }
            
            if (isset($envConfig['ftp'])) {
                if (empty($envConfig['ftp']['host'])) {
                    $errors[] = "Missing '{$env}.ftp.host'";
                }
                if (empty($envConfig['ftp']['user'])) {
                    $errors[] = "Missing '{$env}.ftp.user'";
                }
            }
        }
        
        return $errors;
    }

    /**
     * Generate initial configuration file
     */
    public function generateConfig(): string
    {
        $stubPath = config('command.dixlase_stub_directory') . '/dixlase-deploy.stub';
        
        if (!File::exists($stubPath)) {
            throw new \RuntimeException("Stub file not found: {$stubPath}");
        }
        
        $content = File::get($stubPath);
        
        // Parse JSON to modify values
        $config = json_decode($content, true);
        
        if ($config === null) {
            throw new \RuntimeException("Invalid JSON in stub file");
        }
        
        // Update local settings with current environment
        $config['local']['vhost'] = config('app.url');
        $config['local']['dixlase_path'] = base_path();
        $config['local']['database']['name'] = config('database.connections.mysql.database');
        $config['local']['database']['user'] = config('database.connections.mysql.username');
        $config['local']['database']['password'] = config('database.connections.mysql.password');
        $config['local']['database']['host'] = config('database.connections.mysql.host', 'localhost');
        $config['local']['database']['port'] = (int) config('database.connections.mysql.port', 3306);
        
        // Write formatted JSON
        $content = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        
        File::put($this->configPath, $content);
        
        return $this->configPath;
    }
}
