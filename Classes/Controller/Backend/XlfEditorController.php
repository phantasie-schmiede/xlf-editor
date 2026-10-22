<?php

declare(strict_types=1);

/*
 * This file is part of the PSBits XLF-Editor.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\XlfEditor\Controller\Backend;

use PSBits\Foundation\Attribute\ModuleAction;
use PSBits\Foundation\Controller\Backend\AbstractModuleController;
use PSBits\Foundation\Utility\Xml\XmlUtility;
use PSBits\XlfEditor\Service\XlfService;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException;

/**
 * Class XlfEditorController
 *
 * @package PSBits\XlfEditor\Controller\Backend
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

    #[ModuleAction]
    public function indexAction(): ResponseInterface
    {
        $translations = $this->xlfService->getTranslations();
        $this->view->assign('translations', $translations);

        return $this->htmlResponse();
    }

    /**
     * @param string                               $filePath
     * @param array<string, array<string, string>> $labels
     *
     * @throws NoSuchCacheException
     * @throws RuntimeException
     */
    #[ModuleAction]
    public function saveChangesAction(string $filePath, array $labels): ResponseInterface
    {
        foreach (array_keys($labels) as $languageKey) {
            $fullPath = XlfService::DEFAULT_LANGUAGE_KEY === $languageKey
                ? $filePath
                : pathinfo($filePath, PATHINFO_DIRNAME) . '/' . $languageKey . '.' . pathinfo(
                    $filePath,
                    PATHINFO_BASENAME
                );

            if (!is_file($fullPath)) {
                continue;
            }

            $xmlData = XmlUtility::convertFromXml((string) file_get_contents($fullPath));
            $xmlData = $this->xlfService->updateTransUnitLabels($xmlData, $labels, (string) $languageKey);

            $content = XmlUtility::convertToXml($xmlData, $this->xlfService->getXmlHeader($fullPath));

            if (false === file_put_contents($fullPath, $content)) {
                throw new RuntimeException(sprintf('Could not write the language file "%s".', $fullPath));
            }
        }

        // Clear the l10n-cache. Otherwise, the changes won't be displayed in the overview.
        $this->cacheManager->getCache('l10n')
            ->flush();

        return $this->redirect('index');
    }
}
