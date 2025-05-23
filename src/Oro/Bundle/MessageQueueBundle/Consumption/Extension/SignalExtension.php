<?php

namespace Oro\Bundle\MessageQueueBundle\Consumption\Extension;

use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\Exception\LogicException;
use Psr\Log\LoggerInterface;

/**
 * Pending to SIGTERM, SIGQUIT or SIGINT signal on before receive, post receive or on idle steps,
 * if signal was received, interrupt consumer execution.
 * Optional extension, will be enabled only if php pcntl extension is available.
 */
class SignalExtension extends AbstractExtension
{
    /** @var bool */
    protected $interruptConsumption = false;

    /** @var LoggerInterface */
    protected $logger;

    #[\Override]
    public function onStart(Context $context)
    {
        if (!extension_loaded('pcntl')) {
            throw new LogicException('The pcntl extension is required in order to catch signals.');
        }

        $this->logger = $context->getLogger();

        pcntl_signal(SIGTERM, [$this, 'handleSignal']);
        pcntl_signal(SIGQUIT, [$this, 'handleSignal']);
        pcntl_signal(SIGINT, [$this, 'handleSignal']);

        $this->interruptConsumption = false;
    }

    #[\Override]
    public function onBeforeReceive(Context $context)
    {
        pcntl_signal_dispatch();

        $this->interruptExecutionIfNeeded($context);
    }

    #[\Override]
    public function onPostReceived(Context $context)
    {
        pcntl_signal_dispatch();

        $this->interruptExecutionIfNeeded($context);
    }

    #[\Override]
    public function onIdle(Context $context)
    {
        pcntl_signal_dispatch();

        $this->interruptExecutionIfNeeded($context);
    }

    protected function interruptExecutionIfNeeded(Context $context)
    {
        if ($this->interruptConsumption && !$context->isExecutionInterrupted()) {
            $this->logger->debug('Interrupt execution');
            $context->setExecutionInterrupted($this->interruptConsumption);
            $context->setInterruptedReason('Interrupt execution.');

            $this->interruptConsumption = false;
        }
    }

    /**
     * @param int $signal
     */
    public function handleSignal($signal)
    {
        if ($this->logger) {
            $this->logger->debug(sprintf('Caught signal: %s', $signal));
        }

        switch ($signal) {
            case SIGTERM:  // 15 : supervisor default stop
            case SIGQUIT:  // 3  : kill -s QUIT
            case SIGINT:   // 2  : ctrl+c
                $this->logger->debug('Interrupt consumption');
                $this->interruptConsumption = true;
                break;
            default:
                break;
        }
    }
}
