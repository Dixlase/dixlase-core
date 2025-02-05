<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Services\FileGenerator;

class MakePluginJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plugin:make:job
                            {plugin : The plugin name}
                            {name : The name of the job}
                            {--force : Create the class even if the job already exists}
                            {--sync : Indicates that job should be synchronous}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new job class for the specified plugin.';

    protected FileGenerator $fileGenerator;

    public function __construct(FileGenerator $fileGenerator)
    {
        parent::__construct();
        $this->fileGenerator = $fileGenerator;
    }


    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 1) plugin名
        $pluginName = Str::studly($this->argument('plugin'));

        // 2) subDirs + className を parse する (もしサブフォルダ対応したければ)
        //    例: "Admin/BulkImportJob" => ["Admin"], "BulkImportJob"
        [$subDirs, $className] = $this->fileGenerator->parseClassName($this->argument('name'));

        // 3) --force, --sync
        $force = (bool) $this->option('force');
        $sync  = (bool) $this->option('sync');

        // Traitの makeFile(...) を呼ぶ
        $this->makeFile($className, $subDirs, $force, $sync);

        return 0;
    }

    /**
     * (B)パターン: getJobDirectory/Namespaceを実装し、Trait内の getDirectory/getNamespace をクリア
     */
    protected function getJobDirectory(array $subDirs): string
    {
        $plugin = Str::studly($this->argument('plugin'));
        $base = base_path("plugins/{$plugin}/app/Jobs");
        if ($subDirs) {
            $base .= '/' . implode('/', $subDirs);
        }
        return $base;
    }

    protected function getJobNamespace(array $subDirs): string
    {
        $plugin = Str::studly($this->argument('plugin'));
        $base = "Plugins\\{$plugin}\\App\\Jobs";
        if ($subDirs) {
            $base .= '\\' . implode('\\', $subDirs);
        }
        return $base;
    }
}
