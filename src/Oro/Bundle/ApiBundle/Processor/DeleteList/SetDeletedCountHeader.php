<?php

namespace Oro\Bundle\ApiBundle\Processor\DeleteList;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Calculates and sets the total number of deleted records to "X-Include-Deleted-Count" response header
 * if it was requested by "X-Include: deletedCount" request header.
 */
class SetDeletedCountHeader implements ProcessorInterface
{
    public const RESPONSE_HEADER_NAME = 'X-Include-Deleted-Count';
    public const REQUEST_HEADER_VALUE = 'deletedCount';

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var DeleteListContext $context */

        if ($context->getResponseHeaders()->has(self::RESPONSE_HEADER_NAME)) {
            // the deleted records count header is already set
            return;
        }

        $xInclude = $context->getRequestHeaders()->get(Context::INCLUDE_HEADER);
        if (empty($xInclude) || !\in_array(self::REQUEST_HEADER_VALUE, $xInclude, true)) {
            // the deleted records count is not requested
            return;
        }

        $result = $context->getResult();
        if (\is_array($result)) {
            $context->getResponseHeaders()->set(self::RESPONSE_HEADER_NAME, \count($result));
        }
    }
}
