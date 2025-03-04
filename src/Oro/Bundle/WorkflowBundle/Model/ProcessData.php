<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Oro\Component\Action\Model\AbstractStorage as ComponentAbstractStorage;

class ProcessData extends ComponentAbstractStorage implements EntityAwareInterface
{
    #[\Override]
    public function getEntity()
    {
        return $this->get('data');
    }
}
