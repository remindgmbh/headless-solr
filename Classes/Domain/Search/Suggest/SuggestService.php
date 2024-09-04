<?php

declare(strict_types=1);

namespace Remind\HeadlessSolr\Domain\Search\Suggest;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Result\SearchResult;
use ApacheSolrForTypo3\Solr\Domain\Search\Suggest\SuggestService as BaseSuggestService;
use Psr\EventDispatcher\EventDispatcherInterface;
use Remind\HeadlessSolr\Event\ModifySuggestionDocumentEvent;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class SuggestService extends BaseSuggestService
{
    /**
     * @param mixed[] $additionalTopResultsFields
     * @return mixed[]
     */
    protected function getDocumentAsArray(SearchResult $document, array $additionalTopResultsFields = []): array
    {
        $fields = parent::getDocumentAsArray($document, $additionalTopResultsFields);

        $eventDispatcher = GeneralUtility::makeInstance(EventDispatcherInterface::class);

        /** @var ModifySuggestionDocumentEvent $event */
        $event = $eventDispatcher->dispatch(new ModifySuggestionDocumentEvent($fields, $document));

        return $event->getDocument();
    }
}
