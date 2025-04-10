<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Filter\Stub;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\WorkflowBundle\Model\Filter\SystemFilterInterface;
use Oro\Bundle\WorkflowBundle\Model\Filter\WorkflowDefinitionFilterInterface;

class SystemDefinitionFilter implements WorkflowDefinitionFilterInterface, SystemFilterInterface
{
    #[\Override]
    public function filter(Collection $workflowDefinitions): Collection
    {
        return $workflowDefinitions;
    }
}
