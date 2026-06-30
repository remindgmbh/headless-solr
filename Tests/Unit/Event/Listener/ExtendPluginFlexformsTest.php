<?php

declare(strict_types=1);

namespace Remind\HeadlessSolr\Tests\Unit\Event\Listener;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Remind\HeadlessSolr\Event\Listener\ExtendPluginFlexforms;
use TYPO3\CMS\Core\Configuration\Event\AfterFlexFormDataStructureParsedEvent;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[CoversClass(ExtendPluginFlexforms::class)]
final class ExtendPluginFlexformsTest extends UnitTestCase
{
    #[Test]
    public function invokeExtendsDataStructureOnlyForMatchingIdentifier(): void
    {
        $subject = new ExtendPluginFlexforms();
        $baseDataStructure = ['sheets' => ['sDEF' => ['ROOT' => ['el' => []]]]];

        $nonMatchingEvent = new AfterFlexFormDataStructureParsedEvent(
            $baseDataStructure,
            [
                'dataStructureKey' => 'other_plugin,list',
                'tableName' => 'tt_content',
                'type' => 'tca',
            ]
        );
        $subject($nonMatchingEvent);

        self::assertSame($baseDataStructure, $nonMatchingEvent->getDataStructure());

        $matchingEvent = new AfterFlexFormDataStructureParsedEvent(
            $baseDataStructure,
            [
                'dataStructureKey' => 'solr_pi_results,list',
                'tableName' => 'tt_content',
                'type' => 'tca',
            ]
        );
        $subject($matchingEvent);

        $result = $matchingEvent->getDataStructure();

        self::assertTrue(isset($result['sheets']['sDEF']['ROOT']['el']['search.noResultsText']));
    }
}
