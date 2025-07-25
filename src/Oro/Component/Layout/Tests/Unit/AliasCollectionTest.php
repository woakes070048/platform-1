<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\AliasCollection;
use Oro\Component\Layout\Exception\AliasAlreadyExistsException;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AliasCollectionTest extends TestCase
{
    private AliasCollection $aliasCollection;

    #[\Override]
    protected function setUp(): void
    {
        $this->aliasCollection = new AliasCollection();
    }

    public function testIsEmpty(): void
    {
        $this->assertTrue($this->aliasCollection->isEmpty());

        $this->aliasCollection->add('test_alias', 'test_id');
        $this->assertFalse($this->aliasCollection->isEmpty());

        $this->aliasCollection->remove('test_alias');
        $this->assertTrue($this->aliasCollection->isEmpty());
    }

    public function testClear(): void
    {
        $this->aliasCollection->add('test_alias', 'test_id');

        $this->aliasCollection->clear();
        $this->assertTrue($this->aliasCollection->isEmpty());
    }

    public function testGetAliases(): void
    {
        $this->aliasCollection->add('test_alias', 'test_id');
        $this->aliasCollection->add('another_alias', 'test_alias');
        $this->assertEquals(
            ['test_alias', 'another_alias'],
            $this->aliasCollection->getAliases('test_id')
        );
        $this->assertEquals(
            [],
            $this->aliasCollection->getAliases('unknown')
        );
    }

    public function testAdd(): void
    {
        $this->aliasCollection->add('test_alias', 'test_id');
        $this->aliasCollection->add('another_alias', 'test_alias');
        $this->assertTrue($this->aliasCollection->has('test_alias'));
        $this->assertTrue($this->aliasCollection->has('another_alias'));
        $this->assertEquals('test_id', $this->aliasCollection->getId('test_alias'));
        $this->assertEquals('test_id', $this->aliasCollection->getId('another_alias'));
    }

    public function testAddDuplicate(): void
    {
        $this->aliasCollection->add('test_alias', 'test_id');
        $this->aliasCollection->add('test_alias', 'test_id');
        $this->assertTrue($this->aliasCollection->has('test_alias'));
    }

    public function testRedefine(): void
    {
        $this->expectException(AliasAlreadyExistsException::class);
        $this->expectExceptionMessage(
            'The "test_alias" string cannot be used as an alias for "another_id" item'
            . ' because it is already used for "test_id" item.'
        );

        $this->aliasCollection->add('test_alias', 'test_id');
        $this->aliasCollection->add('test_alias', 'another_id');
    }

    public function testRemove(): void
    {
        // another_alias -> test_alias -> test_id
        $this->aliasCollection->add('test_alias', 'test_id');
        $this->aliasCollection->add('another_alias', 'test_alias');
        $this->assertTrue($this->aliasCollection->has('test_alias'));
        $this->assertTrue($this->aliasCollection->has('another_alias'));

        $this->aliasCollection->remove('another_alias');
        $this->assertTrue($this->aliasCollection->has('test_alias'));
        $this->assertFalse($this->aliasCollection->has('another_alias'));

        $this->aliasCollection->remove('test_alias');
        $this->assertFalse($this->aliasCollection->has('test_alias'));
    }

    public function testRemoveIntermediateAlias(): void
    {
        // last_alias -> another_alias -> test_alias -> test_id
        $this->aliasCollection->add('test_alias', 'test_id');
        $this->aliasCollection->add('another_alias', 'test_alias');
        $this->aliasCollection->add('last_alias', 'another_alias');
        $this->assertTrue($this->aliasCollection->has('test_alias'));
        $this->assertTrue($this->aliasCollection->has('another_alias'));

        $this->aliasCollection->remove('test_alias');
        $this->assertFalse($this->aliasCollection->has('test_alias'));
        $this->assertFalse($this->aliasCollection->has('another_alias'));
        $this->assertFalse($this->aliasCollection->has('last_alias'));
    }

    public function testRemoveById(): void
    {
        // another_alias -> test_alias -> test_id
        $this->aliasCollection->add('test_alias', 'test_id');
        $this->aliasCollection->add('another_alias', 'test_alias');
        $this->assertTrue($this->aliasCollection->has('test_alias'));
        $this->assertTrue($this->aliasCollection->has('another_alias'));

        $this->aliasCollection->removeById('test_id');
        $this->assertFalse($this->aliasCollection->has('test_alias'));
        $this->assertFalse($this->aliasCollection->has('another_alias'));
    }

    public function testGetIdUndefined(): void
    {
        $this->assertNull($this->aliasCollection->getId('test_alias'));
    }

    /**
     * No any exception is expected
     */
    public function testRemoveUndefined(): void
    {
        $this->aliasCollection->remove('test_alias');
    }

    /**
     * No any exception is expected
     */
    public function testRemoveByIdUndefined(): void
    {
        $this->aliasCollection->removeById('test_id');
    }
}
