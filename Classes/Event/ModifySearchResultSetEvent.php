<?php

declare(strict_types=1);

namespace Remind\HeadlessSolr\Event;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\SearchResultSet;

class ModifySearchResultSetEvent
{
    /**
     * @param mixed[] $values
     */
    public function __construct(
        private readonly SearchResultSet $searchResultSet,
        private array $values,
    ) {
    }

    public function getSearchResultSet(): SearchResultSet
    {
        return $this->searchResultSet;
    }

    /**
     * @return mixed[]
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * @param mixed[] $values
     */
    public function setValues(array $values): self
    {
        $this->values = $values;

        return $this;
    }
}
