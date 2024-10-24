<?php

namespace Oro\Bundle\WorkflowBundle\Validator\Constraints;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Symfony\Component\Validator\Constraint;

/**
 * Validation constraint checking if workflow transition is allowed.
 */
class TransitionIsAllowed extends Constraint
{
    /**
     * @var WorkflowItem
     */
    protected $workflowItem;

    /**
     * @var string
     */
    protected $transitionName;

    public $unknownTransitionMessage = '"Transition {{ transition }}" is not exist in workflow.';
    public $notStartTransitionMessage = '"{{ transition }}" is not start transition.';
    public $stepHasNotAllowedTransitionMessage = '"{{ transition }}" transition is not allowed at step "{{ step }}".';
    public $someConditionsNotMetMessage = 'Some transition conditions are not met.';

    /**
     * @param WorkflowItem $workflowItem
     * @param string $transitionName
     */
    public function __construct(WorkflowItem $workflowItem, $transitionName)
    {
        parent::__construct();

        $this->workflowItem = $workflowItem;
        $this->transitionName = $transitionName;
    }

    /**
     * @return WorkflowItem
     */
    public function getWorkflowItem()
    {
        return $this->workflowItem;
    }

    /**
     * @return string
     */
    public function getTransitionName()
    {
        return $this->transitionName;
    }

    #[\Override]
    public function validatedBy(): string
    {
        return TransitionIsAllowedValidator::ALIAS;
    }

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
