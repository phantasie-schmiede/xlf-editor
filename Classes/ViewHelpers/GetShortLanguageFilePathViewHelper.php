<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace PSBits\XlfEditor\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Class GetShortLanguageFilePathViewHelper
 *
 * @package PSBits\XlfEditor\ViewHelpers
 */
class GetShortLanguageFilePathViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('path', 'string', 'filepath of which the last part should be extracted', true);
    }

    public function render(): string
    {
        $searchString = '/Resources/Private/Language/';
        $offset       = mb_strpos($this->arguments['path'], $searchString);

        return mb_substr($this->arguments['path'], $offset + mb_strlen($searchString));
    }
}
