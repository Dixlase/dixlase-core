<?php

return [

        'description' => 'Uninstall the plugin and remove from database (files are preserved).',
        'not_found' => 'Plugin \':pluginName\' not found.',
        'still_enabled' => 'Plugin \':pluginName\' is still enabled.',
        'disable_first' => 'Please disable the plugin first using `plugin:disable` command before uninstalling.',
        'force_disabling' => 'Force disabling plugin \':pluginName\' due to --force option.',
        'confirm' => 'Are you sure you want to uninstall plugin \':pluginName\'? This will remove plugin information from the database.',
        'cancelled' => 'Uninstallation cancelled.',
        'rollback_running' => 'Running migrations rollback...',
        'rollback_confirm' => 'Do you want to delete database tables related to plugin \':pluginName\'?',
        'rollback_skipped' => 'Database rollback was skipped.',
        'files_preserved' => 'Plugin files and directories have been preserved.',
        'database_removed' => 'Plugin \':pluginName\' has been removed from the database.',
        'completed' => 'Plugin \':pluginName\' has been uninstalled successfully.',
        'delete_hint' => 'To delete files, run `php artisan plugin:delete <directory>` command.',
];
