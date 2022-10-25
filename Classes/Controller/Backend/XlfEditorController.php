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

namespace PSB\PsbXlfEditor\Controller\Backend;

use JsonException;
use PSB\PsbFoundation\Annotation\ModuleAction;
use PSB\PsbFoundation\Annotation\ModuleConfig;
use PSB\PsbFoundation\Controller\Backend\AbstractModuleController;
use PSB\PsbFoundation\Traits\PropertyInjection\CacheManagerTrait;
use PSB\PsbFoundation\Utility\ArrayUtility;
use PSB\PsbFoundation\Utility\Xml\XmlUtility;
use PSB\PsbXlfEditor\Service\XlfService;
use PSB\PsbXlfEditor\Traits\PropertyInjection\XlfServiceTrait;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException;
use TYPO3\CMS\Core\Package\Exception\UnknownPackageException;
use TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException;
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;

/**
 * Class XlfEditorController
 *
 * @package PSB\PsbXlfEditor\Controller\Backend
 * @ModuleConfig(mainModuleName="tools")
 */
class XlfEditorController extends AbstractModuleController
{
    use CacheManagerTrait;
    use XlfServiceTrait;

    /**
     * @ModuleAction()
     * @return ResponseInterface
     * @throws InvalidConfigurationTypeException
     * @throws JsonException
     * @throws UnknownPackageException
     */
    public function indexAction(): ResponseInterface
    {
        $editableTranslations = $this->xlfService->getEditableTranslations();
        $this->view->assign('translations', $editableTranslations);

        return $this->render();
    }

    /**
     * @param string $filePath
     * @param array  $labels
     *
     * @throws InvalidConfigurationTypeException
     * @throws JsonException
     * @throws NoSuchCacheException
     * @throws StopActionException
     * @ModuleAction()
     */
    public function saveChangesAction(string $filePath, array $labels): void
    {
        foreach ($labels as $languageKey => $localLangIds) {
            $fullPath = $filePath;

            if (XlfService::DEFAULT_LANGUAGE_KEY !== $languageKey) {
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
                $xmlData['xliff']['file']['body']['trans-unit'][$transUnitPosition]['source'] = $labels[XlfService::DEFAULT_LANGUAGE_KEY][$localLangId];

                if (XlfService::DEFAULT_LANGUAGE_KEY !== $languageKey) {
                    $xmlData['xliff']['file']['body']['trans-unit'][$transUnitPosition]['target'] = $localLangValue;
                }
            }

            file_put_contents($fullPath, $xmlDeclaration . XmlUtility::convertToXml($xmlData));
        }

        // Clear the l10n-cache. Otherwise, the changes won't be displayed in the overview.
        $this->cacheManager->getCache('l10n')->flush();
        $this->redirect('index');
    }
}
