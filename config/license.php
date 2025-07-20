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
        'COMMERCIAL',
        'CUSTOM',
        'NONE',
    ],

    'map' => [
        'GPL' => 'gpl',
        'AGPL' => 'agpl',
        'MIT' => 'mit',
        'Apache' => 'apache',
        'BSD' => 'bsd',
        'LGPL' => 'lgpl',
        'COMMERCIAL' => 'commercial',
        'CUSTOM' => 'custom',
        'NONE' => '',
    ],

    'templates' => [
        'GPL' => 'license-gpl.txt',
        'AGPL' => 'license-agpl.txt',
        'MIT' => 'license-mit.txt',
        'Apache' => 'license-apache.txt',
        'BSD' => 'license-bsd.txt',
        'LGPL' => 'license-lgpl.txt',
        'COMMERCIAL' => 'license-commercial.txt',
        'CUSTOM' => 'license-custom.txt',
        'NONE' => '',
    ],
    'defaults' => [
        'software' => 'Dixlase',
        'author' => 'MyNameOrCompany',
        'website' => 'https://companyname.com',
    ],
];
