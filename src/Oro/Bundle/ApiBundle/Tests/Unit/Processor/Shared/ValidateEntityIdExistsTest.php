<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Shared\ValidateEntityIdExists;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorTestCase;

class ValidateEntityIdExistsTest extends GetProcessorTestCase
{
    private ValidateEntityIdExists $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new ValidateEntityIdExists();
    }

    public function testProcess(): void
    {
        $this->context->setId(123);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessWhenIdIsStringWithValue0(): void
    {
        $this->context->setId('0');
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessWhenIdIsIntegerWithValue0(): void
    {
        $this->context->setId(0);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessWhenNoId(): void
    {
        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);

        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        self::assertEquals(
            [
                Error::createValidationError(
                    'entity identifier constraint',
                    'The identifier of an entity must be set in the context.'
                )
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWhenIdIsEmpty(): void
    {
        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);

        $this->context->setId('');
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        self::assertEquals(
            [
                Error::createValidationError(
                    'entity identifier constraint',
                    'The identifier of an entity must be set in the context.'
                )
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWhenNoIdAndEntityDoesNotHaveIdentifierFields(): void
    {
        $metadata = new EntityMetadata('Test\Entity');

        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
    }
}
