<?php

namespace Oro\Bundle\ApiBundle\Processor\Get;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Makes sure that the valid result was added to the context.
 */
class AssertHasResult implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        if (!$context->hasResult()) {
            throw new NotFoundHttpException('Unsupported request.');
        }
        if (null === $context->getResult()) {
            throw new NotFoundHttpException('An entity with the requested identifier does not exist.');
        }
    }
}
