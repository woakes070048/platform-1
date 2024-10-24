<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;

/**
 * The implementation of ExclusionProviderInterface that can be used to ignore
 * entities which do not have aliases.
 */
class AliasedEntityExclusionProvider extends AbstractExclusionProvider
{
    /** @var EntityAliasResolver */
    protected $entityAliasResolver;

    public function __construct(EntityAliasResolver $entityAliasResolver)
    {
        $this->entityAliasResolver = $entityAliasResolver;
    }

    #[\Override]
    public function isIgnoredEntity($className)
    {
        return !$this->entityAliasResolver->hasAlias($className);
    }
}
