<?php

declare(strict_types=1);

/*
 * This file is part of the PSBits XLF-Editor.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\XlfEditor\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use PSBits\Foundation\Utility\Xml\XmlUtility;
use PSBits\XlfEditor\Service\XlfService;

final class XlfServiceTest extends TestCase
{
    private XlfService $service;

    private array $temporaryFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new XlfService();
    }

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $temporaryFile) {
            if (file_exists($temporaryFile)) {
                unlink($temporaryFile);
            }
        }

        parent::tearDown();
    }

    public function testReturnsTheXmlHeaderOfTheFile(): void
    {
        $temporaryFile = $this->createTemporaryFile(
            '<?xml version="1.0" encoding="utf-8"?>
<xliff version="1.2"></xliff>
'
        );

        self::assertSame('<?xml version="1.0" encoding="utf-8"?>', $this->service->getXmlHeader($temporaryFile));
    }

    public function testReturnsTheDefaultXmlHeaderIfTheFileHasNone(): void
    {
        $temporaryFile = $this->createTemporaryFile(
            '<xliff version="1.2"></xliff>
'
        );

        self::assertSame(XmlUtility::XML_HEADER, $this->service->getXmlHeader($temporaryFile));
    }

    public function testReturnsTheDefaultXmlHeaderIfTheFileDoesNotExist(): void
    {
        self::assertSame(XmlUtility::XML_HEADER, $this->service->getXmlHeader('/nonexistent/file.xlf'));
    }

    public function testCollectsTheLabelsOfAllTransUnits(): void
    {
        $xmlData = XmlUtility::convertFromXml(self::buildLanguageFile());

        self::assertSame([
            'editable.one' => 'Editable one',
            'plain.one'    => 'Plain one',
        ], $this->service->getAllTransUnitLabels($xmlData));
    }

    public function testCollectsTheLabelsOfTransUnitsInsidePluralGroups(): void
    {
        $xmlData = XmlUtility::convertFromXml(self::buildPluralLanguageFile());

        self::assertSame([
            'value'                        => 'Value',
            'insertedCounterReadings[0]'   => 'One new counter reading has been added.',
            'insertedCounterReadings[1]'   => '{quantity} new counter readings have been added.',
        ], $this->service->getAllTransUnitLabels($xmlData));
    }

    public function testCollectsTheLabelsOfIndividuallyMarkedTransUnits(): void
    {
        $xmlData = XmlUtility::convertFromXml(
            '<?xml version="1.0" encoding="utf-8"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
    <file source-language="en" datatype="plaintext" original="locallang.xlf">
        <body>
            <trans-unit allow_language_file_editing="true" id="editable.one">
                <source>Editable one</source>
            </trans-unit>
            <trans-unit id="plain.one">
                <source>Plain one</source>
            </trans-unit>
        </body>
    </file>
</xliff>
'
        );

        self::assertSame(
            ['editable.one' => 'Editable one'],
            $this->service->getEditableTransUnitLabels($xmlData)
        );
    }

    public function testCollectsTheLabelsOfAnIndividuallyMarkedSingleTransUnit(): void
    {
        $xmlData = XmlUtility::convertFromXml(
            '<?xml version="1.0" encoding="utf-8"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
    <file source-language="en" datatype="plaintext" original="locallang.xlf">
        <body>
            <trans-unit allow_language_file_editing="true" id="only.one">
                <source>Only one</source>
            </trans-unit>
        </body>
    </file>
</xliff>
'
        );

        self::assertSame(['only.one' => 'Only one'], $this->service->getEditableTransUnitLabels($xmlData));
    }

    public function testPrefersTheTargetLabelOfIndividuallyMarkedTransUnits(): void
    {
        $xmlData = XmlUtility::convertFromXml(
            '<?xml version="1.0" encoding="utf-8"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
    <file source-language="en" datatype="plaintext" original="locallang.xlf">
        <body>
            <trans-unit allow_language_file_editing="true" id="editable.one">
                <source>Editable one</source>
                <target>Übersetztes eins</target>
            </trans-unit>
        </body>
    </file>
</xliff>
'
        );

        self::assertSame(
            ['editable.one' => 'Übersetztes eins'],
            $this->service->getEditableTransUnitLabels($xmlData)
        );
    }

    public function testReturnsNoLabelsIfNoTransUnitIsMarked(): void
    {
        $xmlData = XmlUtility::convertFromXml(self::buildLanguageFile());

        self::assertSame([], $this->service->getEditableTransUnitLabels($xmlData));
    }

    public function testUpdatesTheSourceLabelOfTheDefaultLanguage(): void
    {
        $xmlData = XmlUtility::convertFromXml(self::buildLanguageFile());

        $updatedXmlData = $this->service->updateTransUnitLabels($xmlData, [
            'default' => [
                'editable.one' => 'Updated one',
            ],
        ], 'default');

        $transUnits = $updatedXmlData['xliff']['file']['body']['trans-unit'];

        self::assertSame('Updated one', $transUnits[0]['source']);
        self::assertSame('Plain one', XmlUtility::getNodeValue($transUnits[1], 'source', false));
    }

    public function testUpdatesTheTargetLabelAndSyncsTheSourceLabelOfALanguageFile(): void
    {
        $xmlData = XmlUtility::convertFromXml(self::buildLanguageFile());

        $updatedXmlData = $this->service->updateTransUnitLabels($xmlData, [
            'default' => [
                'editable.one' => 'Updated one',
            ],
            'de' => [
                'editable.one' => 'Aktualisiertes eins',
            ],
        ], 'de');

        $transUnits = $updatedXmlData['xliff']['file']['body']['trans-unit'];

        self::assertSame('Aktualisiertes eins', $transUnits[0]['target']);
        self::assertSame('Updated one', $transUnits[0]['source']);
        self::assertSame('Plain one', XmlUtility::getNodeValue($transUnits[1], 'source', false));
    }

    public function testUpdatesOnlyTheTargetLabelIfNoDefaultLabelWasSubmitted(): void
    {
        $xmlData = XmlUtility::convertFromXml(self::buildLanguageFile());

        $updatedXmlData = $this->service->updateTransUnitLabels($xmlData, [
            'de' => [
                'editable.one' => 'Aktualisiertes eins',
            ],
        ], 'de');

        $transUnits = $updatedXmlData['xliff']['file']['body']['trans-unit'];

        self::assertSame('Aktualisiertes eins', $transUnits[0]['target']);
        self::assertSame('Editable one', XmlUtility::getNodeValue($transUnits[0], 'source', false));
    }

    public function testUpdatesTransUnitsInsidePluralGroups(): void
    {
        $xmlData = XmlUtility::convertFromXml(self::buildPluralLanguageFile());

        $updatedXmlData = $this->service->updateTransUnitLabels($xmlData, [
            'default' => [
                'insertedCounterReadings[1]' => '{quantity} new counter readings were added.',
            ],
        ], 'default');

        $transUnits = $updatedXmlData['xliff']['file']['body']['group'][0]['trans-unit'];

        self::assertSame('{quantity} new counter readings were added.', $transUnits[1]['source']);
        self::assertSame('One new counter reading has been added.', XmlUtility::getNodeValue($transUnits[0], 'source', false));
    }

    public function testUpdatesASingleTransUnit(): void
    {
        $xmlData = XmlUtility::convertFromXml(
            '<?xml version="1.0" encoding="utf-8"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
    <file source-language="en" datatype="plaintext" original="locallang.xlf">
        <body>
            <trans-unit id="only.one">
                <source>Only one</source>
            </trans-unit>
        </body>
    </file>
</xliff>
'
        );

        $updatedXmlData = $this->service->updateTransUnitLabels($xmlData, [
            'default' => [
                'only.one' => 'Updated only one',
            ],
        ], 'default');

        $transUnit = $updatedXmlData['xliff']['file']['body']['trans-unit'][0];

        self::assertSame('only.one', $transUnit[XmlUtility::SPECIAL_ARRAY_KEYS['ATTRIBUTES']]['id']);
        self::assertSame('Updated only one', $transUnit['source']);
    }

    public function testLeavesUnknownTransUnitsUntouched(): void
    {
        $xmlData = XmlUtility::convertFromXml(self::buildLanguageFile());

        $updatedXmlData = $this->service->updateTransUnitLabels($xmlData, [
            'default' => [
                'unknown.id' => 'Unknown label',
            ],
        ], 'default');

        self::assertSame($xmlData, $updatedXmlData);
    }

    private static function buildLanguageFile(): string
    {
        return <<<XML
        <?xml version="1.0" encoding="utf-8"?>
        <xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
            <file source-language="en" datatype="plaintext" original="locallang.xlf">
                <body>
                    <trans-unit id="editable.one">
                        <source>Editable one</source>
                    </trans-unit>
                    <trans-unit id="plain.one">
                        <source>Plain one</source>
                    </trans-unit>
                </body>
            </file>
        </xliff>
        XML;
    }

    private static function buildPluralLanguageFile(): string
    {
        return <<<XML
        <?xml version="1.0" encoding="utf-8"?>
        <xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
            <file source-language="en" datatype="plaintext" original="bulkInsertForm.xlf">
                <body>
                    <group restype="x-gettext-plurals">
                        <trans-unit id="insertedCounterReadings[0]">
                            <source>One new counter reading has been added.</source>
                        </trans-unit>
                        <trans-unit id="insertedCounterReadings[1]">
                            <source>{quantity} new counter readings have been added.</source>
                        </trans-unit>
                    </group>
                    <trans-unit id="value">
                        <source>Value</source>
                    </trans-unit>
                </body>
            </file>
        </xliff>
        XML;
    }

    private function createTemporaryFile(string $content): string
    {
        $temporaryFile = tempnam(sys_get_temp_dir(), 'xlf-editor-test-');
        $this->temporaryFiles[] = $temporaryFile;
        file_put_contents($temporaryFile, $content);

        return $temporaryFile;
    }
}
