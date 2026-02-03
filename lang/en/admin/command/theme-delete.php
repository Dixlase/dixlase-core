<?php

return [

        'description' => 'Delete theme files and directory (theme must be uninstalled first)',
        'theme_directory_prompt' => 'The directory name of the theme to delete',
        'force_option' => 'Force delete without confirmation',
        'not_found' => 'Theme directory \':directory\' not found.',
        'still_installed' => 'Theme \':themeName\' is still installed.',
        'still_enabled' => 'Theme \':themeName\' is still enabled.',
        'uninstall_first' => 'Please uninstall the theme first using `dls:theme:uninstall` command before deleting.',
        'disable_first' => 'Please switch to a different theme before deleting.',
        'confirm' => 'Are you sure you want to delete theme directory \':directory\' and all its files? This action cannot be undone.',
        'cancelled' => 'Deletion cancelled.',
        'deleted' => 'Deleted theme directory: :path',
        'failed' => 'Failed to delete theme directory: :error',
        'database_removed' => 'Removed theme \':themeName\' from database.',
        'completed' => 'Theme \':directory\' deletion completed.',
];
