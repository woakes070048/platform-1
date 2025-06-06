<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\CustomizeLoadedData;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\Extra\HateoasConfigExtra;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\TestConfigExtra;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Component\ChainProcessor\ParameterBagInterface;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class CustomizeLoadedDataContextTest extends TestCase
{
    private CustomizeLoadedDataContext $context;

    #[\Override]
    protected function setUp(): void
    {
        $this->context = new CustomizeLoadedDataContext();
    }

    public function testRootClassName(): void
    {
        self::assertNull($this->context->getRootClassName());

        $className = 'Test\Class';
        $this->context->setRootClassName($className);
        self::assertEquals($className, $this->context->getRootClassName());
    }

    public function testClassName(): void
    {
        $className = 'Test\Class';
        $this->context->setClassName($className);
        self::assertEquals($className, $this->context->getClassName());
    }

    public function testPropertyPath(): void
    {
        self::assertNull($this->context->getPropertyPath());

        $propertyPath = 'field1.field11';
        $this->context->setPropertyPath($propertyPath);
        self::assertEquals($propertyPath, $this->context->getPropertyPath());
    }

    public function testParentAction(): void
    {
        self::assertNull($this->context->getParentAction());
        self::assertTrue($this->context->has('parentAction'));
        self::assertSame('', $this->context->get('parentAction'));

        $actionName = 'test_action';
        $this->context->setParentAction($actionName);
        self::assertEquals($actionName, $this->context->getParentAction());
        self::assertTrue($this->context->has('parentAction'));
        self::assertEquals($actionName, $this->context->get('parentAction'));

        $this->context->setParentAction(null);
        self::assertNull($this->context->getParentAction());
        self::assertTrue($this->context->has('parentAction'));
        self::assertSame('', $this->context->get('parentAction'));
    }

    public function testRootConfig(): void
    {
        self::assertNull($this->context->getRootConfig());

        $config = new EntityDefinitionConfig();
        $this->context->setRootConfig($config);
        self::assertSame($config, $this->context->getRootConfig());

        $this->context->setRootConfig(null);
        self::assertNull($this->context->getRootConfig());
    }

    public function testConfig(): void
    {
        self::assertNull($this->context->getConfig());

        $config = new EntityDefinitionConfig();
        $this->context->setConfig($config);
        self::assertSame($config, $this->context->getConfig());

        $this->context->setConfig(null);
        self::assertNull($this->context->getConfig());
    }

    public function testSharedData(): void
    {
        $sharedData = $this->createMock(ParameterBagInterface::class);

        $this->context->setSharedData($sharedData);
        self::assertSame($sharedData, $this->context->getSharedData());
    }

    public function testGetNormalizationContext(): void
    {
        $action = 'test_action';
        $version = '1.2';
        $sharedData = $this->createMock(ParameterBagInterface::class);
        $this->context->setAction($action);
        $this->context->setVersion($version);
        $this->context->setSharedData($sharedData);
        $this->context->getRequestType()->add('test_request_type');
        $requestType = $this->context->getRequestType();

        $normalizationContext = $this->context->getNormalizationContext();
        self::assertCount(4, $normalizationContext);
        self::assertSame($action, $normalizationContext['action']);
        self::assertSame($version, $normalizationContext['version']);
        self::assertSame($requestType, $normalizationContext['requestType']);
        self::assertSame($sharedData, $normalizationContext['sharedData']);

        $parentAction = 'test_parent_action';
        $this->context->setParentAction($parentAction);
        $normalizationContext = $this->context->getNormalizationContext();
        self::assertCount(5, $normalizationContext);
        self::assertSame($action, $normalizationContext['action']);
        self::assertSame($version, $normalizationContext['version']);
        self::assertSame($requestType, $normalizationContext['requestType']);
        self::assertSame($sharedData, $normalizationContext['sharedData']);
        self::assertSame($parentAction, $normalizationContext['parentAction']);
    }

    public function testData(): void
    {
        $data = ['key' => 'value'];
        $this->context->setData($data);
        self::assertSame($data, $this->context->getData());
        self::assertTrue($this->context->hasResult());
        self::assertSame($data, $this->context->getResult());

        $this->context->setData([]);
        self::assertSame([], $this->context->getData());
        self::assertTrue($this->context->hasResult());
        self::assertSame([], $this->context->getResult());
    }

    public function testIdentifierOnly(): void
    {
        self::assertFalse($this->context->isIdentifierOnly());

        $this->context->setIdentifierOnly(true);
        self::assertTrue($this->context->isIdentifierOnly());

        $this->context->setIdentifierOnly(false);
        self::assertFalse($this->context->isIdentifierOnly());
    }

    public function testGetResultFieldNameWithoutConfig(): void
    {
        $propertyPath = 'test';
        self::assertEquals($propertyPath, $this->context->getResultFieldName($propertyPath));
    }

    public function testGetResultFieldNameWhenFieldDoesNotExist(): void
    {
        $propertyPath = 'test';
        $config = new EntityDefinitionConfig();
        $this->context->setConfig($config);
        self::assertEquals($propertyPath, $this->context->getResultFieldName($propertyPath));
    }

    public function testGetResultFieldNameForNotRenamedField(): void
    {
        $propertyPath = 'test';
        $config = new EntityDefinitionConfig();
        $config->addField($propertyPath);
        $this->context->setConfig($config);
        self::assertEquals($propertyPath, $this->context->getResultFieldName($propertyPath));
    }

    public function testGetResultFieldNameForRenamedField(): void
    {
        $fieldName = 'renamedTest';
        $propertyPath = 'test';
        $config = new EntityDefinitionConfig();
        $config->addField($fieldName)->setPropertyPath($propertyPath);
        $this->context->setConfig($config);
        self::assertEquals($fieldName, $this->context->getResultFieldName($propertyPath));
    }

    public function testGetResultFieldNameForComputedField(): void
    {
        $fieldName = 'test';
        $config = new EntityDefinitionConfig();
        $config->addField($fieldName)->setPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH);
        $this->context->setConfig($config);
        self::assertEquals($fieldName, $this->context->getResultFieldName($fieldName));
    }

    public function testGetResultFieldValueWithoutConfig(): void
    {
        $propertyName = 'test';
        $data = [$propertyName => 'test value'];
        self::assertEquals($data[$propertyName], $this->context->getResultFieldValue($propertyName, $data));
    }

    public function testGetResultFieldValueWhenFieldDoesNotExist(): void
    {
        $propertyName = 'test';
        $data = [$propertyName => 'test value'];
        $config = new EntityDefinitionConfig();
        $this->context->setConfig($config);
        self::assertEquals($data[$propertyName], $this->context->getResultFieldValue($propertyName, $data));
    }

    public function testGetResultFieldValueForNotRenamedField(): void
    {
        $propertyName = 'test';
        $data = [$propertyName => 'test value'];
        $config = new EntityDefinitionConfig();
        $config->addField($propertyName);
        $this->context->setConfig($config);
        self::assertEquals($data[$propertyName], $this->context->getResultFieldValue($propertyName, $data));
    }

    public function testGetResultFieldValueForRenamedField(): void
    {
        $fieldName = 'renamedTest';
        $propertyName = 'test';
        $data = [$fieldName => 'test value'];
        $config = new EntityDefinitionConfig();
        $config->addField($fieldName)->setPropertyPath($propertyName);
        $this->context->setConfig($config);
        self::assertEquals($data[$fieldName], $this->context->getResultFieldValue($propertyName, $data));
    }

    public function testGetResultFieldValueWhenDataDoesNotHaveFieldValue(): void
    {
        $fieldName = 'renamedTest';
        $propertyName = 'test';
        $data = [];
        $config = new EntityDefinitionConfig();
        $config->addField($fieldName)->setPropertyPath($propertyName);
        $this->context->setConfig($config);
        self::assertNull($this->context->getResultFieldValue($propertyName, $data));
    }

    public function testGetResultFieldNameForSpecifiedConfigWhenFieldDoesNotExist(): void
    {
        $propertyPath = 'test';
        $config = new EntityDefinitionConfig();
        self::assertEquals($propertyPath, $this->context->getResultFieldName($propertyPath, $config));
    }

    public function testGetResultFieldNameForSpecifiedConfigForNotRenamedField(): void
    {
        $propertyPath = 'test';
        $config = new EntityDefinitionConfig();
        $config->addField($propertyPath);
        self::assertEquals($propertyPath, $this->context->getResultFieldName($propertyPath, $config));
    }

    public function testGetResultFieldNameForSpecifiedConfigForRenamedField(): void
    {
        $fieldName = 'renamedTest';
        $propertyPath = 'test';
        $config = new EntityDefinitionConfig();
        $config->addField($fieldName)->setPropertyPath($propertyPath);
        self::assertEquals($fieldName, $this->context->getResultFieldName($propertyPath, $config));
    }

    public function testGetResultFieldNameForSpecifiedConfigForComputedField(): void
    {
        $fieldName = 'test';
        $config = new EntityDefinitionConfig();
        $config->addField($fieldName)->setPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH);
        self::assertEquals($fieldName, $this->context->getResultFieldName($fieldName, $config));
    }

    public function testGetResultFieldValueByPropertyPathWithoutConfig(): void
    {
        $data = [
            'prop1' => [
                'prop11' => [
                    'prop111' => 'value111'
                ]
            ]
        ];
        self::assertEquals(
            $data['prop1'],
            $this->context->getResultFieldValueByPropertyPath('prop1', $data)
        );
        self::assertNull(
            $this->context->getResultFieldValueByPropertyPath('prop2', $data)
        );
        self::assertEquals(
            $data['prop1']['prop11'],
            $this->context->getResultFieldValueByPropertyPath('prop1.prop11', $data)
        );
        self::assertNull(
            $this->context->getResultFieldValueByPropertyPath('prop1.prop12', $data)
        );
        self::assertEquals(
            $data['prop1']['prop11']['prop111'],
            $this->context->getResultFieldValueByPropertyPath('prop1.prop11.prop111', $data)
        );
        self::assertNull(
            $this->context->getResultFieldValueByPropertyPath('prop1.prop11.prop112', $data)
        );
        self::assertNull(
            $this->context->getResultFieldValueByPropertyPath('prop1.prop11.prop111.prop1111', $data)
        );
    }

    public function testGetResultFieldValueByPropertyPathWhenFieldDoesNotExist(): void
    {
        $data = [
            'prop1' => [
                'prop11' => [
                    'prop111' => 'value111'
                ]
            ]
        ];
        $config = new EntityDefinitionConfig();
        $this->context->setConfig($config);
        self::assertNull(
            $this->context->getResultFieldValueByPropertyPath('prop1', $data)
        );
        self::assertNull(
            $this->context->getResultFieldValueByPropertyPath('prop2', $data)
        );
        self::assertNull(
            $this->context->getResultFieldValueByPropertyPath('prop1.prop11', $data)
        );
        self::assertNull(
            $this->context->getResultFieldValueByPropertyPath('prop1.prop12', $data)
        );
        self::assertNull(
            $this->context->getResultFieldValueByPropertyPath('prop1.prop11.prop111', $data)
        );
        self::assertNull(
            $this->context->getResultFieldValueByPropertyPath('prop1.prop11.prop112', $data)
        );
        self::assertNull(
            $this->context->getResultFieldValueByPropertyPath('prop1.prop11.prop111.prop1111', $data)
        );
    }

    public function testGetResultFieldValueByPropertyPathForNotRenamedField(): void
    {
        $data = [
            'prop1' => [
                'prop11' => [
                    'prop111' => 'value111'
                ]
            ]
        ];
        $config = new EntityDefinitionConfig();
        $field1 = $config->addField('prop1');
        $field1TargetConfig = $field1->createAndSetTargetEntity();
        $field11 = $field1TargetConfig->addField('prop11');
        $field11TargetConfig = $field11->createAndSetTargetEntity();
        $field11TargetConfig->addField('prop111');
        $this->context->setConfig($config);
        self::assertEquals(
            $data['prop1'],
            $this->context->getResultFieldValueByPropertyPath('prop1', $data)
        );
        self::assertNull(
            $this->context->getResultFieldValueByPropertyPath('prop2', $data)
        );
        self::assertEquals(
            $data['prop1']['prop11'],
            $this->context->getResultFieldValueByPropertyPath('prop1.prop11', $data)
        );
        self::assertNull(
            $this->context->getResultFieldValueByPropertyPath('prop1.prop12', $data)
        );
        self::assertEquals(
            $data['prop1']['prop11']['prop111'],
            $this->context->getResultFieldValueByPropertyPath('prop1.prop11.prop111', $data)
        );
        self::assertNull(
            $this->context->getResultFieldValueByPropertyPath('prop1.prop11.prop112', $data)
        );
        self::assertNull(
            $this->context->getResultFieldValueByPropertyPath('prop1.prop11.prop111.prop1111', $data)
        );
    }

    public function testGetResultFieldValueByPropertyPathForRenamedField(): void
    {
        $data = [
            'renamedProp1' => [
                'renamedProp11' => [
                    'renamedProp111' => 'value111'
                ]
            ]
        ];
        $config = new EntityDefinitionConfig();
        $field1 = $config->addField('renamedProp1');
        $field1->setPropertyPath('prop1');
        $field1TargetConfig = $field1->createAndSetTargetEntity();
        $field11 = $field1TargetConfig->addField('renamedProp11');
        $field11->setPropertyPath('prop11');
        $field11TargetConfig = $field11->createAndSetTargetEntity();
        $field111 = $field11TargetConfig->addField('renamedProp111');
        $field111->setPropertyPath('prop111');
        $this->context->setConfig($config);
        self::assertEquals(
            $data['renamedProp1'],
            $this->context->getResultFieldValueByPropertyPath('prop1', $data)
        );
        self::assertNull(
            $this->context->getResultFieldValueByPropertyPath('renamedProp1', $data)
        );
        self::assertNull(
            $this->context->getResultFieldValueByPropertyPath('prop2', $data)
        );
        self::assertEquals(
            $data['renamedProp1']['renamedProp11'],
            $this->context->getResultFieldValueByPropertyPath('prop1.prop11', $data)
        );
        self::assertNull(
            $this->context->getResultFieldValueByPropertyPath('renamedProp1.prop11', $data)
        );
        self::assertNull(
            $this->context->getResultFieldValueByPropertyPath('renamedProp1.renamedProp11', $data)
        );
        self::assertNull(
            $this->context->getResultFieldValueByPropertyPath('prop1.prop12', $data)
        );
        self::assertEquals(
            $data['renamedProp1']['renamedProp11']['renamedProp111'],
            $this->context->getResultFieldValueByPropertyPath('prop1.prop11.prop111', $data)
        );
        self::assertNull(
            $this->context->getResultFieldValueByPropertyPath('prop1.prop11.renamedProp111', $data)
        );
        self::assertNull(
            $this->context->getResultFieldValueByPropertyPath('prop1.renamedProp11.renamedProp111', $data)
        );
        self::assertNull(
            $this->context->getResultFieldValueByPropertyPath('renamedProp1.renamedProp11.renamedProp111', $data)
        );
        self::assertNull(
            $this->context->getResultFieldValueByPropertyPath('prop1.prop11.prop112', $data)
        );
        self::assertNull(
            $this->context->getResultFieldValueByPropertyPath('prop1.prop11.prop111.prop1111', $data)
        );
    }

    public function testIsFieldRequestedWithoutConfig(): void
    {
        $fieldName = 'test';
        self::assertFalse($this->context->isFieldRequested($fieldName));
        self::assertFalse($this->context->isFieldRequested($fieldName, [$fieldName => 'value']));
        self::assertFalse($this->context->isFieldRequested($fieldName, ['another' => 'value']));
    }

    public function testIsFieldRequestedWhenFieldDoesNotExist(): void
    {
        $fieldName = 'test';
        $config = new EntityDefinitionConfig();
        $this->context->setConfig($config);
        self::assertFalse($this->context->isFieldRequested($fieldName));
        self::assertFalse($this->context->isFieldRequested($fieldName, [$fieldName => 'value']));
        self::assertFalse($this->context->isFieldRequested($fieldName, ['another' => 'value']));
    }

    public function testIsFieldRequestedWhenFieldExists(): void
    {
        $fieldName = 'test';
        $config = new EntityDefinitionConfig();
        $config->addField($fieldName);
        $this->context->setConfig($config);
        self::assertTrue($this->context->isFieldRequested($fieldName));
        self::assertFalse($this->context->isFieldRequested($fieldName, [$fieldName => 'value']));
        self::assertTrue($this->context->isFieldRequested($fieldName, ['another' => 'value']));
    }

    public function testIsFieldRequestedWhenFieldExcluded(): void
    {
        $fieldName = 'test';
        $config = new EntityDefinitionConfig();
        $config->addField($fieldName)->setExcluded();
        $this->context->setConfig($config);
        self::assertFalse($this->context->isFieldRequested($fieldName));
        self::assertFalse($this->context->isFieldRequested($fieldName, [$fieldName => 'value']));
        self::assertFalse($this->context->isFieldRequested($fieldName, ['another' => 'value']));
    }

    public function testIsAtLeastOneFieldRequestedWithoutConfig(): void
    {
        $fieldName = 'test';
        self::assertFalse(
            $this->context->isAtLeastOneFieldRequested(['anotherField', $fieldName])
        );
        self::assertFalse(
            $this->context->isAtLeastOneFieldRequested(['anotherField', $fieldName], [$fieldName => 'value'])
        );
        self::assertFalse(
            $this->context->isAtLeastOneFieldRequested(['anotherField', $fieldName], ['test1' => 'value'])
        );
    }

    public function testIsAtLeastOneFieldRequestedWhenFieldsDoNotExist(): void
    {
        $fieldName = 'test';
        $config = new EntityDefinitionConfig();
        $this->context->setConfig($config);
        self::assertFalse(
            $this->context->isAtLeastOneFieldRequested(['anotherField', $fieldName])
        );
        self::assertFalse(
            $this->context->isAtLeastOneFieldRequested(['anotherField', $fieldName], [$fieldName => 'value'])
        );
        self::assertFalse(
            $this->context->isAtLeastOneFieldRequested(['anotherField', $fieldName], ['test1' => 'value'])
        );
    }

    public function testIsAtLeastOneFieldRequestedWhenFieldsExists(): void
    {
        $fieldName = 'test';
        $anotherFieldName = 'anotherField';
        $config = new EntityDefinitionConfig();
        $config->addField($fieldName);
        $config->addField($anotherFieldName);
        $this->context->setConfig($config);
        self::assertTrue(
            $this->context->isAtLeastOneFieldRequested([$anotherFieldName, $fieldName])
        );
        self::assertTrue(
            $this->context->isAtLeastOneFieldRequested([$anotherFieldName, $fieldName], [$fieldName => 'value'])
        );
        self::assertTrue(
            $this->context->isAtLeastOneFieldRequested([$anotherFieldName, $fieldName], ['test1' => 'value'])
        );
        self::assertFalse(
            $this->context->isAtLeastOneFieldRequested(
                [$anotherFieldName, $fieldName],
                [$anotherFieldName => 'value', $fieldName => 'value']
            )
        );
    }

    public function testIsAtLeastOneFieldRequestedWhenAllFieldsExcluded(): void
    {
        $fieldName = 'test';
        $anotherFieldName = 'anotherField';
        $config = new EntityDefinitionConfig();
        $config->addField($fieldName)->setExcluded();
        $config->addField($anotherFieldName)->setExcluded();
        $this->context->setConfig($config);
        self::assertFalse(
            $this->context->isAtLeastOneFieldRequested([$anotherFieldName, $fieldName])
        );
        self::assertFalse(
            $this->context->isAtLeastOneFieldRequested([$anotherFieldName, $fieldName], [$fieldName => 'value'])
        );
        self::assertFalse(
            $this->context->isAtLeastOneFieldRequested([$anotherFieldName, $fieldName], ['test1' => 'value'])
        );
        self::assertFalse(
            $this->context->isAtLeastOneFieldRequested(
                [$anotherFieldName, $fieldName],
                [$anotherFieldName => 'value', $fieldName => 'value']
            )
        );
    }

    public function testIsAtLeastOneFieldRequestedWhenOneFieldExcluded(): void
    {
        $fieldName = 'test';
        $anotherFieldName = 'anotherField';
        $config = new EntityDefinitionConfig();
        $config->addField($fieldName)->setExcluded();
        $config->addField($anotherFieldName);
        $this->context->setConfig($config);
        self::assertTrue(
            $this->context->isAtLeastOneFieldRequested([$anotherFieldName, $fieldName])
        );
        self::assertTrue(
            $this->context->isAtLeastOneFieldRequested([$anotherFieldName, $fieldName], [$fieldName => 'value'])
        );
        self::assertFalse(
            $this->context->isAtLeastOneFieldRequested([$anotherFieldName, $fieldName], [$anotherFieldName => 'value'])
        );
        self::assertTrue(
            $this->context->isAtLeastOneFieldRequested([$anotherFieldName, $fieldName], ['test1' => 'value'])
        );
        self::assertFalse(
            $this->context->isAtLeastOneFieldRequested(
                [$anotherFieldName, $fieldName],
                [$anotherFieldName => 'value', $fieldName => 'value']
            )
        );
    }

    public function testIsFieldRequestedForCollection(): void
    {
        $fieldName = 'test';
        $config = new EntityDefinitionConfig();
        $config->addField($fieldName);
        $this->context->setConfig($config);
        self::assertFalse(
            $this->context->isFieldRequestedForCollection(
                $fieldName,
                []
            )
        );
        self::assertFalse(
            $this->context->isFieldRequestedForCollection(
                $fieldName,
                [
                    [$fieldName => 'value']
                ]
            )
        );
        self::assertFalse(
            $this->context->isFieldRequestedForCollection(
                $fieldName,
                [
                    [$fieldName => 'value1'],
                    [$fieldName => 'value2']
                ]
            )
        );
        self::assertTrue(
            $this->context->isFieldRequestedForCollection(
                $fieldName,
                [
                    ['another' => 'value']
                ]
            )
        );
        self::assertTrue(
            $this->context->isFieldRequestedForCollection(
                $fieldName,
                [
                    ['another' => 'value1'],
                    ['another' => 'value2']
                ]
            )
        );
        self::assertTrue(
            $this->context->isFieldRequestedForCollection(
                $fieldName,
                [
                    [$fieldName => 'value1'],
                    ['another' => 'value2']
                ]
            )
        );
    }

    public function testIsAtLeastOneFieldRequestedForCollection(): void
    {
        $fieldName = 'test';
        $config = new EntityDefinitionConfig();
        $config->addField($fieldName);
        $this->context->setConfig($config);
        self::assertFalse(
            $this->context->isAtLeastOneFieldRequestedForCollection(
                [$fieldName],
                []
            )
        );
        self::assertFalse(
            $this->context->isAtLeastOneFieldRequestedForCollection(
                [$fieldName],
                [
                    [$fieldName => 'value']
                ]
            )
        );
        self::assertFalse(
            $this->context->isAtLeastOneFieldRequestedForCollection(
                [$fieldName],
                [
                    [$fieldName => 'value1'],
                    [$fieldName => 'value2']
                ]
            )
        );
        self::assertTrue(
            $this->context->isAtLeastOneFieldRequestedForCollection(
                [$fieldName],
                [
                    ['another' => 'value']
                ]
            )
        );
        self::assertTrue(
            $this->context->isAtLeastOneFieldRequestedForCollection(
                [$fieldName],
                [
                    ['another' => 'value1'],
                    ['another' => 'value2']
                ]
            )
        );
        self::assertTrue(
            $this->context->isAtLeastOneFieldRequestedForCollection(
                [$fieldName],
                [
                    [$fieldName => 'value1'],
                    ['another' => 'value2']
                ]
            )
        );
    }

    public function testGetIdentifierValues(): void
    {
        self::assertEquals(
            [],
            $this->context->getIdentifierValues(
                [],
                'id'
            )
        );
        self::assertEquals(
            [1, 2],
            $this->context->getIdentifierValues(
                [
                    ['id' => 1, 'name' => 'value1'],
                    ['id' => 2, 'name' => 'value2']
                ],
                'id'
            )
        );
    }

    public function testConfigExtras(): void
    {
        $this->context->setConfigExtras([]);
        self::assertSame([], $this->context->getConfigExtras());
        self::assertFalse($this->context->hasConfigExtra('test'));
        self::assertNull($this->context->getConfigExtra('test'));

        $configExtra = new TestConfigExtra('test');

        $configExtras = [$configExtra];
        $this->context->setConfigExtras($configExtras);
        self::assertEquals($configExtras, $this->context->getConfigExtras());

        self::assertTrue($this->context->hasConfigExtra('test'));
        self::assertSame($configExtra, $this->context->getConfigExtra('test'));
        self::assertFalse($this->context->hasConfigExtra('another'));
        self::assertNull($this->context->getConfigExtra('another'));
    }

    public function testHateoas(): void
    {
        $this->context->setConfigExtras([]);
        self::assertFalse($this->context->isHateoasEnabled());

        $this->context->setConfigExtras([new TestConfigExtra('test'), new HateoasConfigExtra()]);
        self::assertTrue($this->context->isHateoasEnabled());

        $this->context->setConfigExtras([new TestConfigExtra('test')]);
        self::assertFalse($this->context->isHateoasEnabled());
    }
}
