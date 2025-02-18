<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FileGenerator;
use App\Console\Traits\MakeLanguageTrait;

class MakeCustomLanguage extends Command
{
    use MakeLanguageTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:custom:lang
        {lang : The language code (e.g. en, ja)}
        {file : The language file name (e.g. messages)}
        {--force : Overwrite if the file already exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new language file in the custom/lang directory';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $langCode = strtolower($this->argument('lang'));
        $fileName = $this->argument('file');
        $options = ['force' => (bool) $this->option('force')];

        $this->makeLanguageFile($langCode, $fileName, $options);

        return 0;
    }

    /**
     * カスタム用 => custom/lang
     */
    protected function getDirectory(array $subDirs): string
    {
        return base_path('custom/lang/' . implode('/', $subDirs));
    }
}
