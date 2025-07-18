<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Log\Handler;

use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Oro\Bundle\MessageQueueBundle\Log\Handler\VerbosityFilterHandler;
use Oro\Component\MessageQueue\Log\ConsumerState;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class VerbosityFilterHandlerTest extends TestCase
{
    private ConsumerState $consumerState;
    private OutputInterface&MockObject $output;
    private VerbosityFilterHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->consumerState = new ConsumerState();
        $this->output = $this->createMock(OutputInterface::class);

        $this->handler = new VerbosityFilterHandler($this->consumerState, new TestHandler());
    }

    public function testIsHandling(): void
    {
        $input = $this->createMock(InputInterface::class);
        $this->output->expects($this->exactly(6))
            ->method('getVerbosity')
            ->willReturn(OutputInterface::VERBOSITY_QUIET);
        $this->handler->onCommand(new ConsoleCommandEvent(null, $input, $this->output));

        $this->assertFalse($this->handler->isHandling(['level' => Logger::EMERGENCY]));
        $this->consumerState->startConsumption();
        $this->assertFalse($this->handler->isHandling(['level' => Logger::DEBUG]));
        $this->assertFalse($this->handler->isHandling(['level' => Logger::INFO]));
        $this->assertFalse($this->handler->isHandling(['level' => Logger::WARNING]));
        $this->assertTrue($this->handler->isHandling(['level' => Logger::ERROR]));
        $this->assertTrue($this->handler->isHandling(['level' => Logger::CRITICAL]));
        $this->assertTrue($this->handler->isHandling(['level' => Logger::EMERGENCY]));
    }
}
