<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Removes the "X-Include-Total-Count" response header if any error occurs.
 */
class RemoveTotalCountHeader implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        if ($context->hasErrors()
            && $context->getResponseHeaders()->has(SetTotalCountHeader::RESPONSE_HEADER_NAME)
        ) {
            $context->getResponseHeaders()->remove(SetTotalCountHeader::RESPONSE_HEADER_NAME);
        }
    }
}
