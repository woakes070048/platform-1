<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Config\Id;

use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use PHPUnit\Framework\TestCase;

class EntityConfigIdTest extends TestCase
{
    public function testEntityConfigId(): void
    {
        $entityId = new EntityConfigId('testScope', 'Test\Class');

        $this->assertEquals('Test\Class', $entityId->getClassName());
        $this->assertEquals('testScope', $entityId->getScope());
        $this->assertEquals('entity_testScope_Test-Class', $entityId->toString());
    }

    public function testSerialize(): void
    {
        $entityId = new EntityConfigId('testScope', 'Test\Class');

        $this->assertEquals($entityId, unserialize(serialize($entityId)));
    }

    public function testSetState(): void
    {
        $entityId = EntityConfigId::__set_state(
            [
                'className' => 'Test\Class',
                'scope' => 'testScope',
            ]
        );
        $this->assertEquals('Test\Class', $entityId->getClassName());
        $this->assertEquals('testScope', $entityId->getScope());
    }
}
