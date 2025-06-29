<?php

return [
    'templatesPath' => 'license-templates',
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
        'GPL' => 'license-gpl.txt',
        'AGPL' => 'license-agpl.txt',
        'MIT' => 'license-mit.txt',
        'Apache' => 'license-apache.txt',
        'BSD' => 'license-bsd.txt',
        'LGPL' => 'license-lgpl.txt',
        'commercial' => 'license-commercial.txt',
        'custom' => 'license-custom.txt',
        'none' => '',
    ],
    'defaults' => [
        'software' => 'Dixlase',
        'author' => 'MyNameOrCompany',
        'website' => 'https://companyname.com',
    ],
];
