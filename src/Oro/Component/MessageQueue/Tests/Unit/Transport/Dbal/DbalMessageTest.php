<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Transport\Dbal;

use Oro\Component\MessageQueue\Transport\Dbal\DbalMessage;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use PHPUnit\Framework\TestCase;

class DbalMessageTest extends TestCase
{
    use EntityTestCaseTrait;

    public function testProperties(): void
    {
        $properties = [
            ['body', 'message body', ''],
            ['properties', ['propertyKey' => 'value'], []],
            ['headers', ['headerKey' => 'value'], []],
            ['redelivered', true, false],
            ['id', 25, null],
        ];

        $message = new DbalMessage();
        $this->assertPropertyAccessors($message, $properties);
    }
}
