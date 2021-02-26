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

namespace PSB\PsbXlfEditor\Service;

use JsonException;
use PSB\PsbFoundation\Data\ExtensionInformationInterface;
use PSB\PsbFoundation\Traits\Properties\ExtensionInformationServiceTrait;
use PSB\PsbFoundation\Utility\Xml\XmlUtility;
use PSB\PsbXlfEditor\Traits\Properties\LocalizationFactoryTrait;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\Exception;

/**
 * Class XlfService
 *
 * @package PSB\PsbXlfEditor\Service
 */
class XlfService
{
    use ExtensionInformationServiceTrait, LocalizationFactoryTrait;

    public const DEFAULT_LANGUAGE_KEY = 'default';
    public const FILE_ENDING          = 'xlf';

    /**
     * @param        $translations
     * @param string $languageFile
     * @param string $languageKey
     */
    private function addAllTranslations(
        &$translations,
        string $languageFile,
        string $languageKey
    ): void {
        $parsedData = $this->localizationFactory->getParsedData($languageFile, $languageKey);

        if (!is_array($translations)) {
            $translations = [];
        }

        $translations = array_merge_recursive($translations,
            $this->rearrangeTranslationArray($parsedData, $languageKey));
    }

    /**
     * @param array  $translations
     * @param string $languageKey
     *
     * @return array
     */
    private function rearrangeTranslationArray(array $translations, string $languageKey): array
    {
        $rearrangedTranslations = [];

        foreach ($translations[$languageKey] as $id => $translation) {
            $rearrangedTranslations[$id][$languageKey] = $translation[0]['target'];
        }

        return $rearrangedTranslations;
    }

    /**
     * @return array
     * @throws Exception
     * @throws JsonException
     */
    public function getEditableTranslations(): array
    {
        $allExtensionInformation = $this->extensionInformationService->getExtensionInformation();
        $editableTranslations = [];
        $loadedExtensions = ExtensionManagementUtility::getLoadedExtensionListArray();

        foreach ($loadedExtensions as $extensionKey) {
            $languageFileDirectory = $this->extensionInformationService->getLanguageFilePath($extensionKey);
            $languageFiles = [];
            $languageFiles = GeneralUtility::getAllFilesAndFoldersInPath($languageFiles, $languageFileDirectory,
                self::FILE_ENDING);

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
                 *
                 * @see \PSB\PsbFoundation\Data\ExtensionInformationInterface
                 */
                if (isset($allExtensionInformation[$extensionKey]) && defined(get_class($allExtensionInformation[$extensionKey]) . '::ALLOW_LANGUAGE_FILE_EDITING') && true === $allExtensionInformation[$extensionKey]::ALLOW_LANGUAGE_FILE_EDITING) {
                    $this->addAllTranslations($editableTranslations[$extensionKey][$arrayKey], $languageFile,
                        $languageKey);
                } else {
                    $xmlData = XmlUtility::convertFromXml(file_get_contents($languageFile));
                    $fileTagAttributes = $xmlData['xliff']['file'][XmlUtility::SPECIAL_KEYS['ATTRIBUTES']];

                    if (isset($fileTagAttributes[ExtensionInformationInterface::ALLOW_LANGUAGE_FILE_EDITING_ATTRIBUTE_NAME])
                        && true === $fileTagAttributes[ExtensionInformationInterface::ALLOW_LANGUAGE_FILE_EDITING_ATTRIBUTE_NAME]
                    ) {
                        $this->addAllTranslations($editableTranslations[$extensionKey][$arrayKey], $languageFile,
                            $languageKey);
                    } elseif (is_iterable($xmlData['xliff']['file']['body']['trans-unit'])) {
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
}
