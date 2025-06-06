<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Model\Strategy;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Oro\Bundle\EntityMergeBundle\Data\FieldData;
use Oro\Bundle\EntityMergeBundle\Doctrine\DoctrineHelper;
use Oro\Bundle\EntityMergeBundle\Metadata\DoctrineMetadata;
use Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;
use Oro\Bundle\EntityMergeBundle\Model\Accessor\DefaultAccessor;
use Oro\Bundle\EntityMergeBundle\Model\Accessor\DelegateAccessor;
use Oro\Bundle\EntityMergeBundle\Model\Accessor\InverseAssociationAccessor;
use Oro\Bundle\EntityMergeBundle\Model\MergeModes;
use Oro\Bundle\EntityMergeBundle\Model\Strategy\UniteStrategy;
use Oro\Bundle\EntityMergeBundle\Tests\Unit\Stub\CollectionItemStub;
use Oro\Bundle\EntityMergeBundle\Tests\Unit\Stub\EntityStub;
use PHPUnit\Framework\TestCase;

class UniteStrategyTest extends TestCase
{
    public function testNotSupports(): void
    {
        $fieldData = $this->createFieldData(
            $this->createEntityData(),
            ['merge_modes' => [MergeModes::REPLACE]]
        );

        $strategy = $this->getStrategy([]);

        $this->assertFalse($strategy->supports($fieldData));
    }

    public function testMergeManyToOne(): void
    {
        $masterEntity = new EntityStub(1);
        $sourceEntity = new EntityStub(2);
        $item1 = new CollectionItemStub(1);
        $item2 = new CollectionItemStub(2);
        $item1->setEntityStub($masterEntity);
        $item2->setEntityStub($sourceEntity);

        $doctrineMetadata = [
            'sourceEntity' => 'Test\SourceEntity',
            'fieldName' => 'entityStub',
            'targetEntity' => EntityStub::class,
            'type' => ClassMetadataInfo::MANY_TO_ONE,
        ];

        $fieldMetadata = [
            'field_name' => 'collection',
            'merge_modes' => [MergeModes::UNITE],
            'source_class_name' => EntityStub::class,
        ];

        $entityData = $this->createEntityData(
            $masterEntity,
            $sourceEntity,
            ['name' => CollectionItemStub::class]
        );

        $fieldData = $this->createFieldData($entityData, $fieldMetadata, $doctrineMetadata);
        $strategy = $this->getStrategy([$item1, $item2]);

        $this->assertTrue($strategy->supports($fieldData));

        $strategy->merge($fieldData);

        $this->assertSame($masterEntity, $item1->getEntityStub());
        $this->assertSame($masterEntity, $item2->getEntityStub());
    }

    public function testMergeOneToMany(): void
    {
        $masterEntity = new EntityStub(1);
        $sourceEntity = new EntityStub(2);
        $collectionItem1 = new CollectionItemStub(1);
        $collectionItem2 = new CollectionItemStub(2);
        $masterEntity->addCollectionItem($collectionItem1);
        $sourceEntity->addCollectionItem($collectionItem2);

        $doctrineMetadata = [
            'fieldName' => 'collection',
            'targetEntity' => CollectionItemStub::class,
            'type' => ClassMetadataInfo::ONE_TO_MANY,
            'orphanRemoval' => true,
        ];

        $fieldMetadata = [
            'field_name' => 'collection',
            'merge_modes' => [MergeModes::UNITE],
        ];

        $entityData = $this->createEntityData(
            $masterEntity,
            $sourceEntity,
            ['name' => EntityStub::class]
        );

        $fieldData = $this->createFieldData($entityData, $fieldMetadata, $doctrineMetadata);
        $strategy = $this->getStrategy([$collectionItem1, $collectionItem2]);

        $this->assertTrue($strategy->supports($fieldData));

        $strategy->merge($fieldData);
        $collection = $masterEntity->getCollection();

        $this->assertCount(2, $collection);
        $this->assertEquals(1, $collection[0]->getId());
        $this->assertEquals(2, $collection[1]->getId());
        $this->assertNotSame($collection[0], $collectionItem1);
        $this->assertNotSame($collection[1], $collectionItem2);
    }

    public function testMergeManyToMany(): void
    {
        $masterEntity = new EntityStub(1);
        $sourceEntity = new EntityStub(2);
        $collectionItem1 = new CollectionItemStub(1);
        $collectionItem2 = new CollectionItemStub(2);
        $masterEntity->addCollectionItem($collectionItem1);
        $sourceEntity->addCollectionItem($collectionItem2);

        $doctrineMetadata = [
            'fieldName' => 'collection',
            'targetEntity' => CollectionItemStub::class,
            'type' => ClassMetadataInfo::MANY_TO_MANY,
        ];

        $fieldMetadata = [
            'field_name' => 'collection',
            'merge_modes' => [MergeModes::UNITE],
        ];

        $entityData = $this->createEntityData(
            $masterEntity,
            $sourceEntity,
            ['name' => EntityStub::class]
        );

        $fieldData = $this->createFieldData($entityData, $fieldMetadata, $doctrineMetadata);
        $strategy = $this->getStrategy([$collectionItem1, $collectionItem2]);

        $this->assertTrue($strategy->supports($fieldData));

        $strategy->merge($fieldData);
        $collection = $masterEntity->getCollection();

        $this->assertCount(2, $collection);
        $this->assertTrue($collection->contains($collectionItem1));
        $this->assertTrue($collection->contains($collectionItem2));
    }

    private function getStrategy(array $relatedEntities): UniteStrategy
    {
        $repository = $this->createMock(EntityRepository::class);

        $repository->expects($this->any())
            ->method('findBy')
            ->willReturnCallback(function (array $values) use ($relatedEntities) {
                return [
                    $relatedEntities[$values['entityStub']->getId() - 1]
                ];
            });

        $doctrineHelper = $this->createMock(DoctrineHelper::class);

        $doctrineHelper->expects($this->any())
            ->method('getEntityIdentifierValue')
            ->willReturnCallback(function ($value) {
                return $value->getId();
            });

        $doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->willReturn($repository);

        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $accessor = new DelegateAccessor([
            new InverseAssociationAccessor($propertyAccessor, $doctrineHelper),
            new DefaultAccessor($propertyAccessor),
        ]);

        return new UniteStrategy($accessor, $doctrineHelper);
    }

    private function createFieldData(EntityData $entityData, array $metadata, array $doctrineMetadata = []): FieldData
    {
        $doctrineMetadata = new DoctrineMetadata($doctrineMetadata);
        $fieldMetadata = new FieldMetadata($metadata, $doctrineMetadata);
        $fieldMetadata->setEntityMetadata($entityData->getMetadata());

        return new FieldData($entityData, $fieldMetadata);
    }

    private function createEntityData(
        ?object $masterEntity = null,
        ?object $sourceEntity = null,
        array $doctrineMetadata = []
    ): EntityData {
        $doctrineMetadata = new DoctrineMetadata($doctrineMetadata);
        $metadata = new EntityMetadata([], $doctrineMetadata);
        $entityData = new EntityData($metadata, [$masterEntity, $sourceEntity]);
        $entityData->setMasterEntity($masterEntity);

        return $entityData;
    }
}
