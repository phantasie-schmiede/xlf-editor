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
            'psb_foundation' => '',
            'typo3'          => '10.1.0-10.4.99',
        ],
        'suggests'  => [
        ],
    ],
    'createDirs'       => '',
    'description'      => 'Backend module which allows to edit XLF files',
    'state'            => 'alpha',
    'title'            => 'PSbits | XLF-editor',
    'uploadfolder'     => false,
    'version'          => '0.0.0',
];
