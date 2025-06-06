<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Options;

use Oro\Bundle\ApiBundle\Processor\Options\OptionsContext;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use PHPUnit\Framework\TestCase;

class OptionsContextTest extends TestCase
{
    private OptionsContext $context;

    #[\Override]
    protected function setUp(): void
    {
        $this->context = new OptionsContext(
            $this->createMock(ConfigProvider::class),
            $this->createMock(MetadataProvider::class)
        );
    }

    public function testActionType(): void
    {
        $this->context->setActionType('item');
        self::assertEquals('item', $this->context->getActionType());
        self::assertEquals('item', $this->context->get('actionType'));

        $this->context->setActionType('list');
        self::assertEquals('list', $this->context->getActionType());
        self::assertEquals('list', $this->context->get('actionType'));

        $this->context->setActionType('subresource');
        self::assertEquals('subresource', $this->context->getActionType());
        self::assertEquals('subresource', $this->context->get('actionType'));

        $this->context->setActionType('relationship');
        self::assertEquals('relationship', $this->context->getActionType());
        self::assertEquals('relationship', $this->context->get('actionType'));
    }

    public function testGetActionTypeWhenItDoesNotSet(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('The action type is not set yet.');

        $this->context->getActionType();
    }

    public function testSetInvalidActionType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The action type must be one of item, list, subresource, relationship. Given: another.'
        );

        $this->context->setActionType('another');
    }

    public function testId(): void
    {
        self::assertNull($this->context->getId());

        $this->context->setId(123);
        self::assertSame(123, $this->context->getId());
        self::assertSame(123, $this->context->get('id'));
    }
}
