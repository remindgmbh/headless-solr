<?php

declare(strict_types=1);

namespace Remind\HeadlessSolr\Controller;

use ApacheSolrForTypo3\Solr\Controller\SearchController as BaseSearchController;
use ApacheSolrForTypo3\Solr\Util;
use ApacheSolrForTypo3\Solr\ViewHelpers\Document\HighlightResultViewHelper;
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

        return $this->jsonResponse(json_encode([
            'form' => $this->getForm(),
        ]));
    }

    public function resultsAction(): ResponseInterface
    {
        parent::resultsAction();

        $renderingContext = $this->view->getRenderingContext();

        $variables = $renderingContext->getVariableProvider()->getAll();
        /** @var \ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\SearchResultSet $newsQueryResult */
        $resultSet = $variables['resultSet'];
        /** @var \ApacheSolrForTypo3\Solr\Pagination\ResultsPagination $pagination */
        $pagination = $variables['pagination'];
        /** @var int $currentPage */
        $currentPage = $variables['currentPage'];

        $documents = [];

        $searchResults = $resultSet->getSearchResults();

        foreach ($searchResults as $searchResult) {
            /** @var \ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Result\SearchResult $searchResult */
            $documents[] = [
                'title' => $searchResult->getTitle(),
                'content' => $renderingContext->getViewHelperInvoker()->invoke(
                    HighlightResultViewHelper::class,
                    ['resultSet' => $resultSet, 'document' => $searchResult, 'fieldName' => 'content'],
                    $renderingContext
                ),
                'url' => $searchResult->getUrl(),
            ];
        }

        $paginationResult = $this->jsonService->serializePagination($pagination, 'page', $currentPage);

        $count = $resultSet->getAllResultCount();

        $usedQuery = $resultSet->getUsedQuery();
        $query = $usedQuery ? $usedQuery->getOption('query') : null;

        $result = [
            'documents' => $documents,
            'count' => $count,
            'query' => $query,
            'pagination' => $paginationResult,
        ];

        return $this->jsonResponse(json_encode([
            'form' => $this->getForm(),
            'results' => $result,
        ]));
    }

    private function getForm(): array
    {
        $pluginNamespace = $this->typoScriptConfiguration->getSearchPluginNamespace();

        $config = Util::getSolrConfiguration();
        $targetPageUid = $config->getSearchTargetPage();
        $getParameter = $config->getValueByPathOrDefaultValue('plugin.tx_solr.search.query.getParameter', 'q');

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

        return [
            'search' => [
                'url' => $searchUrl,
                'queryParam' => $queryParam,
            ],
            'suggest' => [
                'url' => $suggestUrl,
                'queryParam' => $pluginNamespace . '[queryString]',
            ],
        ];
    }
}
