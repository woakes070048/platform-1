<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Makes sure that the context does not contain the result.
 */
class AssertNotHasResult implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        if ($context->hasResult()) {
            throw new RuntimeException('The result should not exist.');
        }
    }
}
