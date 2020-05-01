<?php
declare(strict_types=1);
namespace PSB\PsbXlfEditor\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2020 Daniel Ablass <dn@phantasie-schmiede.de>, PSbits
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use PSB\PsbFoundation\Data\ExtensionInformationInterface;
use PSB\PsbFoundation\Traits\StaticInjectionTrait;
use PSB\PsbFoundation\Utility\ExtensionInformationUtility;
use PSB\PsbFoundation\Utility\XmlUtility;
use TYPO3\CMS\Core\Localization\LocalizationFactory;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class XlfUtility
 * @package PSB\PsbXlfEditor\Utility
 */
class XlfUtility
{
    use StaticInjectionTrait;

    public const DEFAULT_LANGUAGE_KEY = 'default';
    public const FILE_ENDING          = 'xlf';

    /**
     * @return array
     */
    public static function getEditableTranslations(): array
    {
        $allExtensionInformation = ExtensionInformationUtility::getExtensionInformation();
        $editableTranslations = [];
        $loadedExtensions = ExtensionManagementUtility::getLoadedExtensionListArray();

        foreach ($loadedExtensions as $extensionKey) {
            $languageFileDirectory = ExtensionInformationUtility::getLanguageFilePath($extensionKey);
            $languageFiles = [];
            $languageFiles = GeneralUtility::getAllFilesAndFoldersInPath($languageFiles, $languageFileDirectory, self::FILE_ENDING);

            foreach ($languageFiles as $languageFile) {
                $directoryName = pathinfo($languageFile, PATHINFO_DIRNAME);
                $filename = pathinfo($languageFile, PATHINFO_FILENAME);

                if (false !== mb_strpos($filename, '.')) {
                    [$languageKey, $filename] = explode('.', $filename);
                } else {
                    $languageKey = self::DEFAULT_LANGUAGE_KEY;
                }

                $arrayKey = $directoryName . '/' . $filename . '.' . self::FILE_ENDING;

                /**
                 * Collect all translations of current file if the extension allows general editing of its language
                 * files or if current file contains a special XML-attribute in its <file>-tag. Otherwise look at each
                 * translation separately and check if it may be edited.
                 * @see \PSB\PsbFoundation\Data\ExtensionInformationInterface
                 */
                if (isset($allExtensionInformation[$extensionKey]) && true === $allExtensionInformation[$extensionKey]::ALLOW_LANGUAGE_FILE_EDITING) {
                    self::addAllTranslations($editableTranslations[$extensionKey][$arrayKey], $languageFile,
                        $languageKey);
                } else {
                    $xmlData = XmlUtility::convertXmlToArray(file_get_contents($languageFile));
                    $fileTagAttributes = $xmlData['xliff']['file'][XmlUtility::SPECIAL_KEYS['ATTRIBUTES']];

                    if (isset($fileTagAttributes[ExtensionInformationInterface::ALLOW_LANGUAGE_FILE_EDITING_ATTRIBUTE_NAME])
                        && true === $fileTagAttributes[ExtensionInformationInterface::ALLOW_LANGUAGE_FILE_EDITING_ATTRIBUTE_NAME]
                    ) {
                        self::addAllTranslations($editableTranslations[$extensionKey][$arrayKey], $languageFile,
                            $languageKey);
                    } else {
                        foreach ($xmlData['xliff']['file']['body']['trans-unit'] as $transUnit) {
                            if (isset($transUnit[XmlUtility::SPECIAL_KEYS['ATTRIBUTES']])) {
                                $transUnitTagAttributes = $transUnit[XmlUtility::SPECIAL_KEYS['ATTRIBUTES']];

                                if (true === $transUnitTagAttributes[ExtensionInformationInterface::ALLOW_LANGUAGE_FILE_EDITING_ATTRIBUTE_NAME]) {
                                    $editableTranslations[$extensionKey][$arrayKey][$transUnitTagAttributes['id']][$languageKey] = $transUnit['target'] ?? $transUnit['source'];
                                }
                            }
                        }
                    }
                }
            }
        }

        return $editableTranslations;
    }

    /**
     * @param mixed  $translations
     * @param string $languageFile
     * @param string $languageKey
     */
    private static function addAllTranslations(
        &$translations,
        string $languageFile,
        string $languageKey
    ): void {
        $parsedData = self::get(LocalizationFactory::class)->getParsedData($languageFile, $languageKey);

        if (!is_array($translations)) {
            $translations = [];
        }

        $translations = array_merge_recursive($translations,
            self::rearrangeTranslationArray($parsedData, $languageKey));
    }

    /**
     * @param array  $translations
     * @param string $languageKey
     *
     * @return array
     */
    private static function rearrangeTranslationArray(array $translations, string $languageKey): array
    {
        $rearrangedTranslations = [];

        foreach ($translations[$languageKey] as $id => $translation) {
            $rearrangedTranslations[$id][$languageKey] = $translation[0]['target'];
        }

        return $rearrangedTranslations;
    }
}
