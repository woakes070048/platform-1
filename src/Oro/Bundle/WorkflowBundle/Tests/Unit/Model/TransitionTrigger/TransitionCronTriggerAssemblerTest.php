<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\TransitionTrigger;

use Oro\Bundle\WorkflowBundle\Entity\TransitionCronTrigger;
use Oro\Bundle\WorkflowBundle\Entity\TransitionEventTrigger;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\TransitionTrigger\TransitionCronTriggerAssembler;
use Oro\Bundle\WorkflowBundle\Model\TransitionTrigger\TransitionTriggerCronVerifier;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Model\TransitionTrigger\Stub\AbstractTransitionTriggerAssemblerStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TransitionCronTriggerAssemblerTest extends TestCase
{
    private TransitionTriggerCronVerifier&MockObject $verifier;
    private TransitionCronTriggerAssembler $assembler;

    #[\Override]
    protected function setUp(): void
    {
        $this->verifier = $this->createMock(TransitionTriggerCronVerifier::class);

        $this->assembler = new TransitionCronTriggerAssembler($this->verifier);
    }

    /**
     * @dataProvider canAssembleData
     */
    public function testCanAssemble(bool $expected, array $options): void
    {
        $this->assertEquals($expected, $this->assembler->canAssemble($options));
    }

    public function canAssembleData(): array
    {
        return [
            'can' => [
                true,
                [
                    'cron' => '* * * * *'
                ]
            ],
            'can not. cron null' => [
                false,
                [
                    'cron' => null
                ]
            ],
            'can not: cron not defined' => [
                false,
                [
                    'event' => 'create'
                ]
            ]
        ];
    }

    public function testAssemble(): void
    {
        $cronOpt = '* * * * *';
        $filterOpt = 'a = b';
        $queuedOpt = false;
        $transitionOpt = 'transitionName';
        $workflowDefinitionOpt = new WorkflowDefinition();

        $this->verifier->expects($this->once())
            ->method('verify')
            ->with($this->isInstanceOf(TransitionCronTrigger::class));

        /** @var TransitionCronTrigger $trigger */
        $trigger = $this->assembler->assemble(
            [
                'cron' => $cronOpt,
                'filter' => $filterOpt,
                'queued' => $queuedOpt
            ],
            $transitionOpt,
            $workflowDefinitionOpt
        );

        $this->assertInstanceOf(
            TransitionCronTrigger::class,
            $trigger,
            'Must return new instance of cron trigger entity'
        );

        $this->assertSame($cronOpt, $trigger->getCron());
        $this->assertSame($filterOpt, $trigger->getFilter());
        $this->assertSame($queuedOpt, $trigger->isQueued());
        $this->assertSame($transitionOpt, $trigger->getTransitionName());
        $this->assertSame($workflowDefinitionOpt, $trigger->getWorkflowDefinition());
    }

    public function testAssembleDefaults(): void
    {
        $cronOpt = '* * * * *';
        $filterOpt = null;
        $queuedOpt = true;
        $transitionOpt = 'transitionName';
        $workflowDefinitionOpt = new WorkflowDefinition();

        $this->verifier->expects($this->once())
            ->method('verify')
            ->with($this->isInstanceOf(TransitionCronTrigger::class));

        /** @var TransitionCronTrigger $trigger */
        $trigger = $this->assembler->assemble(
            [
                'cron' => $cronOpt,
            ],
            $transitionOpt,
            $workflowDefinitionOpt
        );

        $this->assertInstanceOf(
            TransitionCronTrigger::class,
            $trigger,
            'Must return new instance of cron trigger entity'
        );

        $this->assertSame($cronOpt, $trigger->getCron());
        $this->assertSame($filterOpt, $trigger->getFilter());
        $this->assertSame($queuedOpt, $trigger->isQueued());
        $this->assertSame($transitionOpt, $trigger->getTransitionName());
        $this->assertSame($workflowDefinitionOpt, $trigger->getWorkflowDefinition());
    }

    public function testVerifyTriggerException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Expected instance of Oro\Bundle\WorkflowBundle\Entity\TransitionCronTrigger ' .
            'got Oro\Bundle\WorkflowBundle\Entity\TransitionEventTrigger'
        );

        $stub = new AbstractTransitionTriggerAssemblerStub();

        $stub->verifyProxy($this->assembler, new TransitionEventTrigger());
    }
}
