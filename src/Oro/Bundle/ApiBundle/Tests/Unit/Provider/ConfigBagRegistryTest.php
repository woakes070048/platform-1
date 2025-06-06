<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Provider\ConfigBag;
use Oro\Bundle\ApiBundle\Provider\ConfigBagInterface;
use Oro\Bundle\ApiBundle\Provider\ConfigBagRegistry;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ConfigBagRegistryTest extends TestCase
{
    private ConfigBagInterface&MockObject $defaultConfigBag;
    private ConfigBagInterface&MockObject $firstConfigBag;
    private ConfigBagInterface&MockObject $secondConfigBag;
    private ContainerInterface&MockObject $container;

    #[\Override]
    protected function setUp(): void
    {
        $this->defaultConfigBag = $this->createMock(ConfigBagInterface::class);
        $this->firstConfigBag = $this->createMock(ConfigBag::class);
        $this->secondConfigBag = $this->createMock(ConfigBagInterface::class);
        $this->container = $this->createMock(ContainerInterface::class);
    }

    private function getRegistry(array $configBags): ConfigBagRegistry
    {
        return new ConfigBagRegistry(
            $configBags,
            $this->container,
            new RequestExpressionMatcher()
        );
    }

    public function testGetConfigBagForUnsupportedRequestType(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot find a config bag for the request "rest,another".');

        $requestType = new RequestType(['rest', 'another']);
        $registry = $this->getRegistry([]);
        $registry->getConfigBag($requestType);
    }

    public function testGetConfigBagShouldReturnDefaultBagForNotFirstAndSecondRequestType(): void
    {
        $registry = $this->getRegistry([
            ['default_config_bag', '!first&!second'],
            ['first_config_bag', 'first'],
            ['second_config_bag', 'second']
        ]);

        $this->container->expects(self::once())
            ->method('get')
            ->with('default_config_bag')
            ->willReturn($this->defaultConfigBag);

        $requestType = new RequestType(['rest']);
        self::assertSame($this->defaultConfigBag, $registry->getConfigBag($requestType));
        // test internal cache
        self::assertSame($this->defaultConfigBag, $registry->getConfigBag($requestType));
    }

    public function testGetConfigBagShouldReturnFirstBagForFirstRequestType(): void
    {
        $registry = $this->getRegistry([
            ['default_config_bag', '!first&!second'],
            ['first_config_bag', 'first'],
            ['second_config_bag', 'second']
        ]);

        $this->container->expects(self::once())
            ->method('get')
            ->with('first_config_bag')
            ->willReturn($this->firstConfigBag);

        $requestType = new RequestType(['rest', 'first']);
        self::assertSame($this->firstConfigBag, $registry->getConfigBag($requestType));
        // test internal cache
        self::assertSame($this->firstConfigBag, $registry->getConfigBag($requestType));
    }

    public function testGetConfigBagShouldReturnSecondBagForSecondRequestType(): void
    {
        $registry = $this->getRegistry([
            ['default_config_bag', '!first&!second'],
            ['first_config_bag', 'first'],
            ['second_config_bag', 'second']
        ]);

        $this->container->expects(self::once())
            ->method('get')
            ->with('second_config_bag')
            ->willReturn($this->secondConfigBag);

        $requestType = new RequestType(['rest', 'second']);
        self::assertSame($this->secondConfigBag, $registry->getConfigBag($requestType));
        // test internal cache
        self::assertSame($this->secondConfigBag, $registry->getConfigBag($requestType));
    }

    public function testGetConfigBagShouldReturnDefaultBagIfSpecificBagNotFound(): void
    {
        $registry = $this->getRegistry([
            ['first_config_bag', 'first'],
            ['default_config_bag', '']
        ]);

        $this->container->expects(self::once())
            ->method('get')
            ->with('default_config_bag')
            ->willReturn($this->defaultConfigBag);

        $requestType = new RequestType(['rest', 'another']);
        self::assertSame($this->defaultConfigBag, $registry->getConfigBag($requestType));
        // test internal cache
        self::assertSame($this->defaultConfigBag, $registry->getConfigBag($requestType));
    }

    public function testReset(): void
    {
        $registry = $this->getRegistry([
            ['default_config_bag', '!first&!second'],
            ['first_config_bag', 'first'],
            ['second_config_bag', 'second']
        ]);

        $this->container->expects(self::exactly(4))
            ->method('get')
            ->willReturnMap([
                ['first_config_bag', $this->firstConfigBag],
                ['second_config_bag', $this->secondConfigBag]
            ]);
        $this->firstConfigBag->expects(self::once())
            ->method('reset');

        $requestType1 = new RequestType(['rest', 'first']);
        $requestType2 = new RequestType(['rest', 'second']);
        self::assertSame($this->firstConfigBag, $registry->getConfigBag($requestType1));
        self::assertSame($this->secondConfigBag, $registry->getConfigBag($requestType2));
        // test internal cache
        self::assertSame($this->firstConfigBag, $registry->getConfigBag($requestType1));
        self::assertSame($this->secondConfigBag, $registry->getConfigBag($requestType2));

        $registry->reset();
        self::assertSame($this->firstConfigBag, $registry->getConfigBag($requestType1));
        self::assertSame($this->secondConfigBag, $registry->getConfigBag($requestType2));
    }
}
