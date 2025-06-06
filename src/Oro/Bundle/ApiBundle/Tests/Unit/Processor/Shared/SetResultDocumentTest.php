<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\Shared\SetResultDocument;
use Oro\Bundle\ApiBundle\Request\DocumentBuilderInterface;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorTestCase;

class SetResultDocumentTest extends GetProcessorTestCase
{
    private SetResultDocument $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new SetResultDocument();
    }

    public function testProcessWhenResultExistsButNoResponseDocumentBuilder(): void
    {
        $result = [];
        $this->context->setResult($result);
        $this->processor->process($this->context);
        self::assertSame($result, $this->context->getResult());
    }

    public function testProcessWhenResponseDocumentBuilderExists(): void
    {
        $resultDocument = [];
        $documentBuilder = $this->createMock(DocumentBuilderInterface::class);
        $documentBuilder->expects(self::once())
            ->method('getDocument')
            ->willReturn($resultDocument);

        $this->context->setResponseDocumentBuilder($documentBuilder);
        $this->processor->process($this->context);
        self::assertSame($resultDocument, $this->context->getResult());
        self::assertNull($this->context->getResponseDocumentBuilder());
    }

    public function testProcessWhenBothResultAndResponseDocumentBuilderExists(): void
    {
        $result = ['result'];
        $resultDocument = ['resultDocument'];
        $documentBuilder = $this->createMock(DocumentBuilderInterface::class);
        $documentBuilder->expects(self::once())
            ->method('getDocument')
            ->willReturn($resultDocument);

        $this->context->setResult($result);
        $this->context->setResponseDocumentBuilder($documentBuilder);
        $this->processor->process($this->context);
        self::assertSame($resultDocument, $this->context->getResult());
        self::assertNull($this->context->getResponseDocumentBuilder());
    }
}
