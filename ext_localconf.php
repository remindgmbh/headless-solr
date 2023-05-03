<?php

declare(strict_types=1);

use ApacheSolrForTypo3\Solr\Controller\SearchController;
use Remind\HeadlessSolr\Controller\SearchController as HeadlessSearchController;

defined('TYPO3') || die('Access denied.');

(function () {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][SearchController::class] = [
        'className' => HeadlessSearchController::class,
    ];
})();
