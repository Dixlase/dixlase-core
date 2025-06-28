<?php

return [
    'keys' => [
        'GPL',
        'AGPL',
        'MIT',
        'Apache',
        'BSD',
        'LGPL',
        'commercial',
        'custom',
        'none',
    ],

    'map' => [
        'GPL' => 'gpl',
        'AGPL' => 'agpl',
        'MIT' => 'mit',
        'Apache' => 'apache',
        'BSD' => 'bsd',
        'LGPL' => 'lgpl',
        'commercial' => 'commercial',
        'custom' => 'custom',
        'none' => '',
    ],

    'templates' => [
        'GPL' => 'license-templates/license-gpl.txt',
        'AGPL' => 'license-templates/license-agpl.txt',
        'MIT' => 'license-templates/license-mit.txt',
        'Apache' => 'license-templates/license-apache.txt',
        'BSD' => 'license-templates/license-bsd.txt',
        'LGPL' => 'license-templates/license-lgpl.txt',
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
