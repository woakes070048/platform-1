<?php

namespace Oro\Bundle\WorkflowBundle\Model\Filter;

use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowDefinitionRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Scope\WorkflowScopeManager;

class WorkflowDefinitionScopesRegistryFilter implements WorkflowDefinitionFilterInterface
{
    /** @var ScopeManager */
    private $scopeManager;

    /** @var ManagerRegistry */
    private $managerRegistry;

    public function __construct(ScopeManager $scopeManager, ManagerRegistry $managerRegistry)
    {
        $this->scopeManager = $scopeManager;
        $this->managerRegistry = $managerRegistry;
    }

    #[\Override]
    public function filter(Collection $workflowDefinitions)
    {
        $scopeAwareDefinitions = $workflowDefinitions->filter(
            function (WorkflowDefinition $workflowDefinition) {
                return $workflowDefinition->hasScopesConfig();
            }
        );

        if ($scopeAwareDefinitions->isEmpty()) {
            return $workflowDefinitions;
        }

        $scopeMatches = $this->getScopeMatches($scopeAwareDefinitions->getValues());

        foreach ($workflowDefinitions as $key => $workflowDefinition) {
            $name = $workflowDefinition->getName();
            if ($scopeAwareDefinitions->contains($workflowDefinition) && !in_array($name, $scopeMatches, true)) {
                $workflowDefinitions->remove($key);
            }
        }

        return $workflowDefinitions;
    }

    /**
     * @param array $workflowDefinitions
     * @return array
     */
    private function getScopeMatches(array $workflowDefinitions)
    {
        $scopeDefinitions = $this->getWorkflowDefinitionRepository()->getScopedByNames(
            $this->getNames($workflowDefinitions),
            $this->scopeManager->getCriteria(WorkflowScopeManager::SCOPE_TYPE)
        );

        return $this->getNames($scopeDefinitions);
    }

    /**
     * @param array $workflowDefinitions
     * @return array|string[]
     */
    private function getNames(array $workflowDefinitions)
    {
        return array_map(
            function (WorkflowDefinition $workflowDefinition) {
                return $workflowDefinition->getName();
            },
            $workflowDefinitions
        );
    }

    /**
     * @return WorkflowDefinitionRepository
     */
    private function getWorkflowDefinitionRepository()
    {
        return $this->managerRegistry
            ->getManagerForClass(WorkflowDefinition::class)
            ->getRepository(WorkflowDefinition::class);
    }
}
