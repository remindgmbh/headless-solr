<?php

defined('TYPO3') || die;

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

ExtensionManagementUtility::addTCAcolumns(
    'pages',
    [
        'tx_headless_solr_image' => [
            'label' => 'LLL:EXT:rmnd_headless_solr/Resources/Private/Language/locallang.xlf:pages.tx_headless_solr_image',
            'config' => [
                'type' => 'file',
                'allowed' => 'common-image-types',
                'maxitems' => 1,
            ],
        ],
    ]
);

// Replace no_search and no_search_sub_entries fields with empty palette to remove, they will be added to a new palette below
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
    '--palette--;Search;search',
);
