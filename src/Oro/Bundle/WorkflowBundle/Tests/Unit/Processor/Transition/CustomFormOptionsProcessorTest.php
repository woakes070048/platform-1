<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Processor\Transition;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Form\EventListener\FormInitListener;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Bundle\WorkflowBundle\Processor\Transition\CustomFormOptionsProcessor;
use Oro\Component\Action\Action\ActionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CustomFormOptionsProcessorTest extends TestCase
{
    private FormInitListener&MockObject $formInitListener;
    private CustomFormOptionsProcessor $processor;

    #[\Override]
    protected function setUp(): void
    {
        $this->formInitListener = $this->createMock(FormInitListener::class);

        $this->processor = new CustomFormOptionsProcessor($this->formInitListener);
    }

    public function testSkipDefaultTransitionForms(): void
    {
        $transition = $this->createMock(Transition::class);
        $transition->expects($this->once())
            ->method('hasFormConfiguration')
            ->willReturn(false);

        $context = $this->createMock(TransitionContext::class);
        $context->expects($this->once())
            ->method('getTransition')
            ->willReturn($transition);
        $context->expects($this->never())
            ->method('getWorkflowItem');

        $this->processor->process($context);
    }

    public function testWithFormInit(): void
    {
        $formData = (object)['id' => 42];

        $data = $this->createMock(WorkflowData::class);
        $data->expects($this->once())
            ->method('get')
            ->with('formAttribute')
            ->willReturn($formData);

        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->any())
            ->method('getWorkflowName')
            ->willReturn('test_workflow');
        $workflowItem->expects($this->once())
            ->method('getData')
            ->willReturn($data);
        $workflowItem->expects($this->once())
            ->method('lock');
        $workflowItem->expects($this->once())
            ->method('unlock');

        $action = $this->createMock(ActionInterface::class);

        $transition = $this->createMock(Transition::class);
        $transition->expects($this->once())
            ->method('hasFormConfiguration')
            ->willReturn(true);
        $transition->expects($this->any())
            ->method('getFormOptions')
            ->willReturn(['form_init' => $action]);
        $transition->expects($this->once())
            ->method('getFormDataAttribute')
            ->willReturn('formAttribute');

        $this->formInitListener->expects($this->once())
            ->method('executeInitAction')
            ->with($action, $workflowItem);
        $this->formInitListener->expects($this->once())
            ->method('dispatchFormInitEvents')
            ->with($workflowItem, $transition);

        $context = new TransitionContext();
        $context->setTransition($transition);
        $context->setWorkflowItem($workflowItem);

        $this->processor->process($context);

        $this->assertSame($formData, $context->getFormData());
    }

    public function testWithoutFormInit(): void
    {
        $formData = (object)['id' => 42];

        $data = $this->createMock(WorkflowData::class);
        $data->expects($this->once())
            ->method('get')
            ->with('formAttribute')
            ->willReturn($formData);

        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->any())
            ->method('getWorkflowName')
            ->willReturn('test_workflow');
        $workflowItem->expects($this->once())
            ->method('getData')
            ->willReturn($data);

        $transition = $this->createMock(Transition::class);
        $transition->expects($this->once())
            ->method('hasFormConfiguration')
            ->willReturn(true);
        $transition->expects($this->any())
            ->method('getFormOptions')
            ->willReturn([]);
        $transition->expects($this->once())
            ->method('getFormDataAttribute')
            ->willReturn('formAttribute');

        $context = new TransitionContext();
        $context->setTransition($transition);
        $context->setWorkflowItem($workflowItem);

        $this->processor->process($context);

        $this->assertSame($formData, $context->getFormData());
    }
}
