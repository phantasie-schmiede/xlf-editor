<?php
declare(strict_types=1);

$EM_CONF['xlf_editor'] = [
    'author'           => 'Daniel Ablass',
    'author_email'     => 'dn@phantasie-schmiede.de',
    'category'         => 'module',
    'clearCacheOnLoad' => false,
    'constraints'      => [
        'conflicts' => [
        ],
        'depends'   => [
            'foundation' => '3.0',
        ],
        'suggests'  => [
        ],
    ],
    'description'      => 'Backend module which allows to edit XLF files',
    'state'            => 'alpha',
    'title'            => 'PSBits | XLF-editor',
    'version'          => '0.0.1',
];
