<?php

/* Servers configuration */
$cfg = [
    'Application' => [
        'AvailableLanguages' => [
            'en_US' => 'EN',
            'ro_RO' => 'RO',
        ],
        'DefaultLanguage'    => 'en_US',
        'Name'               => 'Normalize MySQL internal structures',
    ],
    'FileToStore' => [
        'Folder'    => __DIR__ . '/results/',
        'Separator' => '|',
    ],
    'PhpLogDir'   => pathinfo(ini_get('error_log'))['dirname'],
];
$i   = 0;

/* Server: MySQL @ localhost [1] */
$i++;
$cfg['Servers'][$i]['verbose']  = 'MySQL @ localhost';
$cfg['Servers'][$i]['host']     = 'localhost';
$cfg['Servers'][$i]['port']     = '3306';
$cfg['Servers'][$i]['user']     = 'root';
$cfg['Servers'][$i]['password'] = '';
$cfg['Servers'][$i]['database'] = 'mysql';
