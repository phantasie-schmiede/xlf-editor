<?php

$EM_CONF[$_EXTKEY] = [
    'author'           => 'Daniel Ablass',
    'author_email'     => 'dn@phantasie-schmiede.de',
    'category'         => 'module',
    'clearCacheOnLoad' => false,
    'constraints'      => [
        'conflicts' => [],
        'depends'   => [
            'foundation' => '3.0',
            'php'        => '8.3',
            'typo3'      => '13.4.0-13.4.99',
        ],
        'suggests'  => [],
    ],
    'description'      => 'Backend module which allows to edit XLF files',
    'state'            => 'alpha',
    'title'            => 'PSBits | XLF-editor',
    'version'          => '0.1.0',
];
