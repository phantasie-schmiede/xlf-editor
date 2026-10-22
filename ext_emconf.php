<?php

$EM_CONF[$_EXTKEY] = [
    'author'           => 'Daniel Ablass',
    'author_email'     => 'dn@phantasie-schmiede.de',
    'category'         => 'module',
    'clearCacheOnLoad' => false,
    'constraints'      => [
        'conflicts' => [],
        'depends'   => [
            'foundation' => '4.1',
            'php'        => '8.3.0-8.5.99',
            'typo3'      => '13.4.0-14.99.99',
        ],
        'suggests'  => [],
    ],
    'description'      => 'Backend module which allows to edit XLF files. Developmental state, not intended for production environments.',
    'state'            => 'alpha',
    'title'            => 'PSBits | XLF-editor',
    'version'          => '0.1.0',
];
