<?php

declare(strict_types=1);

namespace Remind\HeadlessSolr\Controller;

use ApacheSolrForTypo3\Solr\Controller\SearchController as BaseSearchController;
use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\OptionBased\AbstractOptionsFacet;
use ApacheSolrForTypo3\Solr\Util;
use ApacheSolrForTypo3\Solr\ViewHelpers\Document\HighlightResultViewHelper;
use ApacheSolrForTypo3\Solr\ViewHelpers\Uri\Facet\RemoveAllFacetsViewHelper;
use ApacheSolrForTypo3\Solr\ViewHelpers\Uri\Facet\SetFacetItemViewHelper;
use Psr\Http\Message\ResponseInterface;
use Remind\Headless\Service\JsonService;

class SearchController extends BaseSearchController
{
    private ?JsonService $jsonService = null;

    public function injectJsonService(JsonService $jsonService): void
    {
        $this->jsonService = $jsonService;
    }

    public function formAction(): ResponseInterface
    {
        parent::formAction();

        $config = Util::getSolrConfiguration();
        $targetPageUid = $config->getSearchTargetPage();
        $getParameter = $config->getValueByPathOrDefaultValue('plugin.tx_solr.search.query.getParameter', 'q');

        $arguments = $this->request->getArguments();
        $query = $arguments[$getParameter];

        $pluginNamespace = $this->typoScriptConfiguration->getSearchPluginNamespace();

        $searchUrl = $this->uriBuilder
            ->reset()
            ->setTargetPageUid($targetPageUid)
            ->build();

        $searchUrlWithQueryParam = $this->uriBuilder
            ->reset()
            ->setArguments([$pluginNamespace . '[' . $getParameter . ']' => '*'])
            ->build();

        $queryParam = str_replace('=*', '', str_replace($searchUrl . '?', '', urldecode($searchUrlWithQueryParam)));

        $suggestUrl = $this->uriBuilder
            ->reset()
            ->setTargetPageUid($targetPageUid)
            ->setTargetPageType((int)$this->settings['suggest']['typeNum'])
            ->build();

        $result = [
            'query' => $query,
            'search' => [
                'url' => $searchUrl,
                'queryParam' => $queryParam,
            ],
            'suggest' => [
                'url' => $suggestUrl,
                'queryParam' => $pluginNamespace . '[queryString]',
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

        foreach ($searchResults as $searchResult) {
            /** @var \ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Result\SearchResult $searchResult */
            $documents[] = [
                'title' => $searchResult->getTitle(),
                'content' => $viewHelperInvoker->invoke(
                    HighlightResultViewHelper::class,
                    ['resultSet' => $searchResultSet, 'document' => $searchResult, 'fieldName' => 'content'],
                    $renderingContext
                ),
                'link' => $searchResult->getUrl(),
            ];
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

        $result = [
            'query' => $query,
            'count' => $count,
            'pagination' => $paginationResult,
            'facets' => $facets,
            'documents' => $documents,
        ];

        return $this->jsonResponse(json_encode($result));
    }
}
