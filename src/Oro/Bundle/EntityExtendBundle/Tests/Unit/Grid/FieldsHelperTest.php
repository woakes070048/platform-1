<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Grid;

use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Grid\FieldsHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;

class FieldsHelperTest extends \PHPUnit\Framework\TestCase
{
    private const ENTITY_CLASS = 'Test\Entity';
    private const FIELD_NAME = 'testFieldName';

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var FieldsHelper */
    private $helper;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $entityConfigProvider;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $extendConfigProvider;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $datagridConfigProvider;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $viewConfigProvider;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    #[\Override]
    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->helper = new FieldsHelper(
            $this->configManager,
            $this->featureChecker
        );

        $this->entityConfigProvider = $this->createMock(ConfigProvider::class);
        $this->extendConfigProvider = $this->createMock(ConfigProvider::class);
        $this->datagridConfigProvider = $this->createMock(ConfigProvider::class);
        $this->viewConfigProvider = $this->createMock(ConfigProvider::class);

        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->willReturnMap([
                ['entity', $this->entityConfigProvider],
                ['extend', $this->extendConfigProvider],
                ['datagrid', $this->datagridConfigProvider],
                ['view', $this->viewConfigProvider],
            ]);
    }

    public function testGetFieldsWithoutConfig()
    {
        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with(self::ENTITY_CLASS)
            ->willReturn(false);

        $this->configManager->expects($this->never())
            ->method('getProvider');

        $this->helper->getFields(self::ENTITY_CLASS);
    }

    public function testGetFields()
    {
        $fieldId = new FieldConfigId('entity', self::ENTITY_CLASS, self::FIELD_NAME, 'string');

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with(self::ENTITY_CLASS)
            ->willReturn(true);

        $this->entityConfigProvider->expects($this->once())
            ->method('getIds')
            ->with(self::ENTITY_CLASS)
            ->willReturn([$fieldId]);

        $extendConfig = new Config(new FieldConfigId('extend', self::ENTITY_CLASS, self::FIELD_NAME, 'string'));
        $extendConfig->set('owner', ExtendScope::OWNER_CUSTOM);
        $extendConfig->set('state', ExtendScope::STATE_ACTIVE);
        $extendConfig->set('is_deleted', false);

        $datagridConfig = new Config(new FieldConfigId('datagrid', self::ENTITY_CLASS, self::FIELD_NAME, 'string'));
        $datagridConfig->set('is_visible', DatagridScope::IS_VISIBLE_TRUE);

        $this->extendConfigProvider->expects($this->once())
            ->method('getConfigById')
            ->with($fieldId)
            ->willReturn($extendConfig);

        $this->datagridConfigProvider->expects($this->once())
            ->method('getConfigById')
            ->with($fieldId)
            ->willReturn($datagridConfig);

        $viewFieldConfig = new Config(
            new FieldConfigId('view', self::ENTITY_CLASS, self::FIELD_NAME, 'string')
        );

        $this->viewConfigProvider->expects($this->any())
            ->method('getConfig')
            ->with(self::ENTITY_CLASS, self::FIELD_NAME)
            ->willReturn($viewFieldConfig);

        $fields = $this->helper->getFields(self::ENTITY_CLASS);
        $this->assertEquals([$fieldId], $fields);
    }

    public function testGetFieldsWithWrongExtendConfig()
    {
        $fieldId = new FieldConfigId('entity', self::ENTITY_CLASS, self::FIELD_NAME, 'string');

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with(self::ENTITY_CLASS)
            ->willReturn(true);

        $this->entityConfigProvider->expects($this->once())
            ->method('getIds')
            ->with(self::ENTITY_CLASS)
            ->willReturn([$fieldId]);

        $extendConfig = new Config(new FieldConfigId('extend', self::ENTITY_CLASS, self::FIELD_NAME, 'string'));
        $extendConfig->set('owner', ExtendScope::OWNER_SYSTEM);
        $extendConfig->set('state', ExtendScope::STATE_ACTIVE);
        $extendConfig->set('is_deleted', false);

        $datagridConfig = new Config(new FieldConfigId('datagrid', self::ENTITY_CLASS, self::FIELD_NAME, 'string'));
        $datagridConfig->set('is_visible', DatagridScope::IS_VISIBLE_TRUE);

        $this->extendConfigProvider->expects($this->once())
            ->method('getConfigById')
            ->with($fieldId)
            ->willReturn($extendConfig);

        $this->datagridConfigProvider->expects($this->once())
            ->method('getConfigById')
            ->willReturn($datagridConfig);

        $this->viewConfigProvider->expects($this->never())
            ->method('getConfig');

        $fields = $this->helper->getFields(self::ENTITY_CLASS);
        $this->assertEquals([], $fields);
    }

    public function testGetFieldsWhenFeatureCheckerFalse()
    {
        $fieldId = new FieldConfigId('entity', self::ENTITY_CLASS, self::FIELD_NAME, 'string');

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with(self::ENTITY_CLASS)
            ->willReturn(true);

        $this->featureChecker->expects($this->once())
            ->method('isResourceEnabled')
            ->with(self::ENTITY_CLASS, 'entities')
            ->willReturn(false);

        $this->entityConfigProvider->expects($this->once())
            ->method('getIds')
            ->with(self::ENTITY_CLASS)
            ->willReturn([$fieldId]);

        $extendConfig = new Config(new FieldConfigId('extend', self::ENTITY_CLASS, self::FIELD_NAME, 'string'));
        $extendConfig->set('owner', ExtendScope::OWNER_SYSTEM);
        $extendConfig->set('state', ExtendScope::STATE_ACTIVE);
        $extendConfig->set('is_deleted', false);
        $extendConfig->set('target_entity', self::ENTITY_CLASS);

        $datagridConfig = new Config(new FieldConfigId('datagrid', self::ENTITY_CLASS, self::FIELD_NAME, 'string'));
        $datagridConfig->set('is_visible', DatagridScope::IS_VISIBLE_TRUE);

        $this->extendConfigProvider->expects($this->once())
            ->method('getConfigById')
            ->with($fieldId)
            ->willReturn($extendConfig);

        $this->datagridConfigProvider->expects($this->once())
            ->method('getConfigById')
            ->willReturn($datagridConfig);

        $this->viewConfigProvider->expects($this->never())
            ->method('getConfig');

        $fields = $this->helper->getFields(self::ENTITY_CLASS);
        $this->assertEquals([], $fields);
    }
}
