<?php

declare(strict_types=1);

/*
 * This file is part of the PSBits XLF-Editor.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\XlfEditor\Tests\Unit\Data;

use PHPUnit\Framework\TestCase;
use PSBits\XlfEditor\Controller\Backend\XlfEditorController;
use PSBits\XlfEditor\Data\ExtensionInformation;

final class ExtensionInformationTest extends TestCase
{
    public function testRegistersTheXlfEditorModuleBelowTheFoundationMainModule(): void
    {
        $extensionInformation = new ExtensionInformation();

        self::assertSame([], $extensionInformation->getMainModules());

        $modules = $extensionInformation->getModules();

        self::assertCount(1, $modules);
        self::assertSame('xlfeditor_editor', $modules[0]->getKey());
        self::assertSame('foundation_main', $modules[0]->getParentModule());
        self::assertSame(['after' => 'foundation_analyzelocallang'], $modules[0]->getPosition());
        self::assertSame([XlfEditorController::class], $modules[0]->getControllers());
    }
}
