<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetMetadata\Loader;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Metadata\MetaPropertyMetadata;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader\EntityMetadataFactory;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader\EntityNestedObjectMetadataFactory;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader\NestedObjectMetadataHelper;
use PHPUnit\Framework\MockObject\MockObject;

class EntityNestedObjectMetadataFactoryTest extends LoaderTestCase
{
    private NestedObjectMetadataHelper&MockObject $nestedObjectMetadataHelper;
    private EntityMetadataFactory&MockObject $entityMetadataFactory;
    private EntityNestedObjectMetadataFactory $entityNestedObjectMetadataFactory;

    #[\Override]
    protected function setUp(): void
    {
        $this->nestedObjectMetadataHelper = $this->createMock(NestedObjectMetadataHelper::class);
        $this->entityMetadataFactory = $this->createMock(EntityMetadataFactory::class);

        $this->entityNestedObjectMetadataFactory = new EntityNestedObjectMetadataFactory(
            $this->nestedObjectMetadataHelper,
            $this->entityMetadataFactory
        );
    }

    public function testCreateAndAddNestedObjectMetadataForExcludedTargetField(): void
    {
        $entityMetadata = new EntityMetadata('Test\Entity');
        $config = new EntityDefinitionConfig();
        $entityClass = 'Test\Class';
        $fieldName = 'testField';
        $field = new EntityDefinitionFieldConfig();
        $withExcludedProperties = false;
        $targetAction = 'testAction';

        $classMetadata = $this->getClassMetadataMock();

        $targetConfig = $field->createAndSetTargetEntity();
        $targetFieldName = 'targetField';
        $targetField = $targetConfig->addField($targetFieldName);
        $targetField->setExcluded();

        $associationMetadata = new AssociationMetadata();
        $associationTargetMetadata = new EntityMetadata('Test\Entity');
        $associationMetadata->setTargetMetadata($associationTargetMetadata);

        $this->nestedObjectMetadataHelper->expects(self::once())
            ->method('addNestedObjectAssociation')
            ->with(
                self::identicalTo($entityMetadata),
                $entityClass,
                self::identicalTo($config),
                $fieldName,
                self::identicalTo($field),
                $targetAction
            )
            ->willReturn($associationMetadata);
        $this->nestedObjectMetadataHelper->expects(self::never())
            ->method('getLinkedField');
        $this->entityMetadataFactory->expects(self::never())
            ->method('createAndAddFieldMetadata');
        $this->nestedObjectMetadataHelper->expects(self::never())
            ->method('setTargetPropertyPath');

        $result = $this->entityNestedObjectMetadataFactory->createAndAddNestedObjectMetadata(
            $entityMetadata,
            $classMetadata,
            $config,
            $entityClass,
            $fieldName,
            $field,
            $withExcludedProperties,
            $targetAction
        );
        self::assertSame($associationMetadata, $result);
    }

    public function testCreateAndAddNestedObjMetadataForExcludedTargetFieldWhenExclPropertiesShouldNotBeIgnored(): void
    {
        $entityMetadata = new EntityMetadata('Test\Entity');
        $config = new EntityDefinitionConfig();
        $entityClass = 'Test\Class';
        $fieldName = 'testField';
        $field = new EntityDefinitionFieldConfig();
        $withExcludedProperties = true;
        $targetAction = 'testAction';

        $classMetadata = $this->getClassMetadataMock();

        $targetConfig = $field->createAndSetTargetEntity();
        $targetFieldName = 'targetField';
        $targetField = $targetConfig->addField($targetFieldName);
        $targetField->setExcluded();

        $linkedField = new EntityDefinitionFieldConfig();

        $associationMetadata = new AssociationMetadata();
        $associationTargetMetadata = new EntityMetadata('Test\Entity');
        $associationMetadata->setTargetMetadata($associationTargetMetadata);
        $targetPropertyMetadata = new FieldMetadata();

        $this->nestedObjectMetadataHelper->expects(self::once())
            ->method('addNestedObjectAssociation')
            ->with(
                self::identicalTo($entityMetadata),
                $entityClass,
                self::identicalTo($config),
                $fieldName,
                self::identicalTo($field),
                $targetAction
            )
            ->willReturn($associationMetadata);
        $this->nestedObjectMetadataHelper->expects(self::once())
            ->method('getLinkedField')
            ->with(
                self::identicalTo($config),
                $entityClass,
                $fieldName,
                $targetFieldName,
                self::identicalTo($targetField)
            )
            ->willReturn($linkedField);
        $this->entityMetadataFactory->expects(self::once())
            ->method('createAndAddFieldMetadata')
            ->with(
                self::identicalTo($associationTargetMetadata),
                self::identicalTo($classMetadata),
                $targetFieldName,
                self::identicalTo($targetField),
                $targetAction
            )
            ->willReturn($targetPropertyMetadata);
        $this->nestedObjectMetadataHelper->expects(self::once())
            ->method('setTargetPropertyPath')
            ->with(
                self::identicalTo($targetPropertyMetadata),
                $targetFieldName,
                self::identicalTo($targetField),
                $targetAction
            );

        $result = $this->entityNestedObjectMetadataFactory->createAndAddNestedObjectMetadata(
            $entityMetadata,
            $classMetadata,
            $config,
            $entityClass,
            $fieldName,
            $field,
            $withExcludedProperties,
            $targetAction
        );
        self::assertSame($associationMetadata, $result);
    }

