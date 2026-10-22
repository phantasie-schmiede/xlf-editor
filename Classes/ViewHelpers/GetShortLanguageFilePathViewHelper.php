<?php

declare(strict_types=1);

/*
 * This file is part of the PSBits XLF-Editor.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
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
