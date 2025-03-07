<?php

namespace Oro\Bundle\EntityBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\GetList\GetListContext;
use Oro\Bundle\EntityBundle\Provider\EntityStructureDataProvider;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Loads detailed information about entities.
 */
class LoadEntityStructures implements ProcessorInterface
{
    private EntityStructureDataProvider $entityStructureProvider;

    public function __construct(EntityStructureDataProvider $entityStructureProvider)
    {
        $this->entityStructureProvider = $entityStructureProvider;
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var GetListContext $context */

        // disabling the garbage collector gives a significant performance gain (about 50% faster)
        $gcEnabled = gc_enabled();
        if ($gcEnabled) {
            gc_disable();
        }
        try {
            $context->setResult($this->entityStructureProvider->getEntities());
        } finally {
            if ($gcEnabled) {
                gc_enable();
            }
        }
    }
}
