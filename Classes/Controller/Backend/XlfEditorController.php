<?php
declare(strict_types=1);
namespace PSB\PsbXlfEditor\Controller\Backend;

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

use PSB\PsbFoundation\Controller\Backend\AbstractModuleController;
use PSB\PsbFoundation\Utility\ArrayUtility;
use PSB\PsbFoundation\Utility\XmlUtility;
use PSB\PsbXlfEditor\Utility\XlfUtility;
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;
use TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException;

/**
 * Class XlfEditorController
 * @package                  PSB\PsbXlfEditor\Controller\Backend
 * @PSB\PsbFoundation\Module mainModuleName=tools
 */
class XlfEditorController extends AbstractModuleController
{
    public function indexAction(): void
    {
        $editableTranslations = XlfUtility::getEditableTranslations();
        $this->view->assign('translations', $editableTranslations);
    }

    /**
     * @param string $filePath
     * @param string $locallangId
     * @param array  $translations
     * @PSB\PsbFoundation\Module\Action \PSB\PsbFoundation\Service\DocComment\ValueParsers\ModuleActionParser::FLAGS[DO_NOT_RENDER]
     *
     * @throws StopActionException
     * @throws UnsupportedRequestTypeException
     */
    public function saveChangesAction(string $filePath, string $locallangId, array $translations): void
    {
        $languageKeys = array_keys($translations);

        foreach ($languageKeys as $languageKey) {
            $fullPath = $filePath;

            if (XlfUtility::DEFAULT_LANGUAGE_KEY !== $languageKey) {
                $fullPath = pathinfo($filePath, PATHINFO_DIRNAME) . '/' . $languageKey . '.' . pathinfo($filePath,
                        PATHINFO_BASENAME);
            }

            $xmlDeclaration = fgets(fopen($fullPath, 'rb'));
            $xmlData = XmlUtility::convertXmlToArray(file_get_contents($fullPath));

            // index looks like xliff.file.body.trans-unit.X.@attributes.id, where X is the position among all translations in that file
            $index = ArrayUtility::inArrayRecursive($xmlData, $locallangId, true);
            $arrayPath = explode('.', $index);
            $transUnitPosition = $arrayPath[4];
            $xmlData['xliff']['file']['body']['trans-unit'][$transUnitPosition]['source'] = $translations[XlfUtility::DEFAULT_LANGUAGE_KEY];

            if (XlfUtility::DEFAULT_LANGUAGE_KEY !== $languageKey) {
                $xmlData['xliff']['file']['body']['trans-unit'][$transUnitPosition]['target'] = $translations[$languageKey];
            }

            file_put_contents($fullPath, $xmlDeclaration . XmlUtility::convertArrayToXml($xmlData));
        }

        $this->redirect('index');
    }
}
