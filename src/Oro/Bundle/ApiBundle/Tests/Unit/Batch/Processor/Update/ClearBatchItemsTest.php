<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Processor\Update;

use Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateItem;
use Oro\Bundle\ApiBundle\Batch\Processor\Update\ClearBatchItems;

class ClearBatchItemsTest extends BatchUpdateProcessorTestCase
{
    private ClearBatchItems $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new ClearBatchItems();
    }

    public function testProcess(): void
    {
        $this->context->setBatchItems([$this->createMock(BatchUpdateItem::class)]);
        $this->processor->process($this->context);

        self::assertNull($this->context->getBatchItems());
    }
}
