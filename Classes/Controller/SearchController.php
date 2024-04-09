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
            ? ((int) ($pageConfig['solr']['searchPage'] ?? 0))
            : $this->solrConfig->getSearchTargetPage();

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
                'url' => $searchUrl,
                'queryParam' => $queryParam,
            ],
            'suggest' => [
                'url' => $suggestUrl,
                'queryParam' => $this->pluginNamespace . '[queryString]',
            ],
        ];

        return $this->jsonResponse(json_encode($result));
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
                $imageJson = $this->jsonService->processImage($imageUid);
            }

            $document = [
                'title' => $searchResult->getTitle(),
                'content' => $viewHelperInvoker->invoke(
                    HighlightResultViewHelper::class,
                    ['resultSet' => $searchResultSet, 'document' => $searchResult, 'fieldName' => 'content'],
                    $renderingContext
                ),
                'image' => $imageJson,
                'link' => $searchResult->getUrl(),
                'type' => $searchResult->getType(),
            ];

            /** @var ModifySearchDocumentEvent $event */
            $event = $this->eventDispatcher->dispatch(
                new ModifySearchDocumentEvent($document, $searchResult, $renderingContext)
            );

            $documents[] = $event->getDocument();
        }

        $paginationResult = $this->jsonService->serializePagination($pagination, 'page', $currentPage);

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
                        'value' => $option->getValue(),
                        'label' => $option->getLabel(),
                        'link' => $link,
                        'count' => $option->getDocumentCount(),
                        'active' => $option->getSelected(),
                    ];
                }
            }

            $allOptionsLink = $viewHelperInvoker->invoke(RemoveAllFacetsViewHelper::class, [], $renderingContext);
            $allOptionsCount = array_reduce($options, function (int $result, array $option) {
                return $result + $option['count'];
            }, 0);

            $facets[] = [
                'field' => $facet->getField(),
                'name' => $facet->getName(),
                'label' => $facet->getLabel(),
                'allOptions' => [
                    'link' => $allOptionsLink,
                    'count' => $allOptionsCount,
                    'active' => !$facet->getIsUsed(),
                ],
                'options' => $options,
            ];
        }

        $spellCheckingSuggestion = current($searchResultSet->getSpellCheckingSuggestions());

        $result = [
            'query' => $query,
            'count' => $count,
            'pagination' => $paginationResult,
            'facets' => $facets,
            'documents' => $documents,
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

        return $this->jsonResponse(json_encode($result));
    }

    protected function initializeAction(): void
    {
        parent::initializeAction();
        $this->solrConfig = Util::getSolrConfiguration();
        $this->getParameter = $this->solrConfig->getValueByPathOrDefaultValue('plugin.tx_solr.search.query.getParameter', 'q');
        $this->pluginNamespace = $this->typoScriptConfiguration->getSearchPluginNamespace();
    }

    private function getSearchArguments(string $term): array
    {
        return [$this->pluginNamespace . '[' . $this->getParameter . ']' => $term];
    }
}
