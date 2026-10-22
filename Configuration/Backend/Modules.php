<?php

declare(strict_types=1);

use PSBits\Foundation\Service\Configuration\ModuleService;
use PSBits\XlfEditor\Data\ExtensionInformation;
use TYPO3\CMS\Core\Utility\GeneralUtility;

return GeneralUtility::makeInstance(ModuleService::class)
    ->buildModuleConfiguration(GeneralUtility::makeInstance(ExtensionInformation::class));
