<?php

declare(strict_types=1);

namespace Remind\HeadlessSolr\Event;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Result\SearchResult;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

class ModifySearchDocumentEvent
{
    public function __construct(
        private array $document,
        private readonly SearchResult $searchResult,
        private readonly RenderingContextInterface $renderingContext,
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

    public function getRenderingContext(): RenderingContextInterface
    {
        return $this->renderingContext;
    }
}
