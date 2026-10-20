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

namespace PSBits\XlfEditor\Controller\Backend;

use JsonException;
use PSBits\Foundation\Attribute\ModuleAction;
use PSBits\Foundation\Controller\Backend\AbstractModuleController;
use PSBits\Foundation\Exceptions\ImplementationException;
use PSBits\Foundation\Utility\ArrayUtility;
use PSBits\Foundation\Utility\Xml\XmlUtility;
use PSBits\XlfEditor\Service\XlfService;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use ReflectionException;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException;

/**
 * Class XlfEditorController
 *
 * @package PSBits\XlfEditor\Controller\Backend
 * @ModuleConfig(mainModuleName="tools")
 */
class XlfEditorController extends AbstractModuleController
{
    public function __construct(
        protected readonly CacheManager $cacheManager,
        ModuleTemplateFactory           $moduleTemplateFactory,
        protected readonly XlfService   $xlfService,
    ) {
        parent::__construct($moduleTemplateFactory);
    }

    /**
     * @return ResponseInterface
     * @throws JsonException
     * @throws ImplementationException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    #[ModuleAction]
    public function indexAction(): ResponseInterface
    {
        $editableTranslations = $this->xlfService->getEditableTranslations();
        $this->view->assign('translations', $editableTranslations);

        return $this->htmlResponse();
    }

    /**
     * @param string $filePath
     * @param array  $labels
     *
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NoSuchCacheException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    #[ModuleAction]
    public function saveChangesAction(string $filePath, array $labels): void
    {
        foreach ($labels as $languageKey => $localLangIds) {
            $fullPath = $filePath;

            if (XlfService::DEFAULT_LANGUAGE_KEY !== $languageKey) {
                $fullPath = pathinfo($filePath, PATHINFO_DIRNAME) . '/' . $languageKey . '.' . pathinfo(
                    $filePath,
                    PATHINFO_BASENAME
                );
            }

            $xmlDeclaration = fgets(fopen($fullPath, 'rb'));
            $xmlData        = XmlUtility::convertFromXml(file_get_contents($fullPath));

            foreach ($localLangIds as $localLangId => $localLangValue) {
                // index looks like xliff.file.body.trans-unit.X.@attributes.id, where X is the position among all translations in that file
                $index                                                                        = ArrayUtility::inArrayRecursive($xmlData, $localLangId, true);
                $arrayPath                                                                    = explode('.', $index);
                $transUnitPosition                                                            = $arrayPath[4];
                $xmlData['xliff']['file']['body']['trans-unit'][$transUnitPosition]['source'] = $labels[XlfService::DEFAULT_LANGUAGE_KEY][$localLangId];

                if (XlfService::DEFAULT_LANGUAGE_KEY !== $languageKey) {
                    $xmlData['xliff']['file']['body']['trans-unit'][$transUnitPosition]['target'] = $localLangValue;
                }
            }

            file_put_contents($fullPath, $xmlDeclaration . XmlUtility::convertToXml($xmlData));
        }

        // Clear the l10n-cache. Otherwise, the changes won't be displayed in the overview.
        $this->cacheManager->getCache('l10n')
            ->flush();
        $this->redirect('index');
    }
}