    public function testCreateAndAddNestedObjectMetadataForField(): void
    {
        $entityMetadata = new EntityMetadata('Test\Entity');
        $config = new EntityDefinitionConfig();
        $entityClass = 'Test\Class';
        $fieldName = 'testField';
        $field = new EntityDefinitionFieldConfig();
        $withExcludedProperties = false;
        $targetAction = 'testAction';

        $classMetadata = $this->getClassMetadataMock();

        $targetConfig = $field->createAndSetTargetEntity();
        $targetFieldName = 'targetField';
        $targetField = $targetConfig->addField($targetFieldName);

        $linkedField = new EntityDefinitionFieldConfig();

        $associationMetadata = new AssociationMetadata();
        $associationTargetMetadata = new EntityMetadata('Test\Entity');
        $associationMetadata->setTargetMetadata($associationTargetMetadata);
        $targetPropertyMetadata = new FieldMetadata();

        $this->nestedObjectMetadataHelper->expects(self::once())
            ->method('addNestedObjectAssociation')
            ->with(
                self::identicalTo($entityMetadata),
                $entityClass,
                self::identicalTo($config),
                $fieldName,
                self::identicalTo($field),
                $targetAction
            )
            ->willReturn($associationMetadata);
        $this->nestedObjectMetadataHelper->expects(self::once())
            ->method('getLinkedField')
            ->with(
                self::identicalTo($config),
                $entityClass,
                $fieldName,
                $targetFieldName,
                self::identicalTo($targetField)
            )
            ->willReturn($linkedField);
        $this->entityMetadataFactory->expects(self::once())
            ->method('createAndAddFieldMetadata')
            ->with(
                self::identicalTo($associationTargetMetadata),
                self::identicalTo($classMetadata),
                $targetFieldName,
                self::identicalTo($targetField),
                $targetAction
            )
            ->willReturn($targetPropertyMetadata);
        $this->nestedObjectMetadataHelper->expects(self::once())
            ->method('setTargetPropertyPath')
            ->with(
                self::identicalTo($targetPropertyMetadata),
                $targetFieldName,
                self::identicalTo($targetField),
                $targetAction
            );

        $result = $this->entityNestedObjectMetadataFactory->createAndAddNestedObjectMetadata(
            $entityMetadata,
            $classMetadata,
            $config,
            $entityClass,
            $fieldName,
            $field,
            $withExcludedProperties,
            $targetAction
        );
        self::assertSame($associationMetadata, $result);
    }

    public function testCreateAndAddNestedObjectMetadataForMetaProperty(): void
    {
        $entityMetadata = new EntityMetadata('Test\Entity');
        $config = new EntityDefinitionConfig();
        $entityClass = 'Test\Class';
        $fieldName = 'testField';
        $field = new EntityDefinitionFieldConfig();
        $withExcludedProperties = false;
        $targetAction = 'testAction';

        $classMetadata = $this->getClassMetadataMock();

        $targetConfig = $field->createAndSetTargetEntity();
        $targetFieldName = 'targetField';
        $targetField = $targetConfig->addField($targetFieldName);

        $linkedField = new EntityDefinitionFieldConfig();
        $linkedField->setMetaProperty(true);

        $associationMetadata = new AssociationMetadata();
        $associationTargetMetadata = new EntityMetadata('Test\Entity');
        $associationMetadata->setTargetMetadata($associationTargetMetadata);
        $targetPropertyMetadata = new MetaPropertyMetadata();

        $this->nestedObjectMetadataHelper->expects(self::once())
            ->method('addNestedObjectAssociation')
            ->with(
                self::identicalTo($entityMetadata),
                $entityClass,
                self::identicalTo($config),
                $fieldName,
                self::identicalTo($field),
                $targetAction
            )
            ->willReturn($associationMetadata);
        $this->nestedObjectMetadataHelper->expects(self::once())
            ->method('getLinkedField')
            ->with(
                self::identicalTo($config),
                $entityClass,
                $fieldName,
                $targetFieldName,
                self::identicalTo($targetField)
            )
            ->willReturn($linkedField);
        $this->entityMetadataFactory->expects(self::once())
            ->method('createAndAddMetaPropertyMetadata')
            ->with(
                self::identicalTo($associationTargetMetadata),
                self::identicalTo($classMetadata),
                $targetFieldName,
                self::identicalTo($targetField),
                $targetAction
            )
            ->willReturn($targetPropertyMetadata);
        $this->nestedObjectMetadataHelper->expects(self::once())
            ->method('setTargetPropertyPath')
            ->with(
                self::identicalTo($targetPropertyMetadata),
                $targetFieldName,
                self::identicalTo($targetField),
                $targetAction
            );

        $result = $this->entityNestedObjectMetadataFactory->createAndAddNestedObjectMetadata(
            $entityMetadata,
            $classMetadata,
            $config,
            $entityClass,
            $fieldName,
            $field,
            $withExcludedProperties,
            $targetAction
        );
        self::assertSame($associationMetadata, $result);
    }
}
