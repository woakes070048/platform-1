<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Consumption\Extension;

use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\Extension\LimitConsumedMessagesExtension;
use Oro\Component\MessageQueue\Transport\MessageConsumerInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class LimitConsumedMessagesExtensionTest extends TestCase
{
    public function testCouldBeConstructedWithRequiredArguments(): void
    {
        new LimitConsumedMessagesExtension(12345);
    }

    public function testShouldThrowExceptionIfMessageLimitIsNotInt(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected message limit is int but got: "double"');
        new LimitConsumedMessagesExtension(0.0);
    }

    public function testOnBeforeReceiveShouldInterruptExecutionIfLimitIsZero(): void
    {
        $context = $this->createContext();
        $context->getLogger()->expects($this->once())
            ->method('debug')
            ->with('Message consumption is interrupted since the message limit reached. limit: "0"');

        // guard
        $this->assertFalse($context->isExecutionInterrupted());

        // test
        $extension = new LimitConsumedMessagesExtension(0);

        // consume 1
        $extension->onBeforeReceive($context);
        $this->assertTrue($context->isExecutionInterrupted());
    }

    public function testOnBeforeReceiveShouldInterruptExecutionIfLimitIsLessThatZero(): void
    {
        $context = $this->createContext();
        $context->getLogger()->expects($this->once())
            ->method('debug')
            ->with('Message consumption is interrupted since the message limit reached. limit: "-1"');

        // guard
        $this->assertFalse($context->isExecutionInterrupted());

        // test
        $extension = new LimitConsumedMessagesExtension(-1);

        // consume 1
        $extension->onBeforeReceive($context);
        $this->assertTrue($context->isExecutionInterrupted());
    }

    public function testOnPostReceivedShouldInterruptExecutionIfMessageLimitExceeded(): void
    {
        $context = $this->createContext();
        $context->getLogger()->expects($this->once())
            ->method('debug')
            ->with('Message consumption is interrupted since the message limit reached. limit: "2"');

        // guard
        $this->assertFalse($context->isExecutionInterrupted());

        // test
        $extension = new LimitConsumedMessagesExtension(2);

        // consume 1
        $extension->onPostReceived($context);
        $this->assertFalse($context->isExecutionInterrupted());

        // consume 2 and exit
        $extension->onPostReceived($context);
        $this->assertTrue($context->isExecutionInterrupted());
    }

    /**
     * @return Context
     */
    protected function createContext()
    {
        $context = new Context($this->createMock(SessionInterface::class));
        $context->setLogger($this->createMock(LoggerInterface::class));
        $context->setMessageConsumer($this->createMock(MessageConsumerInterface::class));
        $context->setMessageProcessorName('sample_processor');

        return $context;
    }
}
