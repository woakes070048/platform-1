<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Performance\Acl\Fixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class LoadAclData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * Load ACL Resource
     */
    #[\Override]
    public function load(ObjectManager $manager)
    {
        $loginAcl = $manager->getRepository('Oro\Bundle\UserBundle\Entity\Acl')
            ->findOneBy(array('id' => 'oro_login'))
            ->addAccessRole($this->getReference('login_access_role'));
        $manager->persist($loginAcl);

        $loginCheckAcl = $manager->getRepository('Oro\Bundle\UserBundle\Entity\Acl')
            ->findOneBy(array('id' => 'oro_login_check'))
            ->addAccessRole($this->getReference('login_access_role'));
        $manager->persist($loginCheckAcl);

        $fullAcl = $manager->getRepository('Oro\Bundle\UserBundle\Entity\Acl')
            ->findOneBy(array('id' => 'root'))
            ->addAccessRole($this->getReference('full_access_role'));
        $manager->persist($fullAcl);

        $manager->flush();
    }

    #[\Override]
    public function getOrder()
    {
        return 100;
    }
}
