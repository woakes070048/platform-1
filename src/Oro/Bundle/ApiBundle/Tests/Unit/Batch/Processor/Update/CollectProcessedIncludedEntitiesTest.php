<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Processor\Update;

use Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateItem;
use Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateItemStatus;
use Oro\Bundle\ApiBundle\Batch\IncludeMapManager;
use Oro\Bundle\ApiBundle\Batch\Model\IncludedData;
use Oro\Bundle\ApiBundle\Batch\Processor\Update\CollectProcessedIncludedEntities;
use Oro\Bundle\ApiBundle\Batch\Processor\UpdateItem\BatchUpdateItemContext;
use Oro\Bundle\ApiBundle\Collection\IncludedEntityCollection;
use Oro\Bundle\ApiBundle\Collection\IncludedEntityData;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\Create\CreateContext;
use Oro\Bundle\ApiBundle\Processor\Delete\DeleteContext;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerRegistry;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\GaufretteBundle\FileManager;
use PHPUnit\Framework\MockObject\MockObject;

class CollectProcessedIncludedEntitiesTest extends BatchUpdateProcessorTestCase
{
    private const int ASYNC_OPERATION_ID = 123;

    private IncludeMapManager&MockObject $includeMapManager;
    private ValueNormalizer&MockObject $valueNormalizer;
    private EntityIdTransformerRegistry&MockObject $entityIdTransformerRegistry;
    private FileManager&MockObject $fileManager;
    private CollectProcessedIncludedEntities $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->includeMapManager = $this->createMock(IncludeMapManager::class);
        $this->valueNormalizer = $this->createMock(ValueNormalizer::class);
        $this->entityIdTransformerRegistry = $this->createMock(EntityIdTransformerRegistry::class);
        $this->fileManager = $this->createMock(FileManager::class);

        $this->context->setFileManager($this->fileManager);
        $this->context->setOperationId(self::ASYNC_OPERATION_ID);

