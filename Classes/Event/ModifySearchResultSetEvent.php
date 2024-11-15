<?php

declare(strict_types=1);

namespace Remind\HeadlessSolr\Event;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\SearchResultSet;

class ModifySearchResultSetEvent
{
    /**
     * @param SearchResultSet $searchResultSet
     * @param array $values
     */
    public function __construct(
        private readonly SearchResultSet $searchResultSet,
        private array $values,
    ) {
    }

    /**
     * @return SearchResultSet
     */
    public function getSearchResultSet(): SearchResultSet
    {
        return $this->searchResultSet;
    }

    /**
     * @return array
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * @param array $values
     * @return ModifySearchResultSetEvent
     */
    public function setValues(array $values): self
    {
        $this->values = $values;

        return $this;
    }
}
