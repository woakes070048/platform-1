<?php

namespace Oro\Bundle\ActivityBundle\Tests\Unit\Tools;

use Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope;
use Oro\Bundle\ActivityBundle\Tools\ActivityAssociationHelper;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ActivityAssociationHelperTest extends TestCase
{
    private ConfigManager&MockObject $configManager;
    private ActivityAssociationHelper $activityAssociationHelper;

    #[\Override]
    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->activityAssociationHelper = new ActivityAssociationHelper($this->configManager);
    }

    public function testIsActivityAssociationEnabledForNotConfigurableEntity(): void
    {
        $entityClass = 'Test\Entity';
        $activityClass = 'Test\Activity';

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(false);
        $this->configManager->expects($this->never())
            ->method('getEntityConfig');

        $this->assertFalse(
            $this->activityAssociationHelper->isActivityAssociationEnabled($entityClass, $activityClass)
        );
    }

    public function testIsActivityAssociationEnabledForDisabledAssociation(): void
    {
        $entityClass = 'Test\Entity';
        $activityClass = 'Test\Activity';

        $config = new Config(new EntityConfigId('activity', $entityClass));

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects($this->once())
            ->method('getEntityConfig')
            ->with('activity', $entityClass)
            ->willReturn($config);

        $this->assertFalse(
            $this->activityAssociationHelper->isActivityAssociationEnabled($entityClass, $activityClass)
        );
    }

    public function testIsActivityAssociationEnabledForEnabledAssociation(): void
    {
        $entityClass = 'Test\Entity';
        $activityClass = 'Test\Activity';

        $config = new Config(new EntityConfigId('activity', $entityClass));
        $config->set('activities', [$activityClass]);

        $associationName = ExtendHelper::buildAssociationName($entityClass, ActivityScope::ASSOCIATION_KIND);
        $associationConfig = new Config(
            new FieldConfigId('extend', $activityClass, $associationName)
        );
        $associationConfig->set('is_extend', true);
        $associationConfig->set('state', ExtendScope::STATE_ACTIVE);

        $this->configManager->expects($this->exactly(2))
            ->method('hasConfig')
            ->willReturnMap([
                [$entityClass, null, true],
                [$activityClass, $associationName, true],
            ]);
        $this->configManager->expects($this->once())
            ->method('getEntityConfig')
            ->with('activity', $entityClass)
            ->willReturn($config);
        $this->configManager->expects($this->once())
            ->method('getFieldConfig')
            ->with('extend', $activityClass, $associationName)
            ->willReturn($associationConfig);

        $this->assertTrue(
            $this->activityAssociationHelper->isActivityAssociationEnabled($entityClass, $activityClass)
        );
    }

    public function testIsActivityAssociationEnabledForEnabledButNotAccessibleAssociation(): void
    {
        $entityClass = 'Test\Entity';
        $activityClass = 'Test\Activity';

        $config = new Config(new EntityConfigId('activity', $entityClass));
        $config->set('activities', [$activityClass]);

        $associationName = ExtendHelper::buildAssociationName($entityClass, ActivityScope::ASSOCIATION_KIND);
        $associationConfig = new Config(
            new FieldConfigId('extend', $activityClass, $associationName)
        );
        $associationConfig->set('is_extend', true);
        $associationConfig->set('state', ExtendScope::STATE_NEW);

        $this->configManager->expects($this->exactly(2))
            ->method('hasConfig')
            ->willReturnMap([
                [$entityClass, null, true],
                [$activityClass, $associationName, true],
            ]);
        $this->configManager->expects($this->once())
            ->method('getEntityConfig')
            ->with('activity', $entityClass)
            ->willReturn($config);
        $this->configManager->expects($this->once())
            ->method('getFieldConfig')
            ->with('extend', $activityClass, $associationName)
            ->willReturn($associationConfig);

        $this->assertFalse(
            $this->activityAssociationHelper->isActivityAssociationEnabled($entityClass, $activityClass)
        );
    }

    public function testIsActivityAssociationEnabledForEnabledButNotAccessibleAssociationButWithAccessibleFalse(): void
    {
        $entityClass = 'Test\Entity';
        $activityClass = 'Test\Activity';

        $config = new Config(new EntityConfigId('activity', $entityClass));
        $config->set('activities', [$activityClass]);

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects($this->once())
            ->method('getEntityConfig')
            ->with('activity', $entityClass)
            ->willReturn($config);
        $this->configManager->expects($this->never())
            ->method('getFieldConfig');

        $this->assertTrue(
            $this->activityAssociationHelper->isActivityAssociationEnabled($entityClass, $activityClass, false)
        );
    }

    public function testHasActivityAssociationsForNotConfigurableEntity(): void
    {
        $entityClass = 'Test\Entity';

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(false);
        $this->configManager->expects($this->never())
            ->method('getEntityConfig');

        $this->assertFalse(
            $this->activityAssociationHelper->hasActivityAssociations($entityClass)
        );
    }

    public function testHasActivityAssociations(): void
    {
        $entityClass = 'Test\Entity';
        $activity1Class = 'Test\Activity1';
        $activity2Class = 'Test\Activity2';

        $config = new Config(new EntityConfigId('activity', $entityClass));
        $config->set('activities', [$activity1Class, $activity2Class]);

        $associationName = ExtendHelper::buildAssociationName($entityClass, ActivityScope::ASSOCIATION_KIND);

        $association1Config = new Config(
            new FieldConfigId('extend', $activity1Class, $associationName)
        );
        $association1Config->set('is_extend', true);
        $association1Config->set('state', ExtendScope::STATE_NEW);

        $association2Config = new Config(
            new FieldConfigId('extend', $activity2Class, $associationName)
        );
        $association2Config->set('is_extend', true);
        $association2Config->set('state', ExtendScope::STATE_ACTIVE);

        $this->configManager->expects($this->exactly(3))
            ->method('hasConfig')
            ->with()
            ->willReturnMap([
                [$entityClass, null, true],
                [$activity1Class, $associationName, true],
                [$activity2Class, $associationName, true],
            ]);
        $this->configManager->expects($this->once())
            ->method('getEntityConfig')
            ->with('activity', $entityClass)
            ->willReturn($config);
        $this->configManager->expects($this->exactly(2))
            ->method('getFieldConfig')
            ->willReturnMap([
                ['extend', $activity1Class, $associationName, $association1Config],
                ['extend', $activity2Class, $associationName, $association2Config],
            ]);

        $this->assertTrue(
            $this->activityAssociationHelper->hasActivityAssociations($entityClass)
        );
    }
}
