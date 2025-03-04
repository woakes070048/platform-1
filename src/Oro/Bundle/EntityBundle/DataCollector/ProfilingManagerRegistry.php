<?php

namespace Oro\Bundle\EntityBundle\DataCollector;

use Oro\Bundle\EntityBundle\ORM\OrmConfiguration;
use Oro\Bundle\EntityBundle\ORM\Registry as BaseRegistry;

/**
 * Adds profiling attributes to the configuration of all ORM managers.
 */
class ProfilingManagerRegistry extends BaseRegistry
{
    /** @var OrmLogger */
    private $logger;

    /** @var array|null */
    private $loggingHydrators;

    /**
     * Sets a profiling logger.
     */
    public function setProfilingLogger(OrmLogger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Sets logging hydrators are used for the profiling.
     */
    public function setLoggingHydrators(array $hydrators)
    {
        $this->loggingHydrators = $hydrators;
    }

    #[\Override]
    protected function initializeEntityManagerConfiguration(OrmConfiguration $configuration)
    {
        parent::initializeEntityManagerConfiguration($configuration);
        $configuration->setAttribute('OrmProfilingLogger', $this->logger);
        $configuration->setAttribute('LoggingHydrators', $this->loggingHydrators);
    }
}
