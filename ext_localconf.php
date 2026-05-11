<?php

declare(strict_types=1);

use ApacheSolrForTypo3\Solr\Controller\SearchController;
use ApacheSolrForTypo3\Solr\Domain\Search\Suggest\SuggestService;
use ApacheSolrForTypo3\Solr\FrontendEnvironment\Tsfe as SolrTsfe;
use Remind\HeadlessSolr\Controller\SearchController as HeadlessSearchController;
use Remind\HeadlessSolr\Domain\Search\Suggest\SuggestService as HeadlessSuggestService;
use Remind\HeadlessSolr\FrontendEnvironment\Tsfe as HeadlessTsfe;

defined('TYPO3') || die('Access denied.');

(function (): void {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][SearchController::class] = [
        'className' => HeadlessSearchController::class,
    ];

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][SuggestService::class] = [
        'className' => HeadlessSuggestService::class,
    ];

    // Fix Solr indexing language: sync singleton Context's language aspect
    // so that TYPO3's ContentObjectRenderer uses the correct language for
    // SQL restrictions and overlays during Solr indexing.
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][SolrTsfe::class] = [
        'className' => HeadlessTsfe::class,
    ];

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride']['EXT:solr/Resources/Private/Language/locallang.xlf'][] = 'EXT:rmnd_headless_solr/Resources/Private/Language/Overrides/locallang.xlf';
})();
