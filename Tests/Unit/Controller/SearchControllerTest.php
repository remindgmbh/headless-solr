<?php

declare(strict_types=1);

namespace Remind\HeadlessSolr\Tests\Unit\Controller;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Remind\HeadlessSolr\Controller\SearchController;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[CoversClass(SearchController::class)]
final class SearchControllerTest extends UnitTestCase
{
    #[Test]
    public function getSearchArgumentsBuildsNamespacedQueryParameter(): void
    {
        $subject = new SearchController();

        $reflection = new ReflectionClass($subject);

        $pluginNamespaceProperty = $reflection->getProperty('pluginNamespace');
        $pluginNamespaceProperty->setValue($subject, 'tx_solr');

        $getParameterProperty = $reflection->getProperty('getParameter');
        $getParameterProperty->setValue($subject, 'q');

        $method = $reflection->getMethod('getSearchArguments');
        $result = $method->invoke($subject, 'test-term');

        self::assertSame(['tx_solr[q]' => 'test-term'], $result);
    }
}
