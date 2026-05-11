<?php

declare(strict_types=1);

namespace Remind\HeadlessSolr\FrontendEnvironment;

use ApacheSolrForTypo3\Solr\FrontendEnvironment\Tsfe as SolrTsfe;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspectFactory;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Syncs singleton Context language for CONTENT cObjects during Solr indexing.
 *
 * Solr sets language only on TSFE's cloned Context, but ContentObjectRenderer
 * uses the singleton Context. This ensures CONTENT cObjects see correct language.
 */
class Tsfe extends SolrTsfe
{
    public function getTsfeByPageIdAndLanguageId(
        int $pageId,
        int $language = 0,
        ?int $rootPageId = null,
    ): ?TypoScriptFrontendController {
        $tsfe = parent::getTsfeByPageIdAndLanguageId($pageId, $language, $rootPageId);

        if ($tsfe !== null) {
            $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId($pageId);
            $siteLanguage = $site->getLanguageById($language);
            $languageAspect = LanguageAspectFactory::createFromSiteLanguage($siteLanguage);
            GeneralUtility::makeInstance(Context::class)->setAspect('language', $languageAspect);
        }

        return $tsfe;
    }
}
