<?php

declare(strict_types=1);

defined('TYPO3') || die;

use Remind\Headless\Utility\TcaUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

ExtensionManagementUtility::addTCAcolumns(
    'pages',
    [
        'tx_headless_solr_image' => [
            'config' => [
                'allowed' => 'common-image-types',
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
                'maxitems' => 1,
                'type' => 'file',
            ],
            'label' => 'LLL:EXT:rmnd_headless_solr/Resources/Private/Language/locallang.xlf:pages.tx_headless_solr_image',
        ],
     ]
);

/**
 * Replace no_search and no_search_sub_entries fields with empty palette to remove,
 * they will be added to a new palette below
 */
foreach (['no_search', 'no_search_sub_entries'] as $field) {
    ExtensionManagementUtility::addToAllTCAtypes(
        'pages',
        '--palette--;;',
        '',
        'replace:' . $field,
    );
}

ExtensionManagementUtility::addFieldsToPalette(
    'pages',
    'search',
    'tx_headless_solr_image,--linebreak--,no_search,--linebreak--,no_search_sub_entries',
);

ExtensionManagementUtility::addToAllTCAtypes(
    'pages',
    '--div--;LLL:EXT:rmnd_headless_solr/Resources/Private/Language/locallang.xlf:pages.search,--palette--;;search',
);

TcaUtility::addPageConfigFlexForm('FILE:EXT:rmnd_headless_solr/Configuration/FlexForms/Config.xml');
