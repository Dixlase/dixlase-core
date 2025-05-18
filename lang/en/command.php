<?php

return [
    "make_plugin" => [
        "enter_plugin_name" => "Please enter the plugin name",
        "enter_author_name" => "Please enter the developer name",
        "enter_website_url" => "Please enter the developer's website URL (only the part after https://)",
        "select_license" => "Available licenses:",
        "enter_license_number" => "Enter license number (default: none):",
        "confirm_install" => "Do you want to install the plugin?",
        "confirm_enable" => "Do you want to enable the plugin?",
        "success" => "Plugin :name has been created successfully!",
        "already_exists" => "The plugin ':name' already exists.",
        "installed" => "Plugin :name has been installed.",
        "enabled" => "Plugin ':name' has been enabled.",
        "not_found" => "Plugin ':name' not found in the database.",
        "no_assets" => "No assets directory found for plugin ':name'.",
        "files" => [
            "service_provider" => "Service provider [:name] created for plugin [:plugin].",
            "controller" => "Controller [:name] created for plugin [:plugin].",
            "model" => "Model [:name] created for plugin [:plugin].",
            "policy" => "Policy [:name] created for plugin [:plugin].",
            "listener" => "Listener [:name] created for plugin [:plugin].",
            "test" => "Test [:name] created for plugin [:plugin].",
            "migration" => "Migration :name created for plugin [:plugin].",
            "resource" => "Resource [:name] created for plugin [:plugin].",
            "command" => "Command [:name] created for plugin [:plugin].",
            "job" => "Job [:name] created for plugin [:plugin].",
            "notification" => "Notification [:name] created for plugin [:plugin].",
            "seeder" => "Seeder :name created for plugin [:plugin].",
            "factory" => "Factory [:name] created for plugin [:plugin].",
            "routes" => "Routes file created for plugin.",
            "config" => "Config file created for plugin.",
            "lang" => "Language files (en & ja) created for plugin.",
            "vite" => "Vite config file created for plugin.",
            "composer" => "Composer.json file created for plugin.",
            "readme" => "README.md created for plugin."
        ],
        "license_options" => [
            "gpl" => "GPL-3.0",
            "agpl" => "AGPL-3.0",
            "mit" => "MIT",
            "apache" => "Apache-2.0",
            "bsd3" => "BSD-3-Clause",
            "lgpl" => "LGPL-3.0",
            "commercial" => "Commercial",
            "custom" => "Custom License"
        ]
    ],
    'file_type' => [
        'prompt' => 'Please select the file type for custom',
        'labels' => [
            'core'   => 'Core file (core) for custom',
            'plugin' => 'Plugin file (plugin) for custom',
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

    'license' => [
        'prompt' => 'Please select a license (press Enter without input to select no license)',
        'using_custom_license' => 'Using custom license: :license',
        'failed_to_read_license' => 'Failed to read custom license file: :error',
        'warnings' => [
            'plugin_missing' => '⚠️ License info for plugin [:plugin] not found. License header will be skipped.',
            'template_not_specified' => '⚠️ License info does not contain a template path.',
            'template_not_found' => '⚠️ Template file not found: :path',
            'template_missing_core' => 'Template not found: :path',
            'notice' => <<<EOT
[!] Note: While you can choose any license for newly created files, please be aware of the following:
・Files that extend core classes, use core traits, implement core interfaces, or directly utilize core code will be subject to the core's AGPL license terms.
・However, if you place your code in a completely separate custom directory and maintain loose coupling with the core (e.g., through event listeners), you can choose your own license.
・If you choose AGPL and implement features that allow data input from general users (e.g., forms), source code disclosure will be required. In such cases, we recommend using alternative licenses like GPL or MIT.
・For client-specific deliverables intended for internal use and not public access, no license notice is required.
・Please carefully review the license terms if redistribution or SaaS deployment is planned.
EOT,
        ],
    ],
    'plugin' => [
        'prompt' => 'Please select a plugin',
        'not_found' => 'No plugins found. At least one plugin directory must exist under plugins.',
    ],
        'files' => [
        'category' => [
            'controllers' => 'Controller',
            'requests'    => 'Request',
            'services'    => 'Service',
            'repositories' => 'Repository',
            'models' => 'Model',
            'default'    => 'File',
        ],
        'created' => 'created!',
    ],
    'make' => [
        'options' => [
            'all' => 'Generate a migration, seeder, factory, policy, resource controller and form requests',
            'controller' => 'Create a new controller for the model',
            'factory' => 'Create a new factory for the model',
            'migration' => 'Create a new migration file for the model',
            'policy' => 'Create a new policy for the model',
            'seed' => 'Create a new seeder for the model',
            'api' => 'Exclude the create and edit methods from the controller',
            'requests' => 'Create form request classes for the controller',
            'invokable' => 'Generate a single method, invokable controller class',
            'model' => 'Generate a resource controller for the given model',
            'parent' => 'Generate a nested resource controller class',
            'resource' => 'Generate a resource controller class',
            'singleton' => 'Generate a singleton resource controller class',
            'creatable' => 'Generate a resource controller with create and store methods',
        ],
        'common' => [
            'class_name' => 'Class name',
            'force' => 'Overwrite existing files',
        ]
    ],
    'file' => [
        'already_exists' => 'File already exists: :path',
        'created' => 'File created: :path',
    ]
];
