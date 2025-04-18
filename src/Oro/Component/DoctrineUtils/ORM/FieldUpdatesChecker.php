<?php

namespace Oro\Component\DoctrineUtils\ORM;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Check if entity field has changes.
 */
class FieldUpdatesChecker
{
    use ChangedEntityGeneratorTrait;

    protected PropertyAccessorInterface $propertyAccessor;
    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    public function __construct(ManagerRegistry $managerRegistry, PropertyAccessorInterface $propertyAccessor)
    {
        $this->managerRegistry = $managerRegistry;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * @param object $entity
     * @param string $fieldName
     *
     * @return bool
     */
    public function isRelationFieldChanged($entity, $fieldName)
    {
        $field = $this->propertyAccessor->getValue($entity, $fieldName);

        if ($field instanceof Collection) {
            foreach ($field as $fieldElement) {
                if ($this->inChangedEntities($fieldElement)) {
                    return true;
                }
            }
        } elseif ($this->inChangedEntities($field)) {
            return true;
        }

        return false;
    }

    /**
     * @return UnitOfWork
     */
    private function getUnitOfWork()
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->managerRegistry->getManager();

        return $entityManager->getUnitOfWork();
    }

    /**
     * @param object $entity
     * @return bool
     */
    private function inChangedEntities($entity)
    {
        foreach ($this->getChangedEntities($this->getUnitOfWork()) as $changedEntity) {
            if (spl_object_hash($changedEntity) === spl_object_hash($entity)) {
                return true;
            }
        }

        return false;
    }
}
