<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroChoiceType;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowTransitionSelectType;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\TransitionManager;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class WorkflowTransitionSelectTypeTest extends FormIntegrationTestCase
{
    private WorkflowRegistry&MockObject $workflowRegistry;
    private TranslatorInterface&MockObject $translator;
    private WorkflowTransitionSelectType $type;

    #[\Override]
    protected function setUp(): void
    {
        $this->workflowRegistry = $this->createMock(WorkflowRegistry::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->type = new WorkflowTransitionSelectType($this->workflowRegistry, $this->translator);
        parent::setUp();
    }

    public function testSubmit()
    {
        $transition = $this->getTransition('transition_name', 'transition_label');

        $transitionManager = $this->createMock(TransitionManager::class);
        $transitionManager->expects($this->once())
            ->method('getTransitions')
            ->willReturn([$transition]);

        $workflow = $this->createMock(Workflow::class);
        $workflow->expects($this->once())
            ->method('getTransitionManager')
            ->willReturn($transitionManager);

        $this->workflowRegistry->expects($this->once())
            ->method('getWorkflow')
            ->with('test_workflow')
            ->willReturn($workflow);

        $form = $this->factory->create(WorkflowTransitionSelectType::class, null, ['workflowName' => 'test_workflow']);

        $this->assertFormOptionEqual([$transition->getLabel() => $transition->getName()], 'choices', $form);
        $this->assertNull($form->getData());

        $form->submit($transition->getName());

        $this->assertFormIsValid($form);
        $this->assertEquals($transition->getName(), $form->getData());
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefined')
            ->with('workflowName');
        $resolver->expects($this->once())
            ->method('setAllowedTypes')
            ->with('workflowName', ['string', 'null']);
        $resolver->expects($this->once())
            ->method('setNormalizer')
            ->with('choices');

        $this->type->configureOptions($resolver);
    }

    public function testGetParent()
    {
        $this->assertEquals(OroChoiceType::class, $this->type->getParent());
    }

    /**
     * @dataProvider incorrectOptionsDataProvider
     */
    public function testNormalizersException(array $options, string $exceptionMessage)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $this->factory->create(WorkflowTransitionSelectType::class, null, $options);
    }

    public function incorrectOptionsDataProvider(): array
    {
        return [
            'empty options' => [
                'options' => [],
                'exceptionMessage' => 'The required option "workflowName" is missing',
            ],
            'wrong options' => [
                'options' => ['workflowName' => new \stdClass()],
                'exceptionMessage' => 'The option "workflowName" with value stdClass is expected to be of type ' .
                    '"string" or "null", but is of type "stdClass"',
            ],
        ];
    }

    #[\Override]
    protected function getExtensions(): array
    {
        $choiceType = $this->createMock(OroChoiceType::class);
        $choiceType->expects($this->any())
            ->method('getParent')
            ->willReturn(ChoiceType::class);

        return [
            new PreloadedExtension(
                [
                    $this->type,
                    OroChoiceType::class => $choiceType
                ],
                []
            )
        ];
    }

    public function testFinishView()
    {
        $label = 'test_label';
        $translatedLabel = 'translated_test_label';

        $view = new FormView();
        $view->vars['choices'] = [new ChoiceView([], 'test', $label)];

        $this->translator->expects($this->once())
            ->method('trans')
            ->with($label, [], WorkflowTranslationHelper::TRANSLATION_DOMAIN)
            ->willReturn($translatedLabel);

        $this->type->finishView($view, $this->createMock(FormInterface::class), []);

        $this->assertEquals([new ChoiceView([], 'test', "$translatedLabel (test)")], $view->vars['choices']);
    }

    private function getTransition(string $name, string $label): Transition
    {
        $transition = $this->createMock(Transition::class);
        $transition->expects($this->any())
            ->method('getName')
            ->willReturn($name);
        $transition->expects($this->any())
            ->method('getLabel')
            ->willReturn($label);

        return $transition;
    }
}
