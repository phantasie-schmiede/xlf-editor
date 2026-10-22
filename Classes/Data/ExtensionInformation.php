<?php

declare(strict_types=1);

/*
 * This file is part of the PSBits XLF-Editor.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\XlfEditor\Data;

use PSBits\Foundation\Data\AbstractExtensionInformation;
use PSBits\Foundation\Data\ModuleConfiguration;
use PSBits\XlfEditor\Controller\Backend\XlfEditorController;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ExtensionInformation
 *
 * @package PSBits\XlfEditor\Data
 */
class ExtensionInformation extends AbstractExtensionInformation
{
    public function __construct()
    {
        parent::__construct();

        $this->addModule(
            GeneralUtility::makeInstance(
                ModuleConfiguration::class,
                controllers : [XlfEditorController::class],
                key         : $this->buildModuleKeyPrefix() . 'editor',
                parentModule: 'foundation_main',
                position    : [
                    'after' => 'foundation_analyzelocallang',
                ]
            )
        );
    }
}
