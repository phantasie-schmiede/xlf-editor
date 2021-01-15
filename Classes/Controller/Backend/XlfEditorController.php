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

use JsonException;
use PSB\PsbFoundation\Controller\Backend\AbstractModuleController;
use PSB\PsbFoundation\Service\DocComment\Annotations\ModuleAction;
use PSB\PsbFoundation\Service\DocComment\Annotations\ModuleConfig;
use PSB\PsbFoundation\Utility\ArrayUtility;
use PSB\PsbFoundation\Utility\Xml\XmlUtility;
use PSB\PsbXlfEditor\Utility\XlfUtility;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException;
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;
use TYPO3\CMS\Extbase\Object\Exception;

/**
 * Class XlfEditorController
 *
 * @package PSB\PsbXlfEditor\Controller\Backend
 * @ModuleConfig(mainModuleName=tools)
 */
class XlfEditorController extends AbstractModuleController
{
    /**
     * @ModuleAction()
     * @throws Exception
     */
    public function indexAction(): void
    {
        $editableTranslations = XlfUtility::getEditableTranslations();
        $this->view->assign('translations', $editableTranslations);
    }

    /**
     * @param string $filePath
     * @param array  $labels
     *
     * @ModuleAction(doNotRender=true)
     * @throws Exception
     * @throws JsonException
     * @throws NoSuchCacheException
     * @throws StopActionException
     */
    public function saveChangesAction(string $filePath, array $labels): void
    {
        foreach ($labels as $languageKey => $localLangIds) {
            $fullPath = $filePath;

            if (XlfUtility::DEFAULT_LANGUAGE_KEY !== $languageKey) {
                $fullPath = pathinfo($filePath, PATHINFO_DIRNAME) . '/' . $languageKey . '.' . pathinfo($filePath,
                        PATHINFO_BASENAME);
            }

            $xmlDeclaration = fgets(fopen($fullPath, 'rb'));
            $xmlData = XmlUtility::convertFromXml(file_get_contents($fullPath));

            foreach ($localLangIds as $localLangId => $localLangValue) {
                // index looks like xliff.file.body.trans-unit.X.@attributes.id, where X is the position among all translations in that file
                $index = ArrayUtility::inArrayRecursive($xmlData, $localLangId, true);
                $arrayPath = explode('.', $index);
                $transUnitPosition = $arrayPath[4];
                $xmlData['xliff']['file']['body']['trans-unit'][$transUnitPosition]['source'] = $labels[XlfUtility::DEFAULT_LANGUAGE_KEY][$localLangId];

                if (XlfUtility::DEFAULT_LANGUAGE_KEY !== $languageKey) {
                    $xmlData['xliff']['file']['body']['trans-unit'][$transUnitPosition]['target'] = $localLangValue;
                }
            }

            file_put_contents($fullPath, $xmlDeclaration . XmlUtility::convertToXml($xmlData));
        }

        // Clear the l10n-cache. Otherwise the changes won't be displayed in the overview.
        $this->get(CacheManager::class)->getCache('l10n')->flush();
        $this->redirect('index');
    }
}
