<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor;

use Oro\Bundle\ApiBundle\Processor\OptimizedProcessorBag;
use Oro\Component\ChainProcessor\ChainApplicableChecker;
use Oro\Component\ChainProcessor\Context;
use Oro\Component\ChainProcessor\ProcessorApplicableCheckerFactoryInterface;
use Oro\Component\ChainProcessor\ProcessorBagConfigBuilder;
use Oro\Component\ChainProcessor\ProcessorIterator;
use Oro\Component\ChainProcessor\ProcessorIteratorFactoryInterface;
use Oro\Component\ChainProcessor\ProcessorRegistryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OptimizedProcessorBagTest extends TestCase
{
    private ProcessorRegistryInterface&MockObject $processorRegistry;
    private ProcessorApplicableCheckerFactoryInterface&MockObject $applicableCheckerFactory;
    private ProcessorIteratorFactoryInterface&MockObject $processorIteratorFactory;
    private ProcessorIteratorFactoryInterface&MockObject $ungroupedProcessorIteratorFactory;
    private ChainApplicableChecker $applicableChecker;
    private OptimizedProcessorBag $processorBag;

    #[\Override]
    protected function setUp(): void
    {
        $this->processorRegistry = $this->createMock(ProcessorRegistryInterface::class);
        $this->applicableCheckerFactory = $this->createMock(ProcessorApplicableCheckerFactoryInterface::class);
        $this->processorIteratorFactory = $this->createMock(ProcessorIteratorFactoryInterface::class);
        $this->ungroupedProcessorIteratorFactory = $this->createMock(ProcessorIteratorFactoryInterface::class);
        $this->applicableChecker = new ChainApplicableChecker();

        $this->applicableCheckerFactory->expects(self::any())
            ->method('createApplicableChecker')
            ->willReturn($this->applicableChecker);

        $processorBagConfigBuilder = new ProcessorBagConfigBuilder();
        $this->processorBag = new OptimizedProcessorBag(
            $processorBagConfigBuilder,
            $this->processorRegistry,
            false,
            $this->applicableCheckerFactory,
            $this->processorIteratorFactory,
            $this->ungroupedProcessorIteratorFactory
        );

        $processorBagConfigBuilder->addGroup('group1', 'action_with_groups');
        $processorBagConfigBuilder->addProcessor('processor1', [], 'action_with_groups', 'group1');
        $processorBagConfigBuilder->addProcessor('processor2', [], 'action_without_groups');
    }

    public function testBagWithGroups(): void
    {
        $context = new Context();
        $context->setAction('action_with_groups');

        $iterator = $this->createMock(ProcessorIterator::class);

        $this->processorIteratorFactory->expects(self::once())
            ->method('createProcessorIterator')
            ->with(
                [['processor1', ['group' => 'group1']]],
                self::identicalTo($context),
                self::identicalTo($this->applicableChecker),
                self::identicalTo($this->processorRegistry)
            )
            ->willReturn($iterator);

        self::assertSame(
            $iterator,
            $this->processorBag->getProcessors($context)
        );
    }

    public function testBagWithoutGroups(): void
    {
        $context = new Context();
        $context->setAction('action_without_groups');

        $iterator = $this->createMock(ProcessorIterator::class);

        $this->ungroupedProcessorIteratorFactory->expects(self::once())
            ->method('createProcessorIterator')
            ->with(
                [['processor2', []]],
                self::identicalTo($context),
                self::identicalTo($this->applicableChecker),
                self::identicalTo($this->processorRegistry)
            )
            ->willReturn($iterator);

        self::assertSame(
            $iterator,
            $this->processorBag->getProcessors($context)
        );
    }
}
