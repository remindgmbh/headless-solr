<?php

declare(strict_types=1);

namespace Remind\HeadlessSolr\Tests\Unit\IndexQueue;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Remind\HeadlessSolr\IndexQueue\UserFunc;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[CoversClass(UserFunc::class)]
final class UserFuncTest extends UnitTestCase
{
    #[Test]
    public function getFlexFormValuesReturnsKnownPathsAndIgnoresMissingPaths(): void
    {
        $subject = new UserFunc();

        $contentObject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $contentObject->data = ['CType' => 'textmedia'];
        $subject->setContentObjectRenderer($contentObject);

        $flexFormService = $this->createMock(FlexFormService::class);
        $flexFormService
            ->expects(self::once())
            ->method('convertFlexFormContentToArray')
            ->with('<xml/>')
            ->willReturn([
                'search' => [
                    'placeholderText' => 'Find me',
                ],
            ]);
        GeneralUtility::setSingletonInstance(FlexFormService::class, $flexFormService);

        $configuration = [
            'types.' => [
                'textmedia.' => [
                    'fields' => 'search.placeholderText,search.missing.path',
                ],
            ],
        ];

        $result = $subject->getFlexFormValues('<xml/>', $configuration);

        self::assertSame('Find me', $result);
    }

    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
        parent::tearDown();
    }
}
