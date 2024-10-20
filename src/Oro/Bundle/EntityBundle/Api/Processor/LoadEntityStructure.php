<?php

namespace Oro\Bundle\EntityBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Get\GetContext;
use Oro\Bundle\EntityBundle\Provider\EntityStructureDataProvider;
use Oro\Bundle\EntityConfigBundle\Exception\RuntimeException;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Loads detailed information about an entity.
 */
class LoadEntityStructure implements ProcessorInterface
{
    private EntityStructureDataProvider $entityStructureProvider;

    public function __construct(EntityStructureDataProvider $entityStructureProvider)
    {
        $this->entityStructureProvider = $entityStructureProvider;
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var GetContext $context */

        // disabling the garbage collector gives a significant performance gain
        // (up to 40% faster, depends on entity type)
        $gcEnabled = gc_enabled();
        if ($gcEnabled) {
            gc_disable();
        }
        try {
            $context->setResult($this->entityStructureProvider->getEntity($context->getId()));
        } catch (RuntimeException $e) {
            throw new NotFoundHttpException($e->getMessage());
        } finally {
            if ($gcEnabled) {
                gc_enable();
            }
        }
    }
}
