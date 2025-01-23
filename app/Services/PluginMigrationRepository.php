<?php

namespace App\Services;

use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Database\ConnectionResolverInterface;

class PluginMigrationRepository extends DatabaseMigrationRepository
{
    protected $plugin;

    public function __construct(ConnectionResolverInterface $resolver, $table, $plugin = null)
    {
        parent::__construct($resolver, $table);
        $this->plugin = $plugin;
    }

    /**
     * プラグイン名に基づいて実行済みマイグレーションを取得
     */
    public function getRan($plugin = null)
    {
        if ($plugin) {
            return $this->table()
                ->where('plugin', $plugin)
                ->pluck('migration')
                ->all();
        }

        return parent::getRan();
    }

    /**
     * マイグレーションを記録
     */
    public function log($file, $batch, $plugin = null)
    {
        $this->table()->insert([
            'migration' => $file,
            'batch' => $batch,
            'plugin' => $plugin,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