        $this->processor = new CollectProcessedIncludedEntities(
            $this->includeMapManager,
            $this->valueNormalizer,
            $this->entityIdTransformerRegistry
        );
    }

    public function testProcessWhenItemsAlreadyCollected(): void
    {
        $this->includeMapManager->expects(self::never())
            ->method('moveToProcessed');

        $this->context->setProcessed(CollectProcessedIncludedEntities::OPERATION_NAME);
        $this->context->setIncludedData($this->createMock(IncludedData::class));
        $this->context->setBatchItems([$this->createMock(BatchUpdateItem::class)]);
        $this->context->setProcessedItemStatuses([BatchUpdateItemStatus::NO_ERRORS]);
        $this->processor->process($this->context);
    }

    public function testProcessWithoutIncludedData(): void
    {
        $this->includeMapManager->expects(self::never())
            ->method('moveToProcessed');

        $this->context->setBatchItems([$this->createMock(BatchUpdateItem::class)]);
        $this->context->setProcessedItemStatuses([BatchUpdateItemStatus::NO_ERRORS]);
        $this->processor->process($this->context);
        self::assertFalse($this->context->isProcessed(CollectProcessedIncludedEntities::OPERATION_NAME));
    }

    public function testProcessWhenBatchItemsAreEmpty(): void
    {
        $this->includeMapManager->expects(self::never())
            ->method('moveToProcessed');

        $this->context->setIncludedData($this->createMock(IncludedData::class));
        $this->context->setBatchItems([]);
        $this->context->setProcessedItemStatuses([]);
        $this->processor->process($this->context);
        self::assertTrue($this->context->isProcessed(CollectProcessedIncludedEntities::OPERATION_NAME));
    }

    public function testProcessWhenBatchItemProcessed(): void
    {
        $requestType = new RequestType([RequestType::JSON_API]);

        $item = $this->createMock(BatchUpdateItem::class);
        $itemStatus = BatchUpdateItemStatus::NO_ERRORS;
        $itemContext = $this->createMock(BatchUpdateItemContext::class);
        $itemTargetContext = $this->createMock(CreateContext::class);
        $itemIncludedEntities = new IncludedEntityCollection();

        $includedEntity = $this->createMock(\stdClass::class);
        $includedEntityClass = 'Test\Entity';
        $includedEntityType = 'test_entity_type';
        $includedEntityId = 100;
        $includedEntityDatabaseId = 1000;
        $includedEntityData = $this->createMock(IncludedEntityData::class);
        $includedEntityMetadata = $this->createMock(EntityMetadata::class);
        $includedEntityData->expects(self::once())
            ->method('getMetadata')
            ->willReturn($includedEntityMetadata);
        $includedEntityMetadata->expects(self::once())
            ->method('getIdentifierValue')
            ->with(self::identicalTo($includedEntity))
            ->willReturn($includedEntityDatabaseId);
        $itemIncludedEntities->add($includedEntity, $includedEntityClass, $includedEntityId, $includedEntityData);

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with($includedEntityClass, DataType::ENTITY_TYPE, self::identicalTo($requestType))
            ->willReturn($includedEntityType);

        $entityIdTransformer = $this->createMock(EntityIdTransformerInterface::class);
        $entityIdTransformer->expects(self::exactly(2))
            ->method('transform')
            ->willReturnCallback(function ($id) {
                return (string)$id;
            });

        $this->entityIdTransformerRegistry->expects(self::once())
            ->method('getEntityIdTransformer')
            ->willReturn($entityIdTransformer);

        $item->expects(self::once())
            ->method('getContext')
            ->willReturn($itemContext);
        $itemContext->expects(self::once())
            ->method('getTargetContext')
            ->willReturn($itemTargetContext);
        $itemTargetContext->expects(self::once())
            ->method('getIncludedEntities')
            ->willReturn($itemIncludedEntities);
        $itemTargetContext->expects(self::once())
            ->method('getRequestType')
            ->willReturn($requestType);

        $this->includeMapManager->expects(self::once())
            ->method('moveToProcessed')
            ->with(
                self::identicalTo($this->fileManager),
                self::ASYNC_OPERATION_ID,
                self::identicalTo(
                    [[$includedEntityType, (string)$includedEntityId, (string)$includedEntityDatabaseId]]
                )
            );

        $this->context->setIncludedData($this->createMock(IncludedData::class));
        $this->context->setBatchItems([$item]);
        $this->context->setProcessedItemStatuses([$itemStatus]);
        $this->processor->process($this->context);
        self::assertTrue($this->context->isProcessed(CollectProcessedIncludedEntities::OPERATION_NAME));
    }

    public function testProcessWhenBatchItemNotProcessed(): void
    {
        $requestType = new RequestType([RequestType::JSON_API]);

        $item = $this->createMock(BatchUpdateItem::class);
        $itemStatus = BatchUpdateItemStatus::NO_ERRORS;
        $itemContext = $this->createMock(BatchUpdateItemContext::class);
        $itemTargetContext = $this->createMock(CreateContext::class);
        $itemIncludedEntities = new IncludedEntityCollection();

        $includedEntity = $this->createMock(\stdClass::class);
        $includedEntityClass = 'Test\Entity';
        $includedEntityId = 'test_entity_id';
        $includedEntityData = $this->createMock(IncludedEntityData::class);
        $includedEntityMetadata = $this->createMock(EntityMetadata::class);
        $includedEntityData->expects(self::once())
            ->method('getMetadata')
            ->willReturn($includedEntityMetadata);
        $includedEntityMetadata->expects(self::once())
            ->method('getIdentifierValue')
            ->with(self::identicalTo($includedEntity))
            ->willReturn(null);
        $itemIncludedEntities->add($includedEntity, $includedEntityClass, $includedEntityId, $includedEntityData);

        $this->valueNormalizer->expects(self::never())
            ->method('normalizeValue');

        $item->expects(self::once())
            ->method('getContext')
            ->willReturn($itemContext);
        $itemContext->expects(self::once())
            ->method('getTargetContext')
            ->willReturn($itemTargetContext);
        $itemTargetContext->expects(self::once())
            ->method('getIncludedEntities')
            ->willReturn($itemIncludedEntities);
        $itemTargetContext->expects(self::once())
            ->method('getRequestType')
            ->willReturn($requestType);

        $this->includeMapManager->expects(self::never())
            ->method('moveToProcessed');

        $this->context->setIncludedData($this->createMock(IncludedData::class));
        $this->context->setBatchItems([$item]);
        $this->context->setProcessedItemStatuses([$itemStatus]);
        $this->processor->process($this->context);
        self::assertTrue($this->context->isProcessed(CollectProcessedIncludedEntities::OPERATION_NAME));
    }

    public function testProcessWhenIncludedEntityDoesNotHaveMetadata(): void
    {
        $requestType = new RequestType([RequestType::JSON_API]);

        $item = $this->createMock(BatchUpdateItem::class);
        $itemStatus = BatchUpdateItemStatus::NO_ERRORS;
        $itemContext = $this->createMock(BatchUpdateItemContext::class);
        $itemTargetContext = $this->createMock(CreateContext::class);
        $itemIncludedEntities = new IncludedEntityCollection();

        $includedEntity = $this->createMock(\stdClass::class);
        $includedEntityClass = 'Test\Entity';
        $includedEntityId = 'test_entity_id';
        $includedEntityData = $this->createMock(IncludedEntityData::class);
        $includedEntityData->expects(self::once())
            ->method('getMetadata')
            ->willReturn(null);
        $itemIncludedEntities->add($includedEntity, $includedEntityClass, $includedEntityId, $includedEntityData);

        $this->valueNormalizer->expects(self::never())
            ->method('normalizeValue');

        $item->expects(self::once())
            ->method('getContext')
            ->willReturn($itemContext);
        $itemContext->expects(self::once())
            ->method('getTargetContext')
            ->willReturn($itemTargetContext);
        $itemTargetContext->expects(self::once())
            ->method('getIncludedEntities')
            ->willReturn($itemIncludedEntities);
        $itemTargetContext->expects(self::once())
            ->method('getRequestType')
            ->willReturn($requestType);

        $this->includeMapManager->expects(self::never())
            ->method('moveToProcessed');

        $this->context->setIncludedData($this->createMock(IncludedData::class));
        $this->context->setBatchItems([$item]);
        $this->context->setProcessedItemStatuses([$itemStatus]);
        $this->processor->process($this->context);
        self::assertTrue($this->context->isProcessed(CollectProcessedIncludedEntities::OPERATION_NAME));
    }

    public function testProcessWhenIncludedEntitiesCollectionIsEmpty(): void
    {
        $requestType = new RequestType([RequestType::JSON_API]);

        $item = $this->createMock(BatchUpdateItem::class);
        $itemStatus = BatchUpdateItemStatus::NO_ERRORS;
        $itemContext = $this->createMock(BatchUpdateItemContext::class);
        $itemTargetContext = $this->createMock(CreateContext::class);
        $itemIncludedEntities = new IncludedEntityCollection();

        $this->valueNormalizer->expects(self::never())
            ->method('normalizeValue');

        $item->expects(self::once())
            ->method('getContext')
            ->willReturn($itemContext);
        $itemContext->expects(self::once())
            ->method('getTargetContext')
            ->willReturn($itemTargetContext);
        $itemTargetContext->expects(self::once())
            ->method('getIncludedEntities')
            ->willReturn($itemIncludedEntities);
        $itemTargetContext->expects(self::once())
            ->method('getRequestType')
            ->willReturn($requestType);

        $this->includeMapManager->expects(self::never())
            ->method('moveToProcessed');

        $this->context->setIncludedData($this->createMock(IncludedData::class));
        $this->context->setBatchItems([$item]);
        $this->context->setProcessedItemStatuses([$itemStatus]);
        $this->processor->process($this->context);
        self::assertTrue($this->context->isProcessed(CollectProcessedIncludedEntities::OPERATION_NAME));
    }

    public function testProcessWhenIncludedEntitiesCollectionIsNull(): void
    {
        $item = $this->createMock(BatchUpdateItem::class);
        $itemStatus = BatchUpdateItemStatus::NO_ERRORS;
        $itemContext = $this->createMock(BatchUpdateItemContext::class);
        $itemTargetContext = $this->createMock(CreateContext::class);

        $this->valueNormalizer->expects(self::never())
            ->method('normalizeValue');

        $item->expects(self::once())
            ->method('getContext')
            ->willReturn($itemContext);
        $itemContext->expects(self::once())
            ->method('getTargetContext')
            ->willReturn($itemTargetContext);
        $itemTargetContext->expects(self::once())
            ->method('getIncludedEntities')
            ->willReturn(null);
        $itemTargetContext->expects(self::never())
            ->method('getRequestType');

        $this->includeMapManager->expects(self::never())
            ->method('moveToProcessed');

        $this->context->setIncludedData($this->createMock(IncludedData::class));
        $this->context->setBatchItems([$item]);
        $this->context->setProcessedItemStatuses([$itemStatus]);
        $this->processor->process($this->context);
        self::assertTrue($this->context->isProcessed(CollectProcessedIncludedEntities::OPERATION_NAME));
    }

    public function testProcessForUnsupportedBatchItemAction(): void
    {
        $item = $this->createMock(BatchUpdateItem::class);
        $itemStatus = BatchUpdateItemStatus::NO_ERRORS;
        $itemContext = $this->createMock(BatchUpdateItemContext::class);
        $itemTargetContext = $this->createMock(DeleteContext::class);

        $this->valueNormalizer->expects(self::never())
            ->method('normalizeValue');

        $item->expects(self::once())
            ->method('getContext')
            ->willReturn($itemContext);
        $itemContext->expects(self::once())
            ->method('getTargetContext')
            ->willReturn($itemTargetContext);
        $itemTargetContext->expects(self::never())
            ->method('getRequestType');

        $this->includeMapManager->expects(self::never())
            ->method('moveToProcessed');

        $this->context->setIncludedData($this->createMock(IncludedData::class));
        $this->context->setBatchItems([$item]);
        $this->context->setProcessedItemStatuses([$itemStatus]);
        $this->processor->process($this->context);
        self::assertTrue($this->context->isProcessed(CollectProcessedIncludedEntities::OPERATION_NAME));
    }

    public function testProcessWhenBatchItemHasErrors(): void
    {
        $item = $this->createMock(BatchUpdateItem::class);
        $itemStatus = BatchUpdateItemStatus::HAS_ERRORS;

        $this->valueNormalizer->expects(self::never())
            ->method('normalizeValue');

        $item->expects(self::never())
            ->method('getContext');

        $this->includeMapManager->expects(self::never())
            ->method('moveToProcessed');

        $this->context->setIncludedData($this->createMock(IncludedData::class));
        $this->context->setBatchItems([$item]);
        $this->context->setProcessedItemStatuses([$itemStatus]);
        $this->processor->process($this->context);
        self::assertTrue($this->context->isProcessed(CollectProcessedIncludedEntities::OPERATION_NAME));
    }
}
