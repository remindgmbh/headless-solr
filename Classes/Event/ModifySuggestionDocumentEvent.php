<?php

declare(strict_types=1);

namespace Remind\HeadlessSolr\Event;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Result\SearchResult;

class ModifySuggestionDocumentEvent
{
    /**
     * @param mixed[] $document
     */
    public function __construct(
        private array $document,
        private readonly SearchResult $searchResult,
    ) {
    }

    public function getSearchResult(): SearchResult
    {
        return $this->searchResult;
    }

    /**
     * @return mixed[]
     */
    public function getDocument(): array
    {
        return $this->document;
    }

    /**
     * @param mixed[] $document
     */
    public function setDocument(array $document): self
    {
        $this->document = $document;

        return $this;
    }
}
