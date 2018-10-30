<?php

namespace Oro\Component\MessageQueue\Client;

use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Exception\MessageProcessorNotFoundException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Registry of all registered message queue processors
 */
class ContainerAwareMessageProcessorRegistry implements MessageProcessorRegistryInterface
{
    /** @var MessageProcessorInterface[] */
    private $processors;

    /** @var ContainerInterface */
    private $container;

    /**
     * @param MessageProcessorInterface[] $processors [processor name => processor service id, ...]
     * @param ContainerInterface          $container
     */
    public function __construct(array $processors, ContainerInterface $container)
    {
        $this->processors = $processors;
        $this->container = $container;
    }

    /**
     * @param string $processorName
     * @param string $serviceId
     */
    public function set($processorName, $serviceId)
    {
        $this->processors[$processorName] = $serviceId;
    }

    /**
     * {@inheritdoc}
     */
    public function get($processorName)
    {
        if (!isset($this->processors[$processorName])) {
            throw MessageProcessorNotFoundException::create($processorName);
        }

        $processor = $this->container->get($this->processors[$processorName]);

        if (!$processor instanceof MessageProcessorInterface) {
            throw new \LogicException(sprintf(
                'Invalid instance of message processor. expected: "%s", got: "%s"',
                MessageProcessorInterface::class,
                is_object($processor) ? get_class($processor) : gettype($processor)
            ));
        }

        return $processor;
    }
}
