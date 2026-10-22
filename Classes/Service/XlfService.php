<?php

declare(strict_types=1);

/*
 * This file is part of the PSBits XLF-Editor.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\XlfEditor\Service;

use Exception;
use PSBits\Foundation\Utility\Xml\XmlUtility;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function is_array;

/**
 * Class XlfService
 *
 * @package PSBits\XlfEditor\Service
 */
class XlfService
{
    /**
     * Language files are only editable in the backend module when they carry this attribute with the value
     * "true", either on the <file> tag (all trans-units of the file are editable) or on a specific
     * <trans-unit> tag (only that trans-unit is editable):
     *
     * <?xml version="1.0" encoding="utf-8"?>
     * <xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
     *     <file allow_language_file_editing="true" datatype="plaintext" original="locallang.xlf"
     *     source-language="en">
     *         <body>
     *             <trans-unit id="example">
     *                 <source>Editable</source>
     *             </trans-unit>
     *         </body>
     *     </file>
     * </xliff>
     */
    public const string ALLOW_LANGUAGE_FILE_EDITING_ATTRIBUTE_NAME = 'allow_language_file_editing';
    public const string DEFAULT_LANGUAGE_KEY                       = 'default';
    public const string FILE_ENDING                                = 'xlf';

    /**
     * Collects the labels of all trans-units of the given language file.
     *
     * @param array<string, mixed> $xmlData
     *
     * @return array<string, string> localLangId => label
     */
    public function getAllTransUnitLabels(array $xmlData): array
    {
        $labels = [];

        foreach ($this->collectTransUnits($xmlData) as $transUnit) {
            $localLangId = $transUnit[XmlUtility::SPECIAL_ARRAY_KEYS['ATTRIBUTES']]['id'] ?? null;

            if (is_string($localLangId)) {
                $labels[$localLangId] = $this->getTransUnitLabel($transUnit);
            }
        }

        return $labels;
    }

    /**
     * @return array<string, array<string, array<string, array<string, string>>>>
     */
    public function getEditableTranslations(): array
    {
        $editableTranslations = [];
        $loadedExtensions     = ExtensionManagementUtility::getLoadedExtensionListArray();

        foreach ($loadedExtensions as $extensionKey) {
            $languageFileDirectory = ExtensionManagementUtility::extPath($extensionKey) . 'Resources/Private/Language/';

            if (!is_dir($languageFileDirectory)) {
                continue;
            }

            $languageFiles = GeneralUtility::getAllFilesAndFoldersInPath([], $languageFileDirectory, self::FILE_ENDING);

            foreach ($languageFiles as $languageFile) {
                $filename = pathinfo($languageFile, PATHINFO_FILENAME);

                if (str_contains($filename, '.')) {
                    [$languageKey] = explode('.', $filename, 2);
                } else {
                    $languageKey = self::DEFAULT_LANGUAGE_KEY;
                }

                $arrayKey = pathinfo($languageFile, PATHINFO_DIRNAME) . '/' . $filename . '.' . self::FILE_ENDING;

                try {
                    $xmlData           = XmlUtility::convertFromXml((string) file_get_contents($languageFile));
                    $fileTagAttributes = $xmlData['xliff']['file'][XmlUtility::SPECIAL_ARRAY_KEYS['ATTRIBUTES']] ?? [];

                    if (true === ($fileTagAttributes[self::ALLOW_LANGUAGE_FILE_EDITING_ATTRIBUTE_NAME] ?? false)) {
                        $labels = $this->getAllTransUnitLabels($xmlData);
                    } else {
                        $labels = $this->getEditableTransUnitLabels($xmlData);
                    }

                    foreach ($labels as $localLangId => $label) {
                        $editableTranslations[$extensionKey][$arrayKey][$localLangId][$languageKey] = $label;
                    }
                } catch (Exception) {
                    // Skip language files that cannot be read or parsed.
                }
            }
        }

        return $editableTranslations;
    }

    /**
     * Collects the labels of those trans-units that are individually marked as editable via the
     * allow_language_file_editing attribute on their <trans-unit> tag.
     *
     * @param array<string, mixed> $xmlData
     *
     * @return array<string, string> localLangId => label
     */
    public function getEditableTransUnitLabels(array $xmlData): array
    {
        $labels = [];

        foreach ($this->collectTransUnits($xmlData) as $transUnit) {
            $transUnitTagAttributes = $transUnit[XmlUtility::SPECIAL_ARRAY_KEYS['ATTRIBUTES']] ?? [];

            if (true !== ($transUnitTagAttributes[self::ALLOW_LANGUAGE_FILE_EDITING_ATTRIBUTE_NAME] ?? false)) {
                continue;
            }

            $labels[$transUnitTagAttributes['id']] = $this->getTransUnitLabel($transUnit);
        }

        return $labels;
    }

