<?php

declare(strict_types=1);

namespace Remind\HeadlessSolr\Event\Listener;

use TYPO3\CMS\Core\Configuration\Event\AfterFlexFormDataStructureParsedEvent;
use TYPO3\CMS\Core\Utility\ArrayUtility;

class ExtendPluginFlexforms
{
    private const DATA_STRUCTURE_KEY_RESULTS = 'solr_pi_results,list';
    private const DATA_STRUCTURE_KEY_SEARCH = 'solr_pi_search,list';

    public function __invoke(AfterFlexFormDataStructureParsedEvent $event): void
    {
        $dataStructure = $event->getDataStructure();
        $identifier = $event->getIdentifier();

        if (
            $identifier['type'] === 'tca' &&
            $identifier['tableName'] === 'tt_content' &&
            $identifier['dataStructureKey'] === self::DATA_STRUCTURE_KEY_RESULTS
        ) {
            ArrayUtility::mergeRecursiveWithOverrule(
                $dataStructure,
                [
                    'sheets' => [
                        'sDEF' => [
                            'ROOT' => [
                                'el' => [
                                    'search.noResultsText' => [
                                        'config' => [
                                            'enableRichtext' => true,
                                            'type' => 'text',
                                        ],
                                        'label' => 'LLL:EXT:rmnd_headless_solr/Resources/Private/Language/locallang.xlf:solr_pi_results.no_results_text',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]
            );
        }

        if (
            $identifier['type'] === 'tca' &&
            $identifier['tableName'] === 'tt_content' &&
            $identifier['dataStructureKey'] === self::DATA_STRUCTURE_KEY_SEARCH
        ) {
            ArrayUtility::mergeRecursiveWithOverrule(
                $dataStructure,
                [
                    'sheets' => [
                        'sDEF' => [
                            'ROOT' => [
                                'el' => [
                                    'search.placeholderText' => [
                                        'config' => [
                                            'type' => 'input',
                                        ],
                                        'label' => 'LLL:EXT:rmnd_headless_solr/Resources/Private/Language/locallang.xlf:solr_pi_search.placeholder_text',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]
            );
        }

        $event->setDataStructure($dataStructure);
    }
}
