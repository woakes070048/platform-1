<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\CustomizeLoadedData\Handler;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\Extra\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\Handler\EntityHandler;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Component\ChainProcessor\ActionProcessorInterface;
use Oro\Component\ChainProcessor\ParameterBagInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EntityHandlerTest extends TestCase
{
    private ActionProcessorInterface&MockObject $customizationProcessor;

    #[\Override]
    protected function setUp(): void
    {
        $this->customizationProcessor = $this->createMock(ActionProcessorInterface::class);
    }

    public function handlerCallback(array $data, array $context): array
    {
        $data['callbackKey'] = sprintf('callbackValue for "%s" action', $context['action']);

        return $data;
    }

    public function testWithoutPreviousHandler(): void
    {
        $version = '1.2';
        $requestType = new RequestType(['test']);
        $entityClass = Entity\User::class;
        $config = new EntityDefinitionConfig();
        $configExtras = [new EntityDefinitionConfigExtra()];
        $data = ['key' => 'value'];

        $sharedData = $this->createMock(ParameterBagInterface::class);
        $context = ['action' => 'get', 'sharedData' => $sharedData];

        $handler = new EntityHandler(
            $this->customizationProcessor,
            $version,
            $requestType,
            $entityClass,
            $config,
            $configExtras,
            false
        );

        $this->customizationProcessor->expects(self::once())
            ->method('createContext')
            ->willReturn(new CustomizeLoadedDataContext());
        $this->customizationProcessor->expects(self::once())
            ->method('process')
            ->willReturnCallback(
                function (CustomizeLoadedDataContext $context) use (
                    $version,
                    $requestType,
                    $entityClass,
                    $config,
                    $configExtras,
                    $data
                ): void {
                    self::assertEquals($version, $context->getVersion());
                    self::assertEquals($requestType, $context->getRequestType());
                    self::assertNull($context->getParentAction());
                    self::assertEquals($entityClass, $context->getClassName());
                    self::assertSame($config, $context->getConfig());
                    self::assertSame($configExtras, $context->getConfigExtras());
                    self::assertEquals($data, $context->getResult());
                    self::assertEquals('item', $context->getFirstGroup());
                    self::assertEquals('item', $context->getLastGroup());
                    self::assertFalse($context->isIdentifierOnly());

                    $contextData = $context->getResult();
                    $contextData['anotherKey'] = 'anotherValue';
                    $context->setResult($contextData);
                }
            );

        $handledData = $handler($data, $context);
        self::assertEquals(
            ['key' => 'value', 'anotherKey' => 'anotherValue'],
            $handledData
        );
    }

    public function testWithPreviousHandlerThatIsRedundant(): void
    {
        $version = '1.2';
        $requestType = new RequestType(['test']);
        $entityClass = Entity\UserProfile::class;
        $config = new EntityDefinitionConfig();
        $configExtras = [new EntityDefinitionConfigExtra()];
        $data = ['key' => 'value'];

        $sharedData = $this->createMock(ParameterBagInterface::class);
        $context = ['action' => 'get', 'sharedData' => $sharedData];

        $previousHandler = new EntityHandler(
            $this->customizationProcessor,
            $version,
            $requestType,
            Entity\User::class,
            $config,
            $configExtras,
            false
        );
        $handler = new EntityHandler(
            $this->customizationProcessor,
            $version,
            $requestType,
            $entityClass,
            $config,
            $configExtras,
            false,
            $previousHandler
        );

        $this->customizationProcessor->expects(self::once())
            ->method('createContext')
            ->willReturn(new CustomizeLoadedDataContext());
        $this->customizationProcessor->expects(self::once())
            ->method('process')
            ->willReturnCallback(function (CustomizeLoadedDataContext $context) {
                $contextData = $context->getResult();
                $contextData['anotherKey'] = 'anotherValue';
                $context->setResult($contextData);
            });

        $handledData = $handler($data, $context);
        self::assertEquals(
            ['key' => 'value', 'anotherKey' => 'anotherValue'],
            $handledData
        );
    }

    public function testWithSeveralPreviousHandlersAndMiddleLevelPreviousHandlerIsRedundant(): void
    {
        $version = '1.2';
        $requestType = new RequestType(['test']);
        $entityClass = Entity\UserProfile::class;
        $config = new EntityDefinitionConfig();
        $configExtras = [new EntityDefinitionConfigExtra()];
        $data = ['key' => 'value'];

        $sharedData = $this->createMock(ParameterBagInterface::class);
        $context = ['action' => 'get', 'sharedData' => $sharedData];

        $previousHandler1 = [$this, 'handlerCallback'];
        $previousHandler2 = new EntityHandler(
            $this->customizationProcessor,
            $version,
            $requestType,
            Entity\User::class,
            $config,
            $configExtras,
            false,
            $previousHandler1
        );
        $handler = new EntityHandler(
            $this->customizationProcessor,
            $version,
            $requestType,
            $entityClass,
            $config,
            $configExtras,
            false,
            $previousHandler2
        );

        $this->customizationProcessor->expects(self::once())
            ->method('createContext')
            ->willReturn(new CustomizeLoadedDataContext());
        $this->customizationProcessor->expects(self::once())
            ->method('process')
            ->willReturnCallback(function (CustomizeLoadedDataContext $context) {
                $contextData = $context->getResult();
                $contextData['anotherKey'] = 'anotherValue';
                $context->setResult($contextData);
            });

        $handledData = $handler($data, $context);
        self::assertEquals(
            [
                'key'         => 'value',
                'callbackKey' => 'callbackValue for "get" action',
                'anotherKey'  => 'anotherValue'
            ],
            $handledData
        );
    }

    public function testWithPreviousHandlerThatIsNotRedundantDueToVersion(): void
    {
        $version = '1.2';
        $requestType = new RequestType(['test']);
        $entityClass = Entity\User::class;
        $config = new EntityDefinitionConfig();
        $configExtras = [new EntityDefinitionConfigExtra()];
        $data = ['key' => 'value'];

        $sharedData = $this->createMock(ParameterBagInterface::class);
        $context = ['action' => 'get', 'sharedData' => $sharedData];

        $previousHandler = new EntityHandler(
            $this->customizationProcessor,
            '1.0',
            $requestType,
            $entityClass,
            $config,
            $configExtras,
            false
        );
        $handler = new EntityHandler(
            $this->customizationProcessor,
            $version,
            $requestType,
            $entityClass,
            $config,
            $configExtras,
            false,
            $previousHandler
        );

        $this->customizationProcessor->expects(self::exactly(2))
            ->method('createContext')
            ->willReturn(new CustomizeLoadedDataContext());
        $this->customizationProcessor->expects(self::exactly(2))
            ->method('process')
            ->willReturnCallback(function (CustomizeLoadedDataContext $context) {
                $contextData = $context->getResult();
                if ('1.2' === $context->getVersion()) {
                    $contextData['anotherKey'] = 'anotherValue';
                } elseif ('1.0' === $context->getVersion()) {
                    $contextData['previousKey'] = 'previousValue';
                }
                $context->setResult($contextData);
            });

        $handledData = $handler($data, $context);
        self::assertEquals(
            ['key' => 'value', 'previousKey' => 'previousValue', 'anotherKey' => 'anotherValue'],
            $handledData
        );
    }

    public function testWithPreviousHandlerThatIsNotRedundantDueToRequestType(): void
    {
        $version = '1.2';
        $requestType = new RequestType(['test']);
        $entityClass = Entity\User::class;
        $config = new EntityDefinitionConfig();
        $configExtras = [new EntityDefinitionConfigExtra()];
        $data = ['key' => 'value'];

        $sharedData = $this->createMock(ParameterBagInterface::class);
        $context = ['action' => 'get', 'sharedData' => $sharedData];

        $previousHandler = new EntityHandler(
            $this->customizationProcessor,
            $version,
            new RequestType(['test1']),
            $entityClass,
            $config,
            $configExtras,
            false
        );
        $handler = new EntityHandler(
            $this->customizationProcessor,
            $version,
            $requestType,
            $entityClass,
            $config,
            $configExtras,
            false,
            $previousHandler
        );

        $this->customizationProcessor->expects(self::exactly(2))
            ->method('createContext')
            ->willReturn(new CustomizeLoadedDataContext());
        $this->customizationProcessor->expects(self::exactly(2))
            ->method('process')
            ->willReturnCallback(function (CustomizeLoadedDataContext $context) {
                $contextData = $context->getResult();
                if ('test' === (string)$context->getRequestType()) {
                    $contextData['anotherKey'] = 'anotherValue';
                } elseif ('test1' === (string)$context->getRequestType()) {
                    $contextData['previousKey'] = 'previousValue';
                }
                $context->setResult($contextData);
            });

        $handledData = $handler($data, $context);
        self::assertEquals(
            ['key' => 'value', 'previousKey' => 'previousValue', 'anotherKey' => 'anotherValue'],
            $handledData
        );
    }

    public function testWithPreviousHandlerThatIsNotRedundantDueToEntityClass(): void
    {
        $version = '1.2';
        $requestType = new RequestType(['test']);
        $entityClass = Entity\User::class;
        $config = new EntityDefinitionConfig();
        $configExtras = [new EntityDefinitionConfigExtra()];
        $data = ['key' => 'value'];

        $sharedData = $this->createMock(ParameterBagInterface::class);
        $context = ['action' => 'get', 'sharedData' => $sharedData];

        $previousHandler = new EntityHandler(
            $this->customizationProcessor,
            $version,
            $requestType,
            Entity\Account::class,
            $config,
            $configExtras,
            false
        );
        $handler = new EntityHandler(
            $this->customizationProcessor,
            $version,
            $requestType,
            $entityClass,
            $config,
            $configExtras,
            false,
            $previousHandler
        );

        $this->customizationProcessor->expects(self::exactly(2))
            ->method('createContext')
            ->willReturn(new CustomizeLoadedDataContext());
        $this->customizationProcessor->expects(self::exactly(2))
            ->method('process')
            ->willReturnCallback(function (CustomizeLoadedDataContext $context) {
                $contextData = $context->getResult();
                if (Entity\User::class === $context->getClassName()) {
                    $contextData['anotherKey'] = 'anotherValue';
                } elseif (Entity\Account::class === $context->getClassName()) {
                    $contextData['previousKey'] = 'previousValue';
                }
                $context->setResult($contextData);
            });

        $handledData = $handler($data, $context);
        self::assertEquals(
            ['key' => 'value', 'previousKey' => 'previousValue', 'anotherKey' => 'anotherValue'],
            $handledData
        );
    }

    public function testWithPreviousHandlerThatIsNotRedundantDueToHandlerType(): void
    {
        $version = '1.2';
        $requestType = new RequestType(['test']);
        $entityClass = Entity\User::class;
        $config = new EntityDefinitionConfig();
        $configExtras = [new EntityDefinitionConfigExtra()];
        $data = ['key' => 'value'];

        $sharedData = $this->createMock(ParameterBagInterface::class);
        $context = ['action' => 'get', 'sharedData' => $sharedData];

        $previousHandler = function (array $data) {
            $data['previousKey'] = 'previousValue';

            return $data;
        };
        $handler = new EntityHandler(
            $this->customizationProcessor,
            $version,
            $requestType,
            $entityClass,
            $config,
            $configExtras,
            false,
            $previousHandler
        );

        $this->customizationProcessor->expects(self::once())
            ->method('createContext')
            ->willReturn(new CustomizeLoadedDataContext());
        $this->customizationProcessor->expects(self::once())
            ->method('process')
            ->willReturnCallback(function (CustomizeLoadedDataContext $context) {
                $contextData = $context->getResult();
                $contextData['anotherKey'] = 'anotherValue';
                $context->setResult($contextData);
            });

        $handledData = $handler($data, $context);
        self::assertEquals(
            ['key' => 'value', 'previousKey' => 'previousValue', 'anotherKey' => 'anotherValue'],
            $handledData
        );
    }

    public function testForIdentifierOnly(): void
    {
        $version = '1.2';
        $requestType = new RequestType(['test']);
        $entityClass = Entity\User::class;
        $data = ['key' => 'value'];
        $config = new EntityDefinitionConfig();
        $config->setIdentifierFieldNames(['id']);
        $config->addField('id');
        $configExtras = [new EntityDefinitionConfigExtra()];

        $sharedData = $this->createMock(ParameterBagInterface::class);
        $context = ['action' => 'get', 'sharedData' => $sharedData];

        $handler = new EntityHandler(
            $this->customizationProcessor,
            $version,
            $requestType,
            $entityClass,
            $config,
            $configExtras,
            false
        );

        $this->customizationProcessor->expects(self::once())
            ->method('createContext')
            ->willReturn(new CustomizeLoadedDataContext());
        $this->customizationProcessor->expects(self::once())
            ->method('process')
            ->willReturnCallback(function (CustomizeLoadedDataContext $context) {
                self::assertTrue($context->isIdentifierOnly());
            });

        $handler($data, $context);
    }

    public function testForIdentifierOnlyWithCompositeIdentifier(): void
    {
        $version = '1.2';
        $requestType = new RequestType(['test']);
        $entityClass = Entity\User::class;
        $data = ['key' => 'value'];
        $config = new EntityDefinitionConfig();
        $config->setIdentifierFieldNames(['id1', 'id2']);
        $config->addField('id1');
        $config->addField('id2');
        $configExtras = [new EntityDefinitionConfigExtra()];

        $sharedData = $this->createMock(ParameterBagInterface::class);
        $context = ['action' => 'get', 'sharedData' => $sharedData];

        $handler = new EntityHandler(
            $this->customizationProcessor,
            $version,
            $requestType,
            $entityClass,
            $config,
            $configExtras,
            false
        );

        $this->customizationProcessor->expects(self::once())
            ->method('createContext')
            ->willReturn(new CustomizeLoadedDataContext());
        $this->customizationProcessor->expects(self::once())
            ->method('process')
            ->willReturnCallback(function (CustomizeLoadedDataContext $context) {
                self::assertTrue($context->isIdentifierOnly());
            });

        $handler($data, $context);
    }

    public function testForOneFieldThatIsNotIdentifier(): void
    {
        $version = '1.2';
        $requestType = new RequestType(['test']);
        $entityClass = Entity\User::class;
        $data = ['key' => 'value'];
        $config = new EntityDefinitionConfig();
        $config->setIdentifierFieldNames(['id']);
        $config->addField('field1');
        $configExtras = [new EntityDefinitionConfigExtra()];

        $sharedData = $this->createMock(ParameterBagInterface::class);
        $context = ['action' => 'get', 'sharedData' => $sharedData];

        $handler = new EntityHandler(
            $this->customizationProcessor,
            $version,
            $requestType,
            $entityClass,
            $config,
            $configExtras,
            false
        );

        $this->customizationProcessor->expects(self::once())
            ->method('createContext')
            ->willReturn(new CustomizeLoadedDataContext());
        $this->customizationProcessor->expects(self::once())
            ->method('process')
            ->willReturnCallback(function (CustomizeLoadedDataContext $context) {
                self::assertFalse($context->isIdentifierOnly());
            });

        $handler($data, $context);
    }

    public function testForEntityWithoutIdentifier(): void
    {
        $version = '1.2';
        $requestType = new RequestType(['test']);
        $entityClass = Entity\User::class;
        $data = ['key' => 'value'];
        $config = new EntityDefinitionConfig();
        $config->addField('field1');
        $configExtras = [new EntityDefinitionConfigExtra()];

        $sharedData = $this->createMock(ParameterBagInterface::class);
        $context = ['action' => 'get', 'sharedData' => $sharedData];

        $handler = new EntityHandler(
            $this->customizationProcessor,
            $version,
            $requestType,
            $entityClass,
            $config,
            $configExtras,
            false
        );

        $this->customizationProcessor->expects(self::once())
            ->method('createContext')
            ->willReturn(new CustomizeLoadedDataContext());
        $this->customizationProcessor->expects(self::once())
            ->method('process')
            ->willReturnCallback(function (CustomizeLoadedDataContext $context) {
                self::assertFalse($context->isIdentifierOnly());
            });

        $handler($data, $context);
    }

    public function testForCollectionHandler(): void
    {
        $version = '1.2';
        $requestType = new RequestType(['test']);
        $entityClass = Entity\User::class;
        $config = new EntityDefinitionConfig();
        $configExtras = [new EntityDefinitionConfigExtra()];
        $data = ['key' => 'value'];

        $sharedData = $this->createMock(ParameterBagInterface::class);
        $context = ['action' => 'get', 'sharedData' => $sharedData];

        $handler = new EntityHandler(
            $this->customizationProcessor,
            $version,
            $requestType,
            $entityClass,
            $config,
            $configExtras,
            true
        );

        $this->customizationProcessor->expects(self::once())
            ->method('createContext')
            ->willReturn(new CustomizeLoadedDataContext());
        $this->customizationProcessor->expects(self::once())
            ->method('process')
            ->willReturnCallback(
                function (CustomizeLoadedDataContext $context) use (
                    $version,
                    $requestType,
                    $entityClass,
                    $config,
                    $configExtras,
                    $data
                ) {
                    self::assertEquals($version, $context->getVersion());
                    self::assertEquals($requestType, $context->getRequestType());
                    self::assertNull($context->getParentAction());
                    self::assertEquals($entityClass, $context->getClassName());
                    self::assertSame($config, $context->getConfig());
                    self::assertSame($configExtras, $context->getConfigExtras());
                    self::assertEquals($data, $context->getResult());
                    self::assertEquals('collection', $context->getFirstGroup());
                    self::assertEquals('collection', $context->getLastGroup());
                    self::assertFalse($context->isIdentifierOnly());

                    $contextData = $context->getResult();
                    $contextData['anotherKey'] = 'anotherValue';
                    $context->setResult($contextData);
                }
            );

        $handledData = $handler($data, $context);
        self::assertEquals(
            ['key' => 'value', 'anotherKey' => 'anotherValue'],
            $handledData
        );
    }

    public function testWithCustomConfig(): void
    {
        $version = '1.2';
        $requestType = new RequestType(['test']);
        $entityClass = Entity\User::class;
        $config = new EntityDefinitionConfig();
        $customConfig = new EntityDefinitionConfig();
        $configExtras = [new EntityDefinitionConfigExtra()];
        $data = ['key' => 'value'];

        $sharedData = $this->createMock(ParameterBagInterface::class);
        $context = [
            'action'     => 'get',
            'sharedData' => $sharedData,
            'config'     => [$entityClass => $customConfig]
        ];

        $handler = new EntityHandler(
            $this->customizationProcessor,
            $version,
            $requestType,
            $entityClass,
            $config,
            $configExtras,
            false
        );

        $this->customizationProcessor->expects(self::once())
            ->method('createContext')
            ->willReturn(new CustomizeLoadedDataContext());
        $this->customizationProcessor->expects(self::once())
            ->method('process')
            ->willReturnCallback(
                function (CustomizeLoadedDataContext $context) use (
                    $version,
                    $requestType,
                    $entityClass,
                    $customConfig,
                    $configExtras,
                    $data
                ) {
                    self::assertEquals($version, $context->getVersion());
                    self::assertEquals($requestType, $context->getRequestType());
                    self::assertNull($context->getParentAction());
                    self::assertEquals($entityClass, $context->getClassName());
                    self::assertSame($customConfig, $context->getConfig());
                    self::assertSame($configExtras, $context->getConfigExtras());
                    self::assertEquals($data, $context->getResult());
                    self::assertEquals('item', $context->getFirstGroup());
                    self::assertEquals('item', $context->getLastGroup());
                    self::assertFalse($context->isIdentifierOnly());

                    $contextData = $context->getResult();
                    $contextData['anotherKey'] = 'anotherValue';
                    $context->setResult($contextData);
                }
            );

        $handledData = $handler($data, $context);
        self::assertEquals(
            ['key' => 'value', 'anotherKey' => 'anotherValue'],
            $handledData
        );
    }

    public function testWithParentAction(): void
    {
        $version = '1.2';
        $requestType = new RequestType(['test']);
        $parentAction = 'create';
        $entityClass = Entity\User::class;
        $config = new EntityDefinitionConfig();
        $configExtras = [new EntityDefinitionConfigExtra()];
        $data = ['key' => 'value'];

        $sharedData = $this->createMock(ParameterBagInterface::class);
        $context = ['action' => 'get', 'sharedData' => $sharedData, 'parentAction' => $parentAction];

        $handler = new EntityHandler(
            $this->customizationProcessor,
            $version,
            $requestType,
            $entityClass,
            $config,
            $configExtras,
            false
        );

        $this->customizationProcessor->expects(self::once())
            ->method('createContext')
            ->willReturn(new CustomizeLoadedDataContext());
        $this->customizationProcessor->expects(self::once())
            ->method('process')
            ->willReturnCallback(
                function (CustomizeLoadedDataContext $context) use (
                    $version,
                    $requestType,
                    $parentAction,
                    $entityClass,
                    $config,
                    $configExtras,
                    $data
                ) {
                    self::assertEquals($version, $context->getVersion());
                    self::assertEquals($requestType, $context->getRequestType());
                    self::assertEquals($parentAction, $context->getParentAction());
                    self::assertEquals($entityClass, $context->getClassName());
                    self::assertSame($config, $context->getConfig());
                    self::assertSame($configExtras, $context->getConfigExtras());
                    self::assertEquals($data, $context->getResult());
                    self::assertEquals('item', $context->getFirstGroup());
                    self::assertEquals('item', $context->getLastGroup());
                    self::assertFalse($context->isIdentifierOnly());

                    $contextData = $context->getResult();
                    $contextData['anotherKey'] = 'anotherValue';
                    $context->setResult($contextData);
                }
            );

        $handledData = $handler($data, $context);
        self::assertEquals(
            ['key' => 'value', 'anotherKey' => 'anotherValue'],
            $handledData
        );
    }
}
