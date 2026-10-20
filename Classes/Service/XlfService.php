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

namespace PSBits\XlfEditor\Service;

use JsonException;
use PSBits\Foundation\Exceptions\ImplementationException;
use PSBits\Foundation\Service\ExtensionInformationService;
use PSBits\Foundation\Utility\Xml\XmlUtility;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use TYPO3\CMS\Core\Localization\LocalizationFactory;
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
     * If defined as true, language files of this extension will be editable in the Backend via the Language file
     * editor module. You can't override this constant if you implement this interface directly, but you can achieve
     * this when extending the AbstractExtensionInformation class (which you should definitely do). Another way to
     * allow the editing on a per file or even trans-unit basis is to include this attribute inside the specific tags
     * of your xlf-files: allow_language_file_editing="true"
     *
     * Example 1:
     * <?xml encoding="utf-8" standalone="yes" version="1.0" ?>
     * <xliff version="1.0">
     *     <file allow_language_file_editing="true" datatype="plaintext" date="2020-01-17T12:30:00+01:00"
     *     original="messages" product-name="foundation" source-language="en">
     *         <header />
     *         <body>
     *             <trans-unit id="example1">
     *                 <source>editable</source>
     *             </trans-unit>
     *             <trans-unit id="example2">
     *                 <source>editable</source>
     *             </trans-unit>
     *         </body>
     *     </file>
     * </xliff>
     *
     * Example 2:
     * <?xml encoding="utf-8" standalone="yes" version="1.0" ?>
     * <xliff version="1.0">
     *     <file datatype="plaintext" date="2020-01-17T12:30:00+01:00" original="messages"
     *     product-name="foundation" source-language="en">
     *         <header />
     *         <body>
     *             <trans-unit id="example1">
     *                 <source>not editable</source>
     *             </trans-unit>
     *             <trans-unit allow_language_file_editing="true" id="example2">
     *                 <source>editable</source>
     *             </trans-unit>
     *         </body>
     *     </file>
     * </xliff>
     */
    //    public const ALLOW_LANGUAGE_FILE_EDITING = false;
    public const string ALLOW_LANGUAGE_FILE_EDITING_ATTRIBUTE_NAME = 'allow_language_file_editing';
    public const string DEFAULT_LANGUAGE_KEY                       = 'default';
    public const string FILE_ENDING                                = 'xlf';

    public function __construct(
        protected readonly ExtensionInformationService $extensionInformationService,
        protected readonly LocalizationFactory         $localizationFactory,
    ) {
    }

    /**
     * @param array<string, array<string, string>>|null $translations
     * @param-out array<string, array<string, string>> $translations
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

        $newTranslations = $this->rearrangeTranslationArray($parsedData, $languageKey);

        foreach ($newTranslations as $id => $byLanguage) {
            foreach ($byLanguage as $language => $label) {
                $translations[$id][$language] = $label;
            }
        }
    }

    /**
     * @param array<string, array<string, array<int, array<string, string>>>> $translations
     * @param string                                                          $languageKey
     *
     * @return array<string, array<string, string>>
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
     * @return array<string, array<string, array<string, array<string, string>>>>
     * @throws ContainerExceptionInterface
     * @throws ImplementationException
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function getEditableTranslations(): array
    {
        $editableTranslations = [];
        $loadedExtensions     = ExtensionManagementUtility::getLoadedExtensionListArray();

        foreach ($loadedExtensions as $extensionKey) {
            try {
                $extensionInformation = $this->extensionInformationService->getExtensionInformation($extensionKey);
            } catch (ImplementationException) {
                // Extension without an ExtensionInformation class; attribute-based editing still applies
                $extensionInformation = null;
            }

            $languageFileDirectory = ExtensionManagementUtility::extPath($extensionKey) . 'Resources/Private/Language/';
            $languageFiles         = [];
            $languageFiles         = GeneralUtility::getAllFilesAndFoldersInPath(
                $languageFiles,
                $languageFileDirectory,
                self::FILE_ENDING
            );

            foreach ($languageFiles as $languageFile) {
                $directoryName = pathinfo($languageFile, PATHINFO_DIRNAME);
                $filename      = pathinfo($languageFile, PATHINFO_FILENAME);

                if (str_contains($filename, '.')) {
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
                 * @see \PSBits\Foundation\Data\ExtensionInformationInterface
                 */
                $extensionInformationClass = null !== $extensionInformation ? get_class($extensionInformation) : '';

                if ('' !== $extensionInformationClass && defined($extensionInformationClass . '::ALLOW_LANGUAGE_FILE_EDITING') && true === $extensionInformationClass::ALLOW_LANGUAGE_FILE_EDITING) {
                    $this->addAllTranslations(
                        $editableTranslations[$extensionKey][$arrayKey],
                        $languageFile,
                        $languageKey
                    );
                } else {
                    $xmlData           = XmlUtility::convertFromXml(file_get_contents($languageFile));
                    $fileTagAttributes = $xmlData['xliff']['file'][XmlUtility::SPECIAL_ARRAY_KEYS['ATTRIBUTES']];

                    if (isset($fileTagAttributes[self::ALLOW_LANGUAGE_FILE_EDITING_ATTRIBUTE_NAME])
                        && true === $fileTagAttributes[self::ALLOW_LANGUAGE_FILE_EDITING_ATTRIBUTE_NAME]
                    ) {
                        $this->addAllTranslations(
                            $editableTranslations[$extensionKey][$arrayKey],
                            $languageFile,
                            $languageKey
                        );
                    } elseif (is_iterable($xmlData['xliff']['file']['body']['trans-unit'])) {
                        foreach ($xmlData['xliff']['file']['body']['trans-unit'] as $transUnit) {
                            if (isset($transUnit[XmlUtility::SPECIAL_ARRAY_KEYS['ATTRIBUTES']])) {
                                $transUnitTagAttributes = $transUnit[XmlUtility::SPECIAL_ARRAY_KEYS['ATTRIBUTES']];

                                if (true === $transUnitTagAttributes[self::ALLOW_LANGUAGE_FILE_EDITING_ATTRIBUTE_NAME]) {
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