    /**
     * Returns the XML declaration of the given file, or the default declaration of the XmlUtility if the
     * file does not have one, so that re-saved files keep their header.
     */
    public function getXmlHeader(string $filePath): string
    {
        $handle = @fopen($filePath, 'rb');

        if (false === $handle) {
            return XmlUtility::XML_HEADER;
        }

        $firstLine = (string) fgets($handle);
        fclose($handle);

        return str_starts_with(ltrim($firstLine), '<?xml') ? rtrim($firstLine) : XmlUtility::XML_HEADER;
    }

    /**
     * Updates the source and target labels of the trans-units that are present in the given labels.
     *
     * @param array<string, mixed>                 $xmlData
     * @param array<string, array<string, string>> $labels
     * @param string                               $languageKey
     *
     * @return array<string, mixed>
     */
    public function updateTransUnitLabels(array $xmlData, array $labels, string $languageKey): array
    {
        $body = $xmlData['xliff']['file']['body'] ?? null;

        if (!is_array($body)) {
            return $xmlData;
        }

        if (isset($body['trans-unit'])) {
            $body['trans-unit'] = $this->updateTransUnitLabelsInElements($body['trans-unit'], $labels, $languageKey);
        }

        if (isset($body['group'])) {
            $body['group'] = $this->updateGroups($body['group'], $labels, $languageKey);
        }

        $xmlData['xliff']['file']['body'] = $body;

        return $xmlData;
    }

    /**
     * @param array<string, mixed> $xmlData
     *
     * @return list<array<string, mixed>>
     */
    private function collectTransUnits(array $xmlData): array
    {
        $transUnits = [];

        foreach ($this->normalizeElements($xmlData['xliff']['file']['body']['trans-unit'] ?? []) as $transUnit) {
            $transUnits[] = $transUnit;
        }

        foreach ($this->normalizeElements($xmlData['xliff']['file']['body']['group'] ?? []) as $group) {
            foreach ($this->normalizeElements($group['trans-unit'] ?? []) as $transUnit) {
                $transUnits[] = $transUnit;
            }
        }

        return $transUnits;
    }

    /**
     * @param array<string, mixed>|list<array<string, mixed>> $elements
     *
     * @return list<array<string, mixed>>
     */
    private function normalizeElements(array $elements): array
    {
        if ([] === $elements || !ArrayUtility::isAssociative($elements)) {
            return $elements;
        }

        // A single element is stored as a single array instead of a list.
        return [0 => $elements];
    }

    /**
     * @param array<string, mixed> $transUnit
     */
    private function getTransUnitLabel(array $transUnit): string
    {
        return (string) (XmlUtility::getNodeValue($transUnit, 'target', false)
            ?? XmlUtility::getNodeValue($transUnit, 'source', false)
            ?? '');
    }

    /**
     * @param array<string, mixed>|list<array<string, mixed>> $transUnits
     * @param array<string, array<string, string>>            $labels
     * @param string                                          $languageKey
     *
     * @return list<array<string, mixed>>
     */
    private function updateTransUnitLabelsInElements(array $transUnits, array $labels, string $languageKey): array
    {
        $elements = $this->normalizeElements($transUnits);

        foreach ($elements as $position => $transUnit) {
            $elements[$position] = $this->updateTransUnitLabelsInTransUnit($transUnit, $labels, $languageKey);
        }

        return $elements;
    }

    /**
     * @param array<string, mixed>|list<array<string, mixed>> $groups
     * @param array<string, array<string, string>>            $labels
     * @param string                                          $languageKey
     *
     * @return list<array<string, mixed>>
     */
    private function updateGroups(array $groups, array $labels, string $languageKey): array
    {
        $normalizedGroups = $this->normalizeElements($groups);

        foreach ($normalizedGroups as $position => $group) {
            if (isset($group['trans-unit'])) {
                $normalizedGroups[$position]['trans-unit'] = $this->updateTransUnitLabelsInElements(
                    $group['trans-unit'],
                    $labels,
                    $languageKey
                );
            }
        }

        return $normalizedGroups;
    }

    /**
     * @param array<string, mixed>                 $transUnit
     * @param array<string, array<string, string>> $labels
     * @param string                               $languageKey
     *
     * @return array<string, mixed>
     */
    private function updateTransUnitLabelsInTransUnit(array $transUnit, array $labels, string $languageKey): array
    {
        $localLangId = $transUnit[XmlUtility::SPECIAL_ARRAY_KEYS['ATTRIBUTES']]['id'] ?? null;

        if (!is_string($localLangId) || !isset($labels[$languageKey][$localLangId])) {
            return $transUnit;
        }

        if (self::DEFAULT_LANGUAGE_KEY === $languageKey) {
            $transUnit['source'] = $labels[$languageKey][$localLangId];
        } else {
            $transUnit['target'] = $labels[$languageKey][$localLangId];

            if (isset($labels[self::DEFAULT_LANGUAGE_KEY][$localLangId])) {
                $transUnit['source'] = $labels[self::DEFAULT_LANGUAGE_KEY][$localLangId];
            }
        }

        return $transUnit;
    }
}
