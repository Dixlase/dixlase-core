<?php

return [
    'file_type' => [
        'prompt' => 'Please select the file type',
        'labels' => [
            'core'   => 'Core file (core)',
            'plugin' => 'Plugin file (plugin)',
        ],
    ],
    'scope' => [
        'prompt' => 'Please select the controller scope',
        'labels' => [
            'plain' => 'No scope',
            'front' => 'Frontend',
            'admin' => 'Admin panel',
        ],
    ],
    'plugin' => [
        'prompt' => 'Please select a plugin',
        'not_found' => 'No plugins found. At least one plugin directory must exist under plugins.',
    ],
];
