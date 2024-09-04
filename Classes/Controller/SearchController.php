<?php

declare(strict_types=1);

namespace Remind\HeadlessSolr\Controller;

use ApacheSolrForTypo3\Solr\Controller\SearchController as BaseSearchController;
use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\OptionBased\AbstractOptionsFacet;
use ApacheSolrForTypo3\Solr\System\Configuration\TypoScriptConfiguration;
use ApacheSolrForTypo3\Solr\Util;
use ApacheSolrForTypo3\Solr\ViewHelpers\Document\HighlightResultViewHelper;
use ApacheSolrForTypo3\Solr\ViewHelpers\Uri\Facet\RemoveAllFacetsViewHelper;
use ApacheSolrForTypo3\Solr\ViewHelpers\Uri\Facet\SetFacetItemViewHelper;
use Psr\Http\Message\ResponseInterface;
use Remind\Headless\Service\JsonService;
use Remind\Headless\Utility\ConfigUtility;
use Remind\HeadlessSolr\Event\ModifySearchDocumentEvent;

class SearchController extends BaseSearchController
{
    private ?JsonService $jsonService = null;

    private ?TypoScriptConfiguration $solrConfig = null;

    private ?string $getParameter = null;

    private ?string $pluginNamespace = null;

    public function injectJsonService(JsonService $jsonService): void
    {
        $this->jsonService = $jsonService;
    }

    public function formAction(): ResponseInterface
    {
        parent::formAction();

        $pageConfig = ConfigUtility::getRootPageConfig();

        $targetPageUid = isset($this->settings['global'])
            ? (int) ($pageConfig['solr']['searchPage'] ?? 0)
            : $this->solrConfig?->getSearchTargetPage() ?? 0;

        $searchUrl = $this->uriBuilder
            ->reset()
            ->setTargetPageUid($targetPageUid)
            ->build();

        $searchUrlWithQueryParam = $this->uriBuilder
            ->reset()
            ->setTargetPageUid($targetPageUid)
            ->setArguments($this->getSearchArguments('*'))
            ->build();

        $queryParam = str_replace('=*', '', str_replace($searchUrl . '?', '', urldecode($searchUrlWithQueryParam)));

        $suggestUrl = $this->uriBuilder
            ->reset()
            ->setTargetPageUid($targetPageUid)
            ->setTargetPageType((int)$this->settings['suggest']['typeNum'])
            ->build();

        $result = [
            'search' => [
                'queryParam' => $queryParam,
                'url' => $searchUrl,
            ],
            'suggest' => [
                'queryParam' => $this->pluginNamespace . '[queryString]',
                'url' => $suggestUrl,
            ],
        ];

        return $this->jsonResponse(json_encode($result) ?: null);
    }

    public function resultsAction(): ResponseInterface
    {
        parent::resultsAction();

        $renderingContext = $this->view->getRenderingContext();
        $viewHelperInvoker = $renderingContext->getViewHelperInvoker();

        $variables = $renderingContext->getVariableProvider()->getAll();
        /** @var \ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\SearchResultSet $searchResultSet */
        $searchResultSet = $variables['resultSet'];
        /** @var \ApacheSolrForTypo3\Solr\Pagination\ResultsPagination $pagination */
        $pagination = $variables['pagination'];
        /** @var int $currentPage */
        $currentPage = $variables['currentPage'];

        $documents = [];

        $searchResults = $searchResultSet->getSearchResults();

        /** @var \ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Result\SearchResult $searchResult */
        foreach ($searchResults as $searchResult) {
            $imageJson = null;
            $imageUid = $searchResult->__get('image_intS') ?? null;
            if ($imageUid) {
                $imageJson = $this->jsonService?->processImage($imageUid);
            }

            $document = [
                'content' => $viewHelperInvoker->invoke(
                    HighlightResultViewHelper::class,
                    ['resultSet' => $searchResultSet, 'document' => $searchResult, 'fieldName' => 'content'],
                    $renderingContext
                ),
                'image' => $imageJson,
                'link' => $searchResult->getUrl(),
                'title' => $searchResult->getTitle(),
                'type' => $searchResult->getType(),
            ];

            /** @var ModifySearchDocumentEvent $event */
            $event = $this->eventDispatcher->dispatch(
                new ModifySearchDocumentEvent($document, $searchResult, $renderingContext)
            );

            $documents[] = $event->getDocument();
        }

        $paginationResult = $this->jsonService?->serializePagination($pagination, 'page', $currentPage);

        $count = $searchResultSet->getAllResultCount();

        $usedQuery = $searchResultSet->getUsedQuery();
        $query = $usedQuery ? $usedQuery->getOption('query') : null;

        $facets = [];

        /** @var \ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\AbstractFacet $facet */
        foreach ($searchResultSet->getFacets()->getAvailable() as $facet) {
            $options = [];
            if ($facet instanceof AbstractOptionsFacet) {
                /** @var \ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\OptionBased\AbstractOptionFacetItem $option */
                foreach ($facet->getOptions() as $option) {
                    $link = $viewHelperInvoker->invoke(
                        SetFacetItemViewHelper::class,
                        ['facet' => $facet, 'facetItem' => $option],
                        $renderingContext,
                    );

                    $options[] = [
                        'active' => $option->getSelected(),
                        'count' => $option->getDocumentCount(),
                        'label' => $option->getLabel(),
                        'link' => $link,
                        'value' => $option->getValue(),
                    ];
                }
            }

            $allOptionsLink = $viewHelperInvoker->invoke(RemoveAllFacetsViewHelper::class, [], $renderingContext);
            $allOptionsCount = array_reduce($options, function (int $result, array $option) {
                return $result + $option['count'];
            }, 0);

            $facets[] = [
                'allOptions' => [
                    'active' => !$facet->getIsUsed(),
                    'count' => $allOptionsCount,
                    'link' => $allOptionsLink,
                ],
                'field' => $facet->getField(),
                'label' => $facet->getLabel(),
                'name' => $facet->getName(),
                'options' => $options,
            ];
        }

        $spellCheckingSuggestion = current($searchResultSet->getSpellCheckingSuggestions());

        $result = [
            'count' => $count,
            'documents' => $documents,
            'facets' => $facets,
            'pagination' => $paginationResult,
            'query' => $query,
            'spellCheckingSuggestion' => $spellCheckingSuggestion
                ? [
                    'label' => $spellCheckingSuggestion->getSuggestion(),
                    'link' => $this->uriBuilder
                        ->reset()
                        ->setArguments($this->getSearchArguments($spellCheckingSuggestion->getSuggestion()))
                        ->build(),
                ]
                : null,
        ];

        return $this->jsonResponse(json_encode($result) ?: null);
    }

    protected function initializeAction(): void
    {
        parent::initializeAction();
        $this->solrConfig = Util::getSolrConfiguration();
        $this->getParameter = $this->solrConfig->getValueByPathOrDefaultValue(
            'plugin.tx_solr.search.query.getParameter',
            'q'
        );
        $this->pluginNamespace = $this->typoScriptConfiguration?->getSearchPluginNamespace();
    }

    /**
     * @return string[]
     */
    private function getSearchArguments(string $term): array
    {
        return [$this->pluginNamespace . '[' . $this->getParameter . ']' => $term];
    }
}
