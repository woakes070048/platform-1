<?php

namespace Oro\Bundle\SecurityBundle\Acl\Domain;

use Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException;
use Symfony\Component\Security\Acl\Model\ObjectIdentityRetrievalStrategyInterface;

/**
 * Strategy to be used for retrieving object identities
 */
class ObjectIdentityRetrievalStrategy implements ObjectIdentityRetrievalStrategyInterface
{
    /** @var ObjectIdentityFactory */
    protected $objectIdentityFactory;

    public function __construct(ObjectIdentityFactory $objectIdentityFactory)
    {
        $this->objectIdentityFactory = $objectIdentityFactory;
    }

    #[\Override]
    public function getObjectIdentity($domainObject)
    {
        if ($domainObject instanceof DomainObjectWrapper) {
            return $domainObject->getObjectIdentity();
        }

        try {
            return $this->objectIdentityFactory->get($domainObject);
        } catch (InvalidDomainObjectException $failed) {
            return null;
        }
    }
}
