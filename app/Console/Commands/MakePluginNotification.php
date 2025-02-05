<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Services\FileGenerator;
use App\Console\Traits\MakeNotificationTrait;

class MakePluginNotification extends Command
{

    use MakeNotificationTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:plugin:notification
        {plugin : The plugin name}
        {name : The notification class (with optional subfolders, e.g. Admin/SendUpdate)}
        {--force : Overwrite if notification already exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new notification class for the specified plugin.';

    protected FileGenerator $fileGenerator;

    public function __construct(FileGenerator $fileGenerator)
    {
        parent::__construct();
        $this->fileGenerator = $fileGenerator;
    }

    public function handle()
    {
        // 1) plugin名
        $pluginName = Str::studly($this->argument('plugin'));

        // 2) subDirs + className
        [$subDirs, $className] = $this->fileGenerator->parseClassName($this->argument('name'));

        // 3) --force
        $force = (bool) $this->option('force');

        // 4) Traitの makeFile(...) を呼ぶ
        $this->makeFile($className, $subDirs, $force);

        return 0;
    }

    /**
     * 通知ディレクトリ/名前空間
     * (B)パターンで getDirectory/getNamespace => getNotificationDirectory/Namespace
     */
    protected function getNotificationDirectory(array $subDirs): string
    {
        $pluginName = Str::studly($this->argument('plugin'));
        $base = base_path("plugins/{$pluginName}/app/Notifications");
        if ($subDirs) {
            $base .= '/' . implode('/', $subDirs);
        }
        return $base;
    }

    protected function getNotificationNamespace(array $subDirs): string
    {
        $pluginName = Str::studly($this->argument('plugin'));
        $base = "Plugins\\{$pluginName}\\App\\Notifications";
        if ($subDirs) {
            $base .= '\\' . implode('\\', $subDirs);
        }
        return $base;
    }
}
