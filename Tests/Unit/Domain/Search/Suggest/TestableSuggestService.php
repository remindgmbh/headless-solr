<?php

declare(strict_types=1);

namespace Remind\HeadlessSolr\Tests\Unit\Domain\Search\Suggest;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Result\SearchResult;
use Remind\HeadlessSolr\Domain\Search\Suggest\SuggestService;

final class TestableSuggestService extends SuggestService
{
    public function __construct()
    {
    }

    /**
     * @param array<mixed> $additionalTopResultsFields
     * @return array<mixed>
     */
    public function getDocumentAsArrayProxy(SearchResult $document, array $additionalTopResultsFields = []): array
    {
        return $this->getDocumentAsArray($document, $additionalTopResultsFields);
    }
}
