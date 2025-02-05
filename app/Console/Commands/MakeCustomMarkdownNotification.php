<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FileGenerator;
use App\Console\Traits\MakeMarkdownNotificationTrait;

class MakeCustomMarkdownNotification extends Command
{
    use MakeMarkdownNotificationTrait;

    protected $signature = 'make:custom:markdown-notification
        {name : The notification class name (e.g. Admin/NewMarkdownNotification)}
        {--view=notifications.example : The Markdown Blade view name}
        {--force : Overwrite if the class already exists}';

    protected $description = 'Create a new Markdown-based notification class in the custom directory';

    protected FileGenerator $fileGenerator;

    public function __construct(FileGenerator $fileGenerator)
    {
        parent::__construct();
        $this->fileGenerator = $fileGenerator;
    }

    public function handle()
    {
        // 1) parse subDirs + className
        [$subDirs, $className] = $this->fileGenerator->parseClassName($this->argument('name'));

        // 2) --view
        $view = $this->option('view') ?: 'notifications.example';

        // 3) --force
        $force = (bool)$this->option('force');

        // 4) Trait method
        $this->makeFile($className, $subDirs, $force, $view);

        return 0;
    }

    /**
     * (B)パターン: getMarkdownNotificationDirectory/Namespace
     */
    protected function getMarkdownNotificationDirectory(array $subDirs): string
    {
        $base = base_path('custom/app/Notifications');
        if ($subDirs) {
            $base .= '/' . implode('/', $subDirs);
        }
        return $base;
    }

    protected function getMarkdownNotificationNamespace(array $subDirs): string
    {
        $base = 'Custom\\App\\Notifications';
        if ($subDirs) {
            $base .= '\\' . implode('\\', $subDirs);
        }
        return $base;
    }
}
