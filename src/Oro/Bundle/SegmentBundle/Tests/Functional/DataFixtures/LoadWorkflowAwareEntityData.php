<?php

namespace Oro\Bundle\SegmentBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;

class LoadWorkflowAwareEntityData extends AbstractFixture
{
    const COUNT = 50;

    #[\Override]
    public function load(ObjectManager $manager)
    {
        for ($i = 1; $i <= self::COUNT; $i++) {
            $entity = new WorkflowAwareEntity();

            $name = 'entity_' . $i;
            $entity->setName($name);

            $this->setReference('workflow_aware_' . $name, $entity);

            $manager->persist($entity);
        }

        $manager->flush();
    }
}
