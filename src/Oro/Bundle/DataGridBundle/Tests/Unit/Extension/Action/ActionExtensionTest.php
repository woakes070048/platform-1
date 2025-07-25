<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Action;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Extension\Action\ActionExtension;
use Oro\Bundle\DataGridBundle\Extension\Action\ActionFactory;
use Oro\Bundle\DataGridBundle\Extension\Action\ActionMetadataFactory;
use Oro\Bundle\DataGridBundle\Extension\Action\Actions\ActionInterface;
use Oro\Bundle\DataGridBundle\Extension\Action\DatagridActionProviderInterface;
use Oro\Bundle\DataGridBundle\Provider\DatagridModeProvider;
use Oro\Bundle\SecurityBundle\Acl\Domain\DomainObjectReference;
use Oro\Bundle\SecurityBundle\Owner\OwnershipQueryHelper;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ActionExtensionTest extends TestCase
{
    private DatagridActionProviderInterface&MockObject $actionProvider;
    private ActionFactory&MockObject $actionFactory;
    private ActionMetadataFactory&MockObject $actionMetadataFactory;
    private AuthorizationCheckerInterface&MockObject $authorizationChecker;
    private OwnershipQueryHelper&MockObject $ownershipQueryHelper;
    private ActionExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->actionProvider = $this->createMock(DatagridActionProviderInterface::class);
        $this->actionFactory = $this->createMock(ActionFactory::class);
        $this->actionMetadataFactory = $this->createMock(ActionMetadataFactory::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->ownershipQueryHelper = $this->createMock(OwnershipQueryHelper::class);

        $this->extension = new ActionExtension(
            [$this->actionProvider],
            $this->actionFactory,
            $this->actionMetadataFactory,
            $this->authorizationChecker,
            $this->ownershipQueryHelper
        );
        $this->extension->setParameters(new ParameterBag());
    }

    public function testIsApplicableWithoutEnableActionsParameter(): void
    {
        $config = DatagridConfiguration::create([]);

        self::assertTrue($this->extension->isApplicable($config));
    }

    public function testIsNotApplicableInImportExportMode(): void
    {
        $params = new ParameterBag();
        $params->set(
            ParameterBag::DATAGRID_MODES_PARAMETER,
            [DatagridModeProvider::DATAGRID_IMPORTEXPORT_MODE]
        );
        $config = DatagridConfiguration::create([]);
        $this->extension->setParameters($params);
        self::assertFalse($this->extension->isApplicable($config));
    }

    public function testIsNotApplicableWhenExportMode(): void
    {
        $config = DatagridConfiguration::create([]);
        $this->extension->getParameters()->set(
            ParameterBag::DATAGRID_MODES_PARAMETER,
            [DatagridModeProvider::DATAGRID_IMPORTEXPORT_MODE]
        );

        self::assertFalse($this->extension->isApplicable($config));
    }

    public function testProcessConfigs(): void
    {
        $config = DatagridConfiguration::create([]);

        $this->actionProvider->expects(self::once())
            ->method('hasActions')
            ->with($config)
            ->willReturn(true);
        $this->actionProvider->expects(self::once())
            ->method('applyActions')
            ->with($config);

        $this->extension->processConfigs($config);
    }

    public function testVisitMetadataWithoutActions(): void
    {
        $config = DatagridConfiguration::create([]);
        $metadata = MetadataObject::create([]);

        $this->extension->visitMetadata($config, $metadata);

        self::assertEquals(
            [
                'rowActions' => []
            ],
            $metadata->toArray()
        );
    }

    public function testVisitMetadataWithActions(): void
    {
        $actionName = 'action1';
        $actionConfig = ['type' => 'type1'];
        $actionMetadata = ['type' => 'type1', 'label' => 'label1'];

        $config = DatagridConfiguration::create(
            [
                'actions' => [
                    $actionName => $actionConfig
                ]
            ]
        );
        $metadata = MetadataObject::create([]);

        $action = $this->createMock(ActionInterface::class);
        $action->expects(self::once())
            ->method('getName')
            ->willReturn($actionName);
        $action->expects(self::once())
            ->method('getAclResource')
            ->willReturn(null);

        $this->actionFactory->expects(self::once())
            ->method('createAction')
            ->with($actionName, $actionConfig)
            ->willReturn($action);
        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');
        $this->actionMetadataFactory->expects(self::once())
            ->method('createActionMetadata')
            ->with(self::identicalTo($action))
            ->willReturn($actionMetadata);

        $this->extension->visitMetadata($config, $metadata);

        self::assertEquals(
            [
                'rowActions' => [
                    $actionName => $actionMetadata
                ]
            ],
            $metadata->toArray()
        );
    }

    public function testVisitMetadataWithAclProtectedActionAndAccessGranted(): void
    {
        $actionName = 'action1';
        $actionConfig = ['type' => 'type1', 'acl_resource' => 'acl_resource1'];
        $actionMetadata = ['type' => 'type1', 'label' => 'label1'];

        $config = DatagridConfiguration::create(
            [
                'actions' => [
                    $actionName => $actionConfig
                ]
            ]
        );
        $metadata = MetadataObject::create([]);

        $action = $this->createMock(ActionInterface::class);
        $action->expects(self::once())
            ->method('getName')
            ->willReturn($actionName);
        $action->expects(self::once())
            ->method('getAclResource')
            ->willReturn($actionConfig['acl_resource']);

        $this->actionFactory->expects(self::once())
            ->method('createAction')
            ->with($actionName, $actionConfig)
            ->willReturn($action);
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with($actionConfig['acl_resource'])
            ->willReturn(true);
        $this->actionMetadataFactory->expects(self::once())
            ->method('createActionMetadata')
            ->with(self::identicalTo($action))
            ->willReturn($actionMetadata);

        $this->extension->visitMetadata($config, $metadata);

        self::assertEquals(
            [
                'rowActions' => [
                    $actionName => $actionMetadata
                ]
            ],
            $metadata->toArray()
        );
    }

    public function testVisitMetadataWithAclProtectedActionAndAccessDenied(): void
    {
        $actionName = 'action1';
        $actionConfig = ['type' => 'type1', 'acl_resource' => 'acl_resource1'];

        $config = DatagridConfiguration::create(
            [
                'actions' => [
                    $actionName => $actionConfig
                ]
            ]
        );
        $metadata = MetadataObject::create([]);

        $action = $this->createMock(ActionInterface::class);
        $action->expects(self::once())
            ->method('getAclResource')
            ->willReturn($actionConfig['acl_resource']);

        $this->actionFactory->expects(self::once())
            ->method('createAction')
            ->with($actionName, $actionConfig)
            ->willReturn($action);
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with($actionConfig['acl_resource'])
            ->willReturn(false);
        $this->actionMetadataFactory->expects(self::never())
            ->method('createActionMetadata');

        $this->extension->visitMetadata($config, $metadata);

        self::assertEquals(
            [
                'rowActions' => []
            ],
            $metadata->toArray()
        );
    }

    public function testVisitResultWithoutActions(): void
    {
        $config = DatagridConfiguration::create([]);
        $result = ResultsObject::create([]);

        $this->extension->visitResult($config, $result);

        self::assertArrayHasKey('metadata', $result);
        self::assertEquals(
            [
                'rowActions' => []
            ],
            $result['metadata']
        );
    }

    public function testVisitResultWithActions(): void
    {
        $actionName = 'action1';
        $actionConfig = ['type' => 'type1'];
        $actionMetadata = ['type' => 'type1', 'label' => 'label1'];

        $config = DatagridConfiguration::create(
            [
                'actions' => [
                    $actionName => $actionConfig
                ]
            ]
        );
        $result = ResultsObject::create([]);

        $action = $this->createMock(ActionInterface::class);
        $action->expects(self::once())
            ->method('getName')
            ->willReturn($actionName);
        $action->expects(self::once())
            ->method('getAclResource')
            ->willReturn(null);

        $this->actionFactory->expects(self::once())
            ->method('createAction')
            ->with($actionName, $actionConfig)
            ->willReturn($action);
        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');
        $this->actionMetadataFactory->expects(self::once())
            ->method('createActionMetadata')
            ->with(self::identicalTo($action))
            ->willReturn($actionMetadata);

        $this->extension->visitResult($config, $result);

        self::assertArrayHasKey('metadata', $result);
        self::assertEquals(
            [
                'rowActions' => [
                    $actionName => $actionMetadata
                ]
            ],
            $result['metadata']
        );
    }

    public function testVisitResultWithAclProtectedActionAndAccessGranted(): void
    {
        $actionName = 'action1';
        $actionConfig = ['type' => 'type1', 'acl_resource' => 'acl_resource1'];
        $actionMetadata = ['type' => 'type1', 'label' => 'label1'];

        $config = DatagridConfiguration::create(
            [
                'actions' => [
                    $actionName => $actionConfig
                ]
            ]
        );
        $result = ResultsObject::create([]);

        $action = $this->createMock(ActionInterface::class);
        $action->expects(self::once())
            ->method('getName')
            ->willReturn($actionName);
        $action->expects(self::once())
            ->method('getAclResource')
            ->willReturn($actionConfig['acl_resource']);

        $this->actionFactory->expects(self::once())
            ->method('createAction')
            ->with($actionName, $actionConfig)
            ->willReturn($action);
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with($actionConfig['acl_resource'])
            ->willReturn(true);
        $this->actionMetadataFactory->expects(self::once())
            ->method('createActionMetadata')
            ->with(self::identicalTo($action))
            ->willReturn($actionMetadata);

        $this->extension->visitResult($config, $result);

        self::assertArrayHasKey('metadata', $result);
        self::assertEquals(
            [
                'rowActions' => [
                    $actionName => $actionMetadata
                ]
            ],
            $result['metadata']
        );
    }

    public function testVisitResultWithAclProtectedActionAndAccessDenied(): void
    {
        $actionName = 'action1';
        $actionConfig = ['type' => 'type1', 'acl_resource' => 'acl_resource1'];

        $config = DatagridConfiguration::create(
            [
                'actions' => [
                    $actionName => $actionConfig
                ]
            ]
        );
        $result = ResultsObject::create([]);

        $action = $this->createMock(ActionInterface::class);
        $action->expects(self::once())
            ->method('getAclResource')
            ->willReturn($actionConfig['acl_resource']);

        $this->actionFactory->expects(self::once())
            ->method('createAction')
            ->with($actionName, $actionConfig)
            ->willReturn($action);
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with($actionConfig['acl_resource'])
            ->willReturn(false);
        $this->actionMetadataFactory->expects(self::never())
            ->method('createActionMetadata');

        $this->extension->visitResult($config, $result);

        self::assertArrayHasKey('metadata', $result);
        self::assertEquals(
            [
                'rowActions' => []
            ],
            $result['metadata']
        );
    }

    public function testVisitResultAfterVisitMetadata(): void
    {
        $actionName = 'action1';
        $actionConfig = ['type' => 'type1'];
        $actionMetadata = ['type' => 'type1', 'label' => 'label1'];

        $config = DatagridConfiguration::create(
            [
                'actions' => [
                    $actionName => $actionConfig
                ]
            ]
        );
        $metadata = MetadataObject::create([]);
        $result = ResultsObject::create([]);

        $action = $this->createMock(ActionInterface::class);
        $action->expects(self::once())
            ->method('getName')
            ->willReturn($actionName);
        $action->expects(self::once())
            ->method('getAclResource')
            ->willReturn(null);

        $this->actionFactory->expects(self::once())
            ->method('createAction')
            ->with($actionName, $actionConfig)
            ->willReturn($action);
        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');
        $this->actionMetadataFactory->expects(self::once())
            ->method('createActionMetadata')
            ->with(self::identicalTo($action))
            ->willReturn($actionMetadata);

        $this->extension->visitMetadata($config, $metadata);
        $this->extension->visitResult($config, $result);

        self::assertArrayNotHasKey('metadata', $result);
        self::assertEquals(
            [
                'rowActions' => [
                    $actionName => $actionMetadata
                ]
            ],
            $metadata->toArray()
        );
    }

    public function testVisitDatasourceForNotOrmDatasource(): void
    {
        $config = DatagridConfiguration::create(
            [
                'actions' => [
                    'action1' => ['type' => 'type1', 'acl_resource' => 'acl_resource1']
                ]
            ]
        );
        $datasource = $this->createMock(DatasourceInterface::class);

        $this->extension->visitDatasource($config, $datasource);
        $this->expectNotToPerformAssertions();
    }

    public function testVisitDatasourceForOrmDatasource(): void
    {
        $config = DatagridConfiguration::create(
            [
                'actions' => [
                    'action1' => ['type' => 'type1', 'acl_resource' => 'acl_resource1']
                ]
            ]
        );
        $datasource = $this->createMock(OrmDatasource::class);
        $qb = $this->createMock(QueryBuilder::class);
        $ownershipFields = ['e' => []];

        $datasource->expects(self::once())
            ->method('getQueryBuilder')
            ->willReturn($qb);
        $this->ownershipQueryHelper->expects(self::once())
            ->method('addOwnershipFields')
            ->with(self::identicalTo($qb))
            ->willReturn($ownershipFields);

        $this->extension->visitDatasource($config, $datasource);

        self::assertEquals($ownershipFields, ReflectionUtil::getPropertyValue($this->extension, 'ownershipFields'));
    }

    public function testVisitDatasourceForOrmDatasourceButNoAclProtectedActions(): void
    {
        $config = DatagridConfiguration::create(
            [
                'actions' => [
                    'action1' => ['type' => 'type1']
                ]
            ]
        );
        $datasource = $this->createMock(OrmDatasource::class);

        $this->ownershipQueryHelper->expects(self::never())
            ->method('addOwnershipFields');

        $this->extension->visitDatasource($config, $datasource);
    }

    private function setOwnershipFields(array $ownershipFields)
    {
        // set ownership fields
        ReflectionUtil::setPropertyValue($this->extension, 'ownershipFields', $ownershipFields);
        // skip load actions metadata
        ReflectionUtil::setPropertyValue($this->extension, 'isMetadataVisited', true);
    }

    private function getActionConfigurationOption(DatagridConfiguration $config): mixed
    {
        return $config->offsetGetByPath('[properties][action_configuration]');
    }

    public function testVisitResultWithOwnershipFieldsForActionWithoutAclResource(): void
    {
        $config = DatagridConfiguration::create([
            'actions' => [
                'view' => ['type' => 'viewAction']
            ],
        ]);
        $records = [
            new ResultRecord(['id' => 1, 't_owner_id' => 123, 't_organization_id' => 456])
        ];

        $this->actionFactory->expects(self::never())
            ->method('createAction');
        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');

        $this->setOwnershipFields(
            ['t' => ['Test\Entity', 'id', 't_organization_id', 't_owner_id']]
        );
        $this->extension->visitResult($config, ResultsObject::create(['data' => $records]));

        $actionConfiguration = $this->getActionConfigurationOption($config);
        self::assertNull($actionConfiguration);
    }

    public function testVisitResultWithOwnershipFieldsForActionWithAclResourceAndAccessDenied(): void
    {
        $config = DatagridConfiguration::create([
            'actions' => [
                'update' => ['type' => 'updateAction', 'acl_resource' => 'update_acl_resource'],
            ],
        ]);
        $records = [
            new ResultRecord(['id' => 1, 't_owner_id' => 123, 't_organization_id' => 456]),
        ];

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->willReturnCallback(function ($resource, ?DomainObjectReference $object = null) {
                self::assertEquals('update_acl_resource', $resource);
                self::assertInstanceOf(DomainObjectReference::class, $object);
                self::assertSame('Test\Entity', $object->getType());
                self::assertSame(1, $object->getIdentifier());
                self::assertSame(123, $object->getOwnerId());
                self::assertSame(456, $object->getOrganizationId());

                return false;
            });

        $this->setOwnershipFields(
            ['t' => ['Test\Entity', 'id', 't_organization_id', 't_owner_id']]
        );
        $this->extension->visitResult($config, ResultsObject::create(['data' => $records]));

        $actionConfiguration = $this->getActionConfigurationOption($config);
        self::assertNotNull($actionConfiguration);
        self::assertArrayHasKey('callable', $actionConfiguration);
        foreach ($records as $record) {
            $result = call_user_func($actionConfiguration['callable'], $record);
            self::assertEquals(['update' => false], $result);
        }
    }

    public function testVisitResultForActionsWithDuplicatedAclResources(): void
    {
        $config = DatagridConfiguration::create([
            'actions' => [
                'action1' => ['type' => 'type1', 'acl_resource' => 'acl_resource1'],
                'action2' => ['type' => 'type2', 'acl_resource' => 'acl_resource1'],
                'action3' => ['type' => 'type3', 'acl_resource' => 'acl_resource3']
            ],
        ]);
        $records = [
            new ResultRecord(['id' => 1, 't_owner_id' => 123, 't_organization_id' => 456]),
        ];

        $this->authorizationChecker->expects($this->exactly(2))
            ->method('isGranted')
            ->withConsecutive(
                ['acl_resource1'],
                ['acl_resource3']
            )
            ->willReturn(false);

        $this->setOwnershipFields(
            ['t' => ['Test\Entity', 'id', 't_organization_id', 't_owner_id']]
        );
        $this->extension->visitResult($config, ResultsObject::create(['data' => $records]));

        $actionConfiguration = $this->getActionConfigurationOption($config);
        $this->assertNotNull($actionConfiguration);
        $this->assertArrayHasKey('callable', $actionConfiguration);
        foreach ($records as $record) {
            $result = call_user_func($actionConfiguration['callable'], $record);
            $this->assertEquals(['action1' => false, 'action2' => false, 'action3' => false], $result);
        }
    }

    public function testVisitResultWithOwnershipFieldsForActionWithAclResourceAndAccessGranted(): void
    {
        $config = DatagridConfiguration::create([
            'actions' => [
                'update' => ['type' => 'updateAction', 'acl_resource' => 'update_acl_resource'],
            ],
        ]);
        $records = [
            new ResultRecord(['id' => 1, 't_owner_id' => 123, 't_organization_id' => 456]),
        ];

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->willReturnCallback(function ($resource, ?DomainObjectReference $object = null) {
                self::assertEquals('update_acl_resource', $resource);
                self::assertInstanceOf(DomainObjectReference::class, $object);
                self::assertSame('Test\Entity', $object->getType());
                self::assertSame(1, $object->getIdentifier());
                self::assertSame(123, $object->getOwnerId());
                self::assertSame(456, $object->getOrganizationId());

                return true;
            });

        $this->setOwnershipFields(
            ['t' => ['Test\Entity', 'id', 't_organization_id', 't_owner_id']]
        );
        $this->extension->visitResult($config, ResultsObject::create(['data' => $records]));

        $actionConfiguration = $this->getActionConfigurationOption($config);
        self::assertNull($actionConfiguration);
    }

    public function testVisitResultWithOwnershipFieldsForActionWithAclResourceAndRecordOwnerIdIsNull(): void
    {
        $config = DatagridConfiguration::create([
            'actions' => [
                'update' => ['type' => 'updateAction', 'acl_resource' => 'update_acl_resource'],
            ],
        ]);
        $records = [
            new ResultRecord(['id' => 1, 't_owner_id' => null, 't_organization_id' => 456]),
        ];

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->willReturnCallback(function ($resource, ?DomainObjectReference $object = null) {
                self::assertEquals('update_acl_resource', $resource);
                self::assertNull($object);

                return true;
            });

        $this->setOwnershipFields(
            ['t' => ['Test\Entity', 'id', 't_organization_id', 't_owner_id']]
        );
        $this->extension->visitResult($config, ResultsObject::create(['data' => $records]));

        $actionConfiguration = $this->getActionConfigurationOption($config);
        self::assertNull($actionConfiguration);
    }

    public function testVisitResultWithOwnershipFieldsForActionWithAclResourceAndExistingActionConfiguration(): void
    {
        $config = DatagridConfiguration::create([
            'actions'    => [
                'update' => ['type' => 'updateAction', 'acl_resource' => 'update_acl_resource'],
            ],
            'properties' => [
                'action_configuration' => [
                    'type'     => 'callback',
                    'callable' => function () {
                        return ['delete' => false];
                    }
                ]
            ]
        ]);
        $records = [
            new ResultRecord(['id' => 1, 't_owner_id' => 123, 't_organization_id' => 456]),
        ];

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->willReturn(false);

        $this->setOwnershipFields(
            ['t' => ['Test\Entity', 'id', 't_organization_id', 't_owner_id']]
        );
        $this->extension->visitResult($config, ResultsObject::create(['data' => $records]));

        $actionConfiguration = $this->getActionConfigurationOption($config);
        self::assertNotNull($actionConfiguration);
        self::assertArrayHasKey('callable', $actionConfiguration);
        foreach ($records as $record) {
            $result = call_user_func($actionConfiguration['callable'], $record);
            self::assertEquals(['update' => false, 'delete' => false], $result);
        }
    }

    public function testVisitMetadataDisabledAction(): void
    {
        $config = DatagridConfiguration::create(
            [
                'actions' => [
                    'action1' => ['type' => 'type1', 'disabled' => true],
                ],
            ]
        );
        $metadata = MetadataObject::create([]);

        $this->extension->visitMetadata($config, $metadata);

        $this->assertEquals(
            [
                'rowActions' => []
            ],
            $metadata->toArray()
        );
    }
}
