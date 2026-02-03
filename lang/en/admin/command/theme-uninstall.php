<?php

return [

        'description' => 'Uninstall a theme (files will be preserved)',
        'theme_name_prompt' => 'The name of the theme to uninstall',
        'theme_not_found' => 'Theme \':themeName\' not found in the database.',
        'not_installed' => 'Theme \':themeName\' is not installed.',
        'cannot_uninstall_enabled' => 'Cannot uninstall enabled theme \':themeName\'.',
        'disable_first' => 'Please disable the theme first using `dls:theme:disable` command before uninstalling.',
        'confirmation' => 'Are you sure you want to uninstall theme \':themeName\'?',
        'cancelled' => 'Uninstallation cancelled.',
        'uninstalled' => 'Uninstalled theme: :themeName',
        'files_preserved' => 'Theme files and directory have been preserved.',
        'delete_hint' => 'To delete the files, run `php artisan theme:delete <directory>` command.',
];
