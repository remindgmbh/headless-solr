<?php

declare(strict_types=1);

use ApacheSolrForTypo3\Solr\Controller\SearchController;
use ApacheSolrForTypo3\Solr\Domain\Search\Suggest\SuggestService;
use Remind\HeadlessSolr\Controller\SearchController as HeadlessSearchController;
use Remind\HeadlessSolr\Domain\Search\Suggest\SuggestService as HeadlessSuggestService;

defined('TYPO3') || die('Access denied.');

(function (): void {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][SearchController::class] = [
        'className' => HeadlessSearchController::class,
    ];

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][SuggestService::class] = [
        'className' => HeadlessSuggestService::class,
    ];

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride']['EXT:solr/Resources/Private/Language/locallang.xlf'][] = 'EXT:rmnd_headless_solr/Resources/Private/Language/Overrides/locallang.xlf';
})();
