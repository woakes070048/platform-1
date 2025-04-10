<?php

namespace Oro\Bundle\OrganizationBundle\Event;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\ImportExportBundle\Event\StrategyEvent;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * Populates imported entities with organization if entity supports it.
 */
class ImportStrategyListener implements ImportStrategyListenerInterface
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var OwnershipMetadataProviderInterface */
    protected $ownershipMetadataProvider;

    /** @var Organization */
    protected $defaultOrganization;

    /** @var PropertyAccessor */
    protected $propertyAccessor;

    /** @var array */
    protected $organizationFieldByEntity = [];

    public function __construct(
        ManagerRegistry $registry,
        TokenAccessorInterface $tokenAccessor,
        OwnershipMetadataProviderInterface $ownershipMetadataProvider
    ) {
        $this->registry = $registry;
        $this->tokenAccessor = $tokenAccessor;
        $this->ownershipMetadataProvider = $ownershipMetadataProvider;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    #[\Override]
    public function onProcessAfter(StrategyEvent $event)
    {
        $entity = $event->getEntity();

        $organizationField = $this->getOrganizationField($entity);
        if (!$organizationField) {
            return;
        }

        $entityOrganization = $this->getPropertyAccessor()->getValue($entity, $organizationField);
        $tokenOrganization = $this->tokenAccessor->getOrganization();

        if ($entityOrganization) {
            /**
             * Do nothing in case if entity already have organization field value but this value was absent in item data
             * (the value of organization field was set to the entity before the import).
             */
            $data = $event->getContext()->getValue('itemData');
            if ($data && !array_key_exists($organizationField, $data)) {
                return;
            }

            /**
             * We should allow to set organization for entity only in anonymous mode then the token has no organization
             * (for example, console import).
             * If import process was executed not in anonymous mode (for example, grid's import),
             * current organization for entities should be set.
             */
            if (!$tokenOrganization
                || ($tokenOrganization && $entityOrganization->getId() == $this->tokenAccessor->getOrganizationId())
            ) {
                return;
            }
        }

        // By default, the token organization should be set as entity organization.
        $entityOrganization = $tokenOrganization;

        if (!$entityOrganization) {
            $entityOrganization = $this->getDefaultOrganization();
        }

        if (!$entityOrganization) {
            return;
        }

        $this->getPropertyAccessor()->setValue($entity, $organizationField, $entityOrganization);
    }

    #[\Override]
    public function onClear()
    {
        $this->defaultOrganization = null;
        $this->organizationFieldByEntity = [];
    }

    /**
     * @return PropertyAccessor
     */
    protected function getPropertyAccessor()
    {
        if (!$this->propertyAccessor) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->propertyAccessor;
    }

    /**
     * @return Organization|null
     */
    protected function getDefaultOrganization()
    {
        if (null === $this->defaultOrganization) {
            /** @var EntityRepository $entityRepository */
            $entityRepository = $this->registry->getRepository(Organization::class);
            $organizations = $entityRepository->createQueryBuilder('e')
                ->setMaxResults(2)
                ->getQuery()
                ->getResult();
            if (count($organizations) == 1) {
                $this->defaultOrganization = current($organizations);
            } else {
                $this->defaultOrganization = false;
            }
        }

        return $this->defaultOrganization ?: null;
    }

    /**
     * @param object $entity
     * @return string
     */
    protected function getOrganizationField($entity)
    {
        $entityName = ClassUtils::getClass($entity);
        if (!array_key_exists($entityName, $this->organizationFieldByEntity)) {
            $this->organizationFieldByEntity[$entityName] = $this->ownershipMetadataProvider
                ->getMetadata($entityName)
                ->getOrganizationFieldName();
        }

        return $this->organizationFieldByEntity[$entityName];
    }
}
