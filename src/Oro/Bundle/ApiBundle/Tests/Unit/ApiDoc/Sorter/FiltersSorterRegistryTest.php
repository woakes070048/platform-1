<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\ApiDoc\Sorter;

use Oro\Bundle\ApiBundle\ApiDoc\Sorter\FiltersSorterInterface;
use Oro\Bundle\ApiBundle\ApiDoc\Sorter\FiltersSorterRegistry;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class FiltersSorterRegistryTest extends TestCase
{
    private FiltersSorterInterface&MockObject $defaultSorter;
    private FiltersSorterInterface&MockObject $firstSorter;
    private FiltersSorterInterface&MockObject $secondSorter;
    private ContainerInterface&MockObject $container;

    #[\Override]
    protected function setUp(): void
    {
        $this->defaultSorter = $this->createMock(FiltersSorterInterface::class);
        $this->firstSorter = $this->createMock(FiltersSorterInterface::class);
        $this->secondSorter = $this->createMock(FiltersSorterInterface::class);
        $this->container = $this->createMock(ContainerInterface::class);
    }

    private function getRegistry(array $sorters): FiltersSorterRegistry
    {
        return new FiltersSorterRegistry(
            $sorters,
            $this->container,
            new RequestExpressionMatcher()
        );
    }

    public function testGetSorterForUnsupportedRequestType(): void
    {
        $registry = $this->getRegistry([
            ['first_sorter', 'first'],
            ['second_sorter', 'second']
        ]);

        $requestType = new RequestType(['rest', 'another']);
        self::assertNull($registry->getSorter($requestType));
        // test internal cache
        self::assertNull($registry->getSorter($requestType));
    }

    public function testGetSorterShouldReturnDefaultSorterForNotFirstAndSecondRequestType(): void
    {
        $registry = $this->getRegistry([
            ['first_sorter', 'first'],
            ['second_sorter', 'second'],
            ['default_sorter', null]
        ]);

        $this->container->expects(self::once())
            ->method('get')
            ->with('default_sorter')
            ->willReturn($this->defaultSorter);

        $requestType = new RequestType(['rest']);
        self::assertSame($this->defaultSorter, $registry->getSorter($requestType));
        // test internal cache
        self::assertSame($this->defaultSorter, $registry->getSorter($requestType));
    }

    public function testGetSorterShouldReturnFirstSorterForFirstRequestType(): void
    {
        $registry = $this->getRegistry([
            ['first_sorter', 'first'],
            ['second_sorter', 'second'],
            ['default_sorter', null]
        ]);

        $this->container->expects(self::once())
            ->method('get')
            ->with('first_sorter')
            ->willReturn($this->firstSorter);

        $requestType = new RequestType(['rest', 'first']);
        self::assertSame($this->firstSorter, $registry->getSorter($requestType));
        // test internal cache
        self::assertSame($this->firstSorter, $registry->getSorter($requestType));
    }

    public function testGetSorterShouldReturnSecondSorterForSecondRequestType(): void
    {
        $registry = $this->getRegistry([
            ['first_sorter', 'first'],
            ['second_sorter', 'second'],
            ['default_sorter', null]
        ]);

        $this->container->expects(self::once())
            ->method('get')
            ->with('second_sorter')
            ->willReturn($this->secondSorter);

        $requestType = new RequestType(['rest', 'second']);
        self::assertSame($this->secondSorter, $registry->getSorter($requestType));
        // test internal cache
        self::assertSame($this->secondSorter, $registry->getSorter($requestType));
    }

    public function testGetSorterShouldReturnDefaultSorterIfSpecificSorterNotFound(): void
    {
        $registry = $this->getRegistry([
            ['first_sorter', 'first'],
            ['default_sorter', null]
        ]);

        $this->container->expects(self::once())
            ->method('get')
            ->with('default_sorter')
            ->willReturn($this->defaultSorter);

        $requestType = new RequestType(['rest', 'another']);
        self::assertSame($this->defaultSorter, $registry->getSorter($requestType));
        // test internal cache
        self::assertSame($this->defaultSorter, $registry->getSorter($requestType));
    }

    public function testReset(): void
    {
        $registry = $this->getRegistry([
            ['first_sorter', 'first'],
            ['second_sorter', 'second'],
            ['default_sorter', null]
        ]);

        $this->container->expects(self::exactly(2))
            ->method('get')
            ->with('first_sorter')
            ->willReturn($this->firstSorter);

        $requestType = new RequestType(['rest', 'first']);
        self::assertSame($this->firstSorter, $registry->getSorter($requestType));
        // test internal cache
        self::assertSame($this->firstSorter, $registry->getSorter($requestType));

        $registry->reset();
        self::assertSame($this->firstSorter, $registry->getSorter($requestType));
    }
}
