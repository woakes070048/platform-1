<?php

namespace Oro\Bundle\ScopeBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ScopeBundle\Entity\Scope;

class LoadScopeData extends AbstractFixture
{
    const DEFAULT_SCOPE = 'default_scope';

    #[\Override]
    public function load(ObjectManager $manager)
    {
        $manager->getRepository(Scope::class)
            ->createQueryBuilder('s')
            ->delete()
            ->getQuery()
            ->execute();

        $scope = new Scope();
        $manager->persist($scope);
        $manager->flush();
        $this->setReference(self::DEFAULT_SCOPE, $scope);
    }
}
