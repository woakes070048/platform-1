<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Shared\CompleteErrors;
use Oro\Bundle\ApiBundle\Request\ErrorCompleterInterface;
use Oro\Bundle\ApiBundle\Request\ErrorCompleterRegistry;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class CompleteErrorsTest extends GetProcessorTestCase
{
    private ErrorCompleterInterface&MockObject $errorCompleter;
    private CompleteErrors $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->errorCompleter = $this->createMock(ErrorCompleterInterface::class);

        $errorCompleterRegistry = $this->createMock(ErrorCompleterRegistry::class);
        $errorCompleterRegistry->expects(self::any())
            ->method('getErrorCompleter')
            ->with($this->context->getRequestType())
            ->willReturn($this->errorCompleter);

        $this->processor = new CompleteErrors($errorCompleterRegistry);
    }

    public function testProcessWithoutErrors(): void
    {
        $metadata = new EntityMetadata('Test\Entity');

        $this->errorCompleter->expects(self::never())
            ->method('complete');

        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);
    }

    public function testProcess(): void
    {
        $metadata = new EntityMetadata('Test\Entity');

        $error = Error::createByException(new \Exception('some exception'));

        $this->errorCompleter->expects(self::once())
            ->method('complete')
            ->with(
                self::identicalTo($error),
                self::identicalTo($this->context->getRequestType()),
                self::identicalTo($metadata)
            )
            ->willReturnCallback(function (Error $error) {
                $error->setDetail($error->getInnerException()->getMessage());
            });

        $this->context->addError($error);
        $this->context->setClassName('Test\Entity');
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        $expectedError = Error::createByException(new \Exception('some exception'))
            ->setDetail('some exception');

        self::assertEquals([$expectedError], $this->context->getErrors());
    }

    public function testProcessWhenNoEntityClass(): void
    {
        $error = Error::createByException(new \Exception('some exception'));

        $this->errorCompleter->expects(self::once())
            ->method('complete')
            ->with(self::identicalTo($error), self::identicalTo($this->context->getRequestType()), null)
            ->willReturnCallback(function (Error $error) {
                $error->setDetail($error->getInnerException()->getMessage());
            });

        $this->context->addError($error);
        $this->processor->process($this->context);

        $expectedError = Error::createByException(new \Exception('some exception'))
            ->setDetail('some exception');

        self::assertEquals([$expectedError], $this->context->getErrors());
    }

    public function testProcessWhenEntityTypeWasNotConvertedToEntityClass(): void
    {
        $error = Error::createByException(new \Exception('some exception'));

        $this->errorCompleter->expects(self::once())
            ->method('complete')
            ->with(self::identicalTo($error), self::identicalTo($this->context->getRequestType()), null)
            ->willReturnCallback(function (Error $error) {
                $error->setDetail($error->getInnerException()->getMessage());
            });

        $this->context->addError($error);
        $this->context->setClassName('test');
        $this->processor->process($this->context);

        $expectedError = Error::createByException(new \Exception('some exception'))
            ->setDetail('some exception');

        self::assertEquals([$expectedError], $this->context->getErrors());
    }

    public function testProcessWhenLoadConfigFailed(): void
    {
        $error = Error::createByException(new \Exception('some exception'));

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->willThrowException(new \Exception('load config exception'));
        $this->errorCompleter->expects(self::once())
            ->method('complete')
            ->with(self::identicalTo($error), self::identicalTo($this->context->getRequestType()), null)
            ->willReturnCallback(function (Error $error) {
                $error->setDetail($error->getInnerException()->getMessage());
            });

        $this->context->addError($error);
        $this->context->setClassName('Test\Entity');
        $this->processor->process($this->context);

        $expectedError = Error::createByException(new \Exception('some exception'))
            ->setDetail('some exception');

        self::assertEquals([$expectedError], $this->context->getErrors());
    }

    public function testProcessWhenLoadMetadataFailed(): void
    {
        $error = Error::createByException(new \Exception('some exception'));

        $this->metadataProvider->expects(self::once())
            ->method('getMetadata')
            ->willThrowException(new \Exception('load metadata exception'));
        $this->errorCompleter->expects(self::once())
            ->method('complete')
            ->with(self::identicalTo($error), self::identicalTo($this->context->getRequestType()), null)
            ->willReturnCallback(function (Error $error) {
                $error->setDetail($error->getInnerException()->getMessage());
            });

        $this->context->addError($error);
        $this->context->setClassName('Test\Entity');
        $this->context->setConfig(new EntityDefinitionConfig());
        $this->processor->process($this->context);

        $expectedError = Error::createByException(new \Exception('some exception'))
            ->setDetail('some exception');

        self::assertEquals([$expectedError], $this->context->getErrors());
    }

    public function testRemoveDuplicates(): void
    {
        $this->context->addError(
            Error::create('title1', 'detail1')
                ->setStatusCode(400)
                ->setSource(ErrorSource::createByPropertyPath('path1'))
        );
        $this->context->addError(
            Error::create('title1', 'detail1')
                ->setStatusCode(400)
                ->setSource(ErrorSource::createByPropertyPath('path2'))
        );
        $this->context->addError(
            Error::create('title1', 'detail2')
                ->setStatusCode(400)
                ->setSource(ErrorSource::createByPropertyPath('path1'))
        );
        $this->context->addError(
            Error::create('title2', 'detail1')
                ->setStatusCode(400)
                ->setSource(ErrorSource::createByPropertyPath('path1'))
        );
        $this->context->addError(
            Error::create('title1', 'detail1')
                ->setStatusCode(400)
                ->setSource(ErrorSource::createByPointer('path1'))
        );
        $this->context->addError(
            Error::create('title1', 'detail1')
                ->setStatusCode(400)
                ->setSource(ErrorSource::createByParameter('path1'))
        );
        $this->context->addError(
            Error::create('title1', 'detail1')
                ->setStatusCode(400)
        );
        $this->context->addError(
            Error::create('title1', 'detail1')
                ->setSource(ErrorSource::createByParameter('path1'))
        );
        $this->context->addError(
            Error::create('title1', 'detail1')
        );

        $expectedErrors = $this->context->getErrors();

        // duplicate all errors
        foreach ($expectedErrors as $error) {
            $newError = clone $error;
            if (null !== $error->getSource()) {
                $newError->setSource(clone $error->getSource());
            }
            $this->context->addError($newError);
        }

        $this->processor->process($this->context);
        self::assertSame($expectedErrors, $this->context->getErrors());
    }
}
