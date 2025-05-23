<?php

namespace Oro\Bundle\WorkflowBundle\Form\EventListener;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class DefaultValuesListener implements EventSubscriberInterface
{
    /**
     * @var ContextAccessor $contextAccessor
     */
    protected $contextAccessor;

    /**
     * @var WorkflowItem
     */
    protected $workflowItem;

    /**
     * @var array
     */
    protected $defaultValues;

    public function __construct(ContextAccessor $contextAccessor)
    {
        $this->contextAccessor = $contextAccessor;
    }

    /**
     * Initialize listener with required data
     */
    public function initialize(
        WorkflowItem $workflowItem,
        array $defaultValues = array()
    ) {
        $this->workflowItem = $workflowItem;
        $this->defaultValues = $defaultValues;
    }

    /**
     * Updates default values
     */
    public function setDefaultValues(FormEvent $event)
    {
        /** @var WorkflowData $workflowData */
        $workflowData = $event->getData();

        foreach ($this->defaultValues as $attributeName => $value) {
            $workflowData->set($attributeName, $this->contextAccessor->getValue($this->workflowItem, $value));
        }
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return array(FormEvents::PRE_SET_DATA => 'setDefaultValues');
    }
}
