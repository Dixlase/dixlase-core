<?php

return [
    App\Providers\AdminServiceProvider::class,
    App\Providers\AppServiceProvider::class,
    App\Providers\AuditServiceProvider::class,
    App\Providers\CaptchaServiceProvider::class,
    App\Providers\CspServiceProvider::class,
    App\Providers\EventServiceProvider::class,
    App\Providers\MyTestServiceProvider::class,
    App\Providers\PluginMigrationServiceProvider::class,
    App\Providers\PluginServiceProvider::class,
    App\Providers\RepositoryServiceProvider::class,
    App\Providers\ShortcodeServiceProvider::class,
    App\Providers\ThemeServiceProvider::class,
    Themes\DixlaseDefaultTheme\App\Providers\DixlaseDefaultThemeServiceProvider::class,
];
