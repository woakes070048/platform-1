<?php

namespace Oro\Bundle\WorkflowBundle\Model;

class WorkflowAwareManager implements WorkflowAwareInterface
{
    /** @var string */
    protected $workflowName;

    /** @var WorkflowManager */
    private $workflowManager;

    public function __construct(WorkflowManager $workflowManager)
    {
        $this->workflowManager = $workflowManager;
    }

    /**
     * @return Workflow
     * @throws \Oro\Bundle\WorkflowBundle\Exception\WorkflowException
     */
    public function getWorkflow()
    {
        return $this->workflowManager->getWorkflow($this->workflowName);
    }

    /**
     * @param object $entity
     * @return \Oro\Bundle\WorkflowBundle\Entity\WorkflowItem
     */
    public function startWorkflow($entity)
    {
        return $this->workflowManager->startWorkflow($this->workflowName, $entity);
    }

    /**
     * @param $entity
     * @return null|\Oro\Bundle\WorkflowBundle\Entity\WorkflowItem
     */
    public function getWorkflowItem($entity)
    {
        return $this->workflowManager->getWorkflowItem($entity, $this->workflowName);
    }

    #[\Override]
    public function setWorkflowName($workflowName)
    {
        $this->workflowName = $workflowName;
    }

    #[\Override]
    public function getWorkflowName()
    {
        return $this->workflowName;
    }
}
