<?php

namespace Oro\Bundle\ImportExportBundle\Processor;

use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\BatchBundle\Item\Support\ClosableInterface;
use Oro\Bundle\BatchBundle\Step\StepExecutionAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\ImportExportBundle\Exception\LogicException;

/**
 * Import/export batch job processor that is aware of other processors, delegates processing to the most suitable one.
 */
class RegistryDelegateProcessor implements ProcessorInterface, StepExecutionAwareInterface, ClosableInterface
{
    /**
     * @var ProcessorRegistry
     */
    protected $processorRegistry;

    /**
     * @var string
     */
    protected $delegateType;

    /**
     * @var ContextRegistry
     */
    protected $contextRegistry;

    /**
     * @var StepExecution
     */
    protected $stepExecution;

    /**
     * @param ProcessorRegistry $processorRegistry
     * @param string $delegateType
     * @param ContextRegistry $contextRegistry
     */
    public function __construct(ProcessorRegistry $processorRegistry, $delegateType, ContextRegistry $contextRegistry)
    {
        $this->processorRegistry = $processorRegistry;
        $this->delegateType = $delegateType;
        $this->contextRegistry = $contextRegistry;
    }

    #[\Override]
    public function process($item)
    {
        return $this->getDelegateProcessor()->process($item);
    }

    /**
     * @return ProcessorInterface
     * @throws InvalidConfigurationException
     * @throws LogicException
     */
    protected function getDelegateProcessor()
    {
        if (!$this->stepExecution) {
            throw new LogicException('Step execution entity must be injected to processor.');
        }
        $context = $this->contextRegistry->getByStepExecution($this->stepExecution);
        if (!$context->getOption('processorAlias')) {
            throw new InvalidConfigurationException(
                'Configuration of processor must contain "processorAlias" options.'
            );
        }

        $result = $this->processorRegistry->getProcessor(
            $this->delegateType,
            $context->getOption('processorAlias')
        );

        if ($result instanceof ContextAwareInterface) {
            $result->setImportExportContext($context);
        }

        if ($result instanceof StepExecutionAwareInterface) {
            $result->setStepExecution(
                $this->stepExecution
            );
        }

        return $result;
    }

    #[\Override]
    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->stepExecution = $stepExecution;
    }

    #[\Override]
    public function close()
    {
        $delegateProcessor = $this->getDelegateProcessor();
        if ($delegateProcessor instanceof ClosableInterface) {
            $delegateProcessor->close();
        }
    }
}
