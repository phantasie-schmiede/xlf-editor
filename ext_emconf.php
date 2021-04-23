<?php
declare(strict_types=1);

$EM_CONF['psb_xlf_editor'] = [
    'author'           => 'Daniel Ablass',
    'author_email'     => 'dn@phantasie-schmiede.de',
    'category'         => 'module',
    'clearCacheOnLoad' => false,
    'constraints'      => [
        'conflicts' => [
        ],
        'depends'   => [
            'psb_foundation' => '1.0.0',
        ],
        'suggests'  => [
        ],
    ],
    'description'      => 'Backend module which allows to edit XLF files',
    'state'            => 'alpha',
    'title'            => 'PSbits | XLF-editor',
    'version'          => '0.0.1',
];
