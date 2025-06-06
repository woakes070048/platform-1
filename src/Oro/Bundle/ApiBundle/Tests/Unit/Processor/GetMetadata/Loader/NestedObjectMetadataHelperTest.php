<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetMetadata\Loader;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Model\FakeEntity;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader\MetadataHelper;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader\NestedObjectMetadataHelper;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader\ObjectMetadataFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class NestedObjectMetadataHelperTest extends TestCase
{
    private MetadataHelper&MockObject $metadataHelper;
    private ObjectMetadataFactory&MockObject $objectMetadataFactory;
    private NestedObjectMetadataHelper $nestedObjectMetadataHelper;

    #[\Override]
    protected function setUp(): void
    {
        $this->metadataHelper = $this->createMock(MetadataHelper::class);
        $this->objectMetadataFactory = $this->createMock(ObjectMetadataFactory::class);

        $this->nestedObjectMetadataHelper = new NestedObjectMetadataHelper(
            $this->metadataHelper,
            $this->objectMetadataFactory
        );
    }

    public function testAddNestedObjectAssociation(): void
    {
        $config = new EntityDefinitionConfig();

        $entityMetadata = new EntityMetadata('Test\Entity');
        $entityClass = 'Test\Class';
        $fieldName = 'testField';
        $field = new EntityDefinitionFieldConfig();
        $targetAction = 'testAction';

        $targetConfig = $field->createAndSetTargetEntity();
        $targetClass = 'Test\TargetClass';
        $field->setFormOptions(['data_class' => $targetClass]);
        $targetEntityMetadata = new EntityMetadata('Test\Entity');

        $associationMetadata = new AssociationMetadata();

        $this->objectMetadataFactory->expects(self::once())
            ->method('createAndAddAssociationMetadata')
            ->with(
                self::identicalTo($entityMetadata),
                $entityClass,
                self::identicalTo($config),
                $fieldName,
                self::identicalTo($field),
                $targetAction,
                $targetClass
            )
            ->willReturn($associationMetadata);
        $this->objectMetadataFactory->expects(self::once())
            ->method('createObjectMetadata')
            ->with($targetClass, self::identicalTo($targetConfig))
            ->willReturn($targetEntityMetadata);

        $result = $this->nestedObjectMetadataHelper->addNestedObjectAssociation(
            $entityMetadata,
            $entityClass,
            $config,
            $fieldName,
            $field,
            $targetAction
        );
        self::assertSame($associationMetadata, $result);
        self::assertSame($targetEntityMetadata, $result->getTargetMetadata());
    }

    public function testAddNestedObjectAssociationWithInheritData(): void
    {
        $config = new EntityDefinitionConfig();

        $entityMetadata = new EntityMetadata('Test\Entity');
        $entityClass = 'Test\Class';
        $fieldName = 'testField';
        $field = new EntityDefinitionFieldConfig();
        $targetAction = 'testAction';

        $targetConfig = $field->createAndSetTargetEntity();
        $field->setFormOptions(['inherit_data' => true]);
        $targetEntityMetadata = new EntityMetadata('Test\Entity');

        $associationMetadata = new AssociationMetadata();

        $this->objectMetadataFactory->expects(self::once())
            ->method('createAndAddAssociationMetadata')
            ->with(
                self::identicalTo($entityMetadata),
                $entityClass,
                self::identicalTo($config),
                $fieldName,
                self::identicalTo($field),
                $targetAction,
                FakeEntity::class
            )
            ->willReturn($associationMetadata);
        $this->objectMetadataFactory->expects(self::once())
            ->method('createObjectMetadata')
            ->with(FakeEntity::class, self::identicalTo($targetConfig))
            ->willReturn($targetEntityMetadata);

        $result = $this->nestedObjectMetadataHelper->addNestedObjectAssociation(
            $entityMetadata,
            $entityClass,
            $config,
            $fieldName,
            $field,
            $targetAction
        );
        self::assertSame($associationMetadata, $result);
        self::assertSame($targetEntityMetadata, $result->getTargetMetadata());
    }

    public function testAddNestedObjectAssociationShouldThrowExceptionWhenNoDataClassFormOption(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'The "data_class" form option should be specified for the nested object'
            . ' when the "inherit_data" form option is not specified. Field: Test\Class::testField.'
        );

        $config = new EntityDefinitionConfig();

        $entityMetadata = new EntityMetadata('Test\Entity');
        $entityClass = 'Test\Class';
        $fieldName = 'testField';
        $field = new EntityDefinitionFieldConfig();
        $targetAction = 'testAction';

        $this->objectMetadataFactory->expects(self::never())
            ->method('createAndAddAssociationMetadata');
        $this->objectMetadataFactory->expects(self::never())
            ->method('createObjectMetadata');

        $this->nestedObjectMetadataHelper->addNestedObjectAssociation(
            $entityMetadata,
            $entityClass,
            $config,
            $fieldName,
            $field,
            $targetAction
        );
    }

    public function testAddNestedObjectAssociationShouldThrowExceptionWhenDataClassFormOptionUsedWithInheritData(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'The "data_class" form option should not be specified for the nested object'
            . ' together with the "inherit_data" form option. Field: Test\Class::testField.'
        );

        $config = new EntityDefinitionConfig();

        $entityMetadata = new EntityMetadata('Test\Entity');
        $entityClass = 'Test\Class';
        $fieldName = 'testField';
        $field = new EntityDefinitionFieldConfig();
        $field->setFormOptions(['data_class' => 'Test\TargetClass', 'inherit_data' => true]);
        $targetAction = 'testAction';

        $this->objectMetadataFactory->expects(self::never())
            ->method('createAndAddAssociationMetadata');
        $this->objectMetadataFactory->expects(self::never())
            ->method('createObjectMetadata');

        $this->nestedObjectMetadataHelper->addNestedObjectAssociation(
            $entityMetadata,
            $entityClass,
            $config,
            $fieldName,
            $field,
            $targetAction
        );
    }

    public function testGetLinkedFieldWhenTargetFieldIsLinkedToAssociationField(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'The "association.field1" property path is not supported for the nested object.'
            . ' Parent Field: Test\ParentClass::parentField. Target Field: targetField.'
        );

        $parentConfig = new EntityDefinitionConfig();
        $parentClassName = 'Test\ParentClass';
        $parentFieldName = 'parentField';
        $targetFieldName = 'targetField';
        $targetField = new EntityDefinitionFieldConfig();

        $parentConfig->addField('association')
            ->createAndSetTargetEntity()
            ->addField('field1');
        $targetField->setPropertyPath('association.field1');

        $this->nestedObjectMetadataHelper->getLinkedField(
            $parentConfig,
            $parentClassName,
            $parentFieldName,
            $targetFieldName,
            $targetField
        );
    }

    public function testGetLinkedFieldWhenTargetFieldIsLinkedToAssociation(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'An association is not supported for the nested object.'
            . ' Parent Field: Test\ParentClass::parentField. Target Field: targetField.'
        );

        $parentConfig = new EntityDefinitionConfig();
        $parentClassName = 'Test\ParentClass';
        $parentFieldName = 'parentField';
        $targetFieldName = 'targetField';
        $targetField = new EntityDefinitionFieldConfig();

        $parentConfig->addField('association')
            ->createAndSetTargetEntity();
        $targetField->setPropertyPath('association');

        $this->nestedObjectMetadataHelper->getLinkedField(
            $parentConfig,
            $parentClassName,
            $parentFieldName,
            $targetFieldName,
            $targetField
        );
    }

    public function testGetLinkedFieldWhenTargetFieldIsLinkedToField(): void
    {
        $parentConfig = new EntityDefinitionConfig();
        $parentClassName = 'Test\ParentClass';
        $parentFieldName = 'parentField';
        $targetFieldName = 'targetField';
        $targetField = new EntityDefinitionFieldConfig();

        $linkedField = $parentConfig->addField('field');
        $targetField->setPropertyPath('field');

        $result = $this->nestedObjectMetadataHelper->getLinkedField(
            $parentConfig,
            $parentClassName,
            $parentFieldName,
            $targetFieldName,
            $targetField
        );
        self::assertSame($linkedField, $result);
    }

    public function testGetLinkedFieldFoComputedField(): void
    {
        $parentConfig = new EntityDefinitionConfig();
        $parentClassName = 'Test\ParentClass';
        $parentFieldName = 'parentField';
        $targetFieldName = 'targetField';
        $targetField = new EntityDefinitionFieldConfig();
        $targetField->setPropertyPath('_');

        $result = $this->nestedObjectMetadataHelper->getLinkedField(
            $parentConfig,
            $parentClassName,
            $parentFieldName,
            $targetFieldName,
            $targetField
        );
        self::assertSame($targetField, $result);
    }

    public function testSetTargetPropertyPathWhenFormPropertyPathEqualsToFieldName(): void
    {
        $fieldName = 'testField';
        $propertyMetadata = new FieldMetadata($fieldName);
        $field = new EntityDefinitionFieldConfig();
        $targetAction = 'testAction';

        $this->metadataHelper->expects(self::once())
            ->method('getFormPropertyPath')
            ->with(self::identicalTo($field), $targetAction)
            ->willReturn($fieldName);

        $this->nestedObjectMetadataHelper->setTargetPropertyPath(
            $propertyMetadata,
            $fieldName,
            $field,
            $targetAction
        );
        self::assertEquals($fieldName, $propertyMetadata->getPropertyPath());
    }

    public function testSetTargetPropertyPathWhenFormPropertyPathIsNotEqualToFieldName(): void
    {
        $fieldName = 'testField';
        $propertyMetadata = new FieldMetadata($fieldName);
        $field = new EntityDefinitionFieldConfig();
        $targetAction = 'testAction';
        $formPropertyPath = 'formPropertyPath';

        $this->metadataHelper->expects(self::once())
            ->method('getFormPropertyPath')
            ->with(self::identicalTo($field), $targetAction)
            ->willReturn($formPropertyPath);

        $this->nestedObjectMetadataHelper->setTargetPropertyPath(
            $propertyMetadata,
            $fieldName,
            $field,
            $targetAction
        );
        self::assertEquals($formPropertyPath, $propertyMetadata->getPropertyPath());
    }

    public function testGetLinkedFieldForRenamedField(): void
    {
        $parentConfig = new EntityDefinitionConfig();
        $parentClassName = 'Test\ParentClass';
        $parentFieldName = 'parentField';
        $targetFieldName = 'targetField';
        $targetField = $parentConfig->addField('renamedField');
        $targetField->setPropertyPath('field');

        $anotherField = $parentConfig->addField('field');
        $anotherField->setPropertyPath('anotherField');

        $result = $this->nestedObjectMetadataHelper->getLinkedField(
            $parentConfig,
            $parentClassName,
            $parentFieldName,
            $targetFieldName,
            $targetField
        );
        self::assertSame($targetField, $result);
    }
}
