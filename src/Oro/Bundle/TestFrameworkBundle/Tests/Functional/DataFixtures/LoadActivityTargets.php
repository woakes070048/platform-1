<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivityTarget;

class LoadActivityTargets extends AbstractFixture
{
    #[\Override]
    public function load(ObjectManager $manager)
    {
        $targetOne = new TestActivityTarget();
        $this->addReference('activity_target_one', $targetOne);
        $manager->persist($targetOne);

        $manager->flush();
    }
}
