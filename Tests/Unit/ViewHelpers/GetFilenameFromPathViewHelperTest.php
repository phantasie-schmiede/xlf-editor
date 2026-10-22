<?php

declare(strict_types=1);

/*
 * This file is part of the PSBits XLF-Editor.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\XlfEditor\Tests\Unit\ViewHelpers;

use PHPUnit\Framework\TestCase;
use PSBits\XlfEditor\ViewHelpers\GetFilenameFromPathViewHelper;

final class GetFilenameFromPathViewHelperTest extends TestCase
{
    public function testReturnsTheFileNameWithoutItsExtension(): void
    {
        $viewHelper = new GetFilenameFromPathViewHelper();
        $viewHelper->initializeArguments();
        $viewHelper->setArguments([
            'path' => '/var/www/html/vendor/typo3conf/ext/xlf_editor/Resources/Private/Language/locallang.xlf',
        ]);

        self::assertSame('locallang', $viewHelper->render());
    }
}
