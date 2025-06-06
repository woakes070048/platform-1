<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Provider\ConfigCache;
use Oro\Bundle\ApiBundle\Provider\EntityAliasProvider;
use Oro\Bundle\EntityBundle\Model\EntityAlias;
use PHPUnit\Framework\TestCase;

class EntityAliasProviderTest extends TestCase
{
    private EntityAliasProvider $entityAliasProvider;

    #[\Override]
    protected function setUp(): void
    {
        $configCache = $this->createMock(ConfigCache::class);
        $configCache->expects(self::once())
            ->method('getAliases')
            ->willReturn([
                'Test\Entity1' => [
                    'alias'        => 'entity1',
                    'plural_alias' => 'entity1_plural'
                ]
            ]);
        $configCache->expects(self::once())
            ->method('getExcludedEntities')
            ->willReturn(['Test\Entity2']);
        $this->entityAliasProvider = new EntityAliasProvider($configCache);
    }

    public function testGetClassNames(): void
    {
        self::assertEquals(['Test\Entity1'], $this->entityAliasProvider->getClassNames());
        // test that data is cached in memory
        self::assertEquals(['Test\Entity1'], $this->entityAliasProvider->getClassNames());
    }

    public function testGetEntityAliasForExistingEntity(): void
    {
        self::assertEquals(
            new EntityAlias('entity1', 'entity1_plural'),
            $this->entityAliasProvider->getEntityAlias('Test\Entity1')
        );
        // test that data is cached in memory
        self::assertEquals(
            new EntityAlias('entity1', 'entity1_plural'),
            $this->entityAliasProvider->getEntityAlias('Test\Entity1')
        );
    }

    public function testGetEntityAliasForExcludedEntity(): void
    {
        self::assertFalse($this->entityAliasProvider->getEntityAlias('Test\Entity2'));
        // test that data is cached in memory
        self::assertFalse($this->entityAliasProvider->getEntityAlias('Test\Entity2'));
    }

    public function testGetEntityAliasForNotExistingEntity(): void
    {
        self::assertNull($this->entityAliasProvider->getEntityAlias('Test\Entity3'));
        // test that data is cached in memory
        self::assertNull($this->entityAliasProvider->getEntityAlias('Test\Entity3'));
    }
}
