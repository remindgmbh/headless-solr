<?php

declare(strict_types=1);

namespace Remind\HeadlessSolr\IndexQueue;

use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\Exception\MissingArrayPathException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class UserFunc
{
    private $cObj;

    public function setContentObjectRenderer(ContentObjectRenderer $cObj): void
    {
        $this->cObj = $cObj;
    }

    public function getFlexFormValues(string $content, array $conf): ?string
    {
        $type = $this->cObj->data['CType'];
        $fields = GeneralUtility::trimExplode(',', $conf['types.'][$type . '.']['fields'] ?? '', true);

        if ($fields) {
            $flexFormService = GeneralUtility::makeInstance(FlexFormService::class);

            $flexformArray = $flexFormService->convertFlexFormContentToArray($content);

            $result = [];

            foreach ($fields as $field) {
                try {
                    $value = ArrayUtility::getValueByPath($flexformArray, $field, '.');
                    $result[] = $value;
                } catch (MissingArrayPathException $e) {
                    // do nothing if array path does not exists
                }
            }

            return implode(' ', $result);
        }

        return null;
    }
}
