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
    public function testRegistersTheXlfEditorModule(): void
    {
        $extensionInformation = new ExtensionInformation();
        $mainModules          = $extensionInformation->getMainModules();
        $modules              = $extensionInformation->getModules();

        self::assertCount(1, $mainModules);
        self::assertSame('xlfeditor_main', $mainModules[0]->getKey());
        self::assertSame(['after' => 'tools', 'before' => 'system'], $mainModules[0]->getPosition());

        self::assertCount(1, $modules);
        self::assertSame('xlfeditor_editor', $modules[0]->getKey());
        self::assertSame('xlfeditor_main', $modules[0]->getParentModule());
        self::assertSame([XlfEditorController::class], $modules[0]->getControllers());
    }
}
