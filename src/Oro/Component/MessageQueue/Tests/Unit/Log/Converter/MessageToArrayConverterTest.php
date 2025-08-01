<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Log\Converter;

use Oro\Component\MessageQueue\Log\Converter\MessageToArrayConverter;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use PHPUnit\Framework\TestCase;

class MessageToArrayConverterTest extends TestCase
{
    private MessageToArrayConverter $converter;

    #[\Override]
    protected function setUp(): void
    {
        $this->converter = new MessageToArrayConverter();
    }

    public function testConvertRequiredProperties(): void
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::once())
            ->method('getMessageId')
            ->willReturn('123');
        $message->expects(self::once())
            ->method('getBody')
            ->willReturn('message body');
        $message->expects(self::once())
            ->method('getProperties')
            ->willReturn([]);
        $message->expects(self::once())
            ->method('getHeaders')
            ->willReturn([]);
        $message->expects(self::once())
            ->method('isRedelivered')
            ->willReturn(false);

        self::assertEquals(
            [
                'id'   => '123',
                'body' => 'message body'
            ],
            $this->converter->convert($message)
        );
    }

    public function testConvertAllProperties(): void
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::once())
            ->method('getMessageId')
            ->willReturn('123');
        $message->expects(self::once())
            ->method('getBody')
            ->willReturn('message body');
        $message->expects(self::once())
            ->method('getProperties')
            ->willReturn(['property1' => 'val1']);
        $message->expects(self::once())
            ->method('getHeaders')
            ->willReturn(['header1' => 'val1']);
        $message->expects(self::once())
            ->method('isRedelivered')
            ->willReturn(true);

        self::assertEquals(
            [
                'id'          => '123',
                'body'        => 'message body',
                'properties'  => [
                    'property1' => 'val1'
                ],
                'headers'     => [
                    'header1' => 'val1'
                ],
                'redelivered' => true
            ],
            $this->converter->convert($message)
        );
    }
}
