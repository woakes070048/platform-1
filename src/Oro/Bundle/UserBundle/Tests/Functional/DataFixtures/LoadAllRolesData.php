<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\UserBundle\Entity\Role;

class LoadAllRolesData extends AbstractFixture
{
    #[\Override]
    public function load(ObjectManager $manager)
    {
        $roleRepo = $manager->getRepository(Role::class);
        foreach ($roleRepo->findAll() as $role) {
            $this->setReference(sprintf('role.%s', strtolower($role->getRole())), $role);
        }
    }
}
