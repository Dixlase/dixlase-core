<?php

return [
    'keys' => [
        'GPL-3.0',
        'AGPL-3.0',
        'MIT',
        'Apache-2.0',
        'BSD-3-Clause',
        'LGPL-3.0',
        'commercial',
        'custom',
        'none',
    ],

    'map' => [
        'GPL-3.0' => 'gpl',
        'AGPL-3.0' => 'agpl',
        'MIT' => 'mit',
        'Apache-2.0' => 'apache',
        'BSD-3-Clause' => 'bsd3',
        'LGPL-3.0' => 'lgpl',
        'commercial' => 'commercial',
        'custom' => 'custom',
        'none' => '',
    ],

    'templates' => [
        'GPL-3.0' => 'license-templates/license-gpl.txt',
        'AGPL-3.0' => 'license-templates/license-agpl.txt',
        'MIT' => 'license-templates/license-mit.txt',
        'Apache-2.0' => 'license-templates/license-apache.txt',
        'BSD-3-Clause' => 'license-templates/license-bsd3.txt',
        'LGPL-3.0' => 'license-templates/license-lgpl.txt',
        'commercial' => 'license-templates/license-commercial.txt',
        'custom' => 'license-templates/license-custom.txt',
        'none' => '',
    ],
    'defaults' => [
        'software' => 'Dixlase',
        'author' => 'MyNameOrCompany',
        'website' => 'https://companyname.com',
    ],
];
