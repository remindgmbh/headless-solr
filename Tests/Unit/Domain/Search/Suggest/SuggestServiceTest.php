<?php

declare(strict_types=1);

namespace Remind\HeadlessSolr\Tests\Unit\Domain\Search\Suggest;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Result\SearchResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use Remind\HeadlessSolr\Domain\Search\Suggest\SuggestService;
use Remind\HeadlessSolr\Event\ModifySuggestionDocumentEvent;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[CoversClass(SuggestService::class)]
final class SuggestServiceTest extends UnitTestCase
{
    #[Test]
    public function getDocumentAsArrayUsesDocumentFromDispatchedEvent(): void
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(ModifySuggestionDocumentEvent::class))
            ->willReturnCallback(static function (ModifySuggestionDocumentEvent $event): ModifySuggestionDocumentEvent {
                $event->setDocument(['title' => 'changed-by-listener']);
                return $event;
            });
        GeneralUtility::addInstance(EventDispatcherInterface::class, $eventDispatcher);

        $document = new SearchResult([
            'content' => 'Content',
            'title' => 'Original',
            'type' => 'page',
            'url' => '/page',
        ]);

        $subject = new TestableSuggestService();

        $result = $subject->getDocumentAsArrayProxy($document);

        self::assertSame(['title' => 'changed-by-listener'], $result);
    }

    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
        parent::tearDown();
    }
}
