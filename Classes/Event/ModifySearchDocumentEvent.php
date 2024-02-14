<?php

declare(strict_types=1);

namespace Remind\HeadlessSolr\Event;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Result\SearchResult;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;

class ModifySearchDocumentEvent
{
    public function __construct(
        private array $document,
        private readonly SearchResult $searchResult,
        private readonly RenderingContext $renderingContext,
    ) {
    }

    public function getSearchResult(): SearchResult
    {
        return $this->searchResult;
    }

    public function getDocument(): array
    {
        return $this->document;
    }

    public function setDocument(array $document): self
    {
        $this->document = $document;

        return $this;
    }

    public function getRenderingContext(): RenderingContext
    {
        return $this->renderingContext;
    }
}
