<?php

return [

        'description' => 'Install a theme into the database',
        'theme_name_prompt' => 'The name of the theme to install',
        'theme_not_found' => 'Theme \':themeName\' does not exist in the themes directory.',
        'already_registered' => 'Theme \':themeName\' is already registered in the database.',
        'registered' => 'Theme \':themeName\' has been registered in the database.',
        'enable_help' => 'You can now enable it using: php artisan dls:theme:enable :themeName',
];
