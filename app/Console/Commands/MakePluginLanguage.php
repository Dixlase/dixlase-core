<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Services\FileGenerator;
use App\Console\Traits\MakeLanguageTrait;

class MakePluginLanguage extends Command
{
    use MakeLanguageTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:plugin:lang
        {plugin : The plugin name (e.g. MyPlugin)}
        {lang : The language code (e.g. en, ja)}
        {file : The language file name (e.g. messages)}
        {--force : Overwrite if the file already exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new language file in the specified plugin\'s lang directory';

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
        $plugin = Str::studly($this->argument('plugin'));
        $langCode = strtolower($this->argument('lang'));
        $fileName = $this->argument('file');
        $options = ['force' => (bool) $this->option('force')];

        $this->makeLanguageFile($langCode, $fileName, $options);

        return 0;
    }

    /**
     * プラグイン用 => "plugins/{Plugin}/lang"
     */
    protected function getDirectory(array $subDirs): string
    {
        $pluginName = Str::studly($this->argument('plugin'));
        return base_path("plugins/{$pluginName}/lang/" . implode('/', $subDirs));
    }
}
