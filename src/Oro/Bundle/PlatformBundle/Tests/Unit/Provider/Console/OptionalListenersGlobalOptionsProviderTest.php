<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\Provider\Console;

use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;
use Oro\Bundle\PlatformBundle\Provider\Console\OptionalListenersGlobalOptionsProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;

class OptionalListenersGlobalOptionsProviderTest extends TestCase
{
    private OptionalListenerManager&MockObject $listenersManager;
    private OptionalListenersGlobalOptionsProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->listenersManager = $this->createMock(OptionalListenerManager::class);
        $this->provider = new OptionalListenersGlobalOptionsProvider($this->listenersManager);
    }

    public function testAddGlobalOptions(): void
    {
        $inputDefinition = new InputDefinition();
        $application = $this->createMock(Application::class);
        $application->expects($this->any())
            ->method('getDefinition')
            ->willReturn($inputDefinition);
        $application->expects($this->once())
            ->method('getHelperSet')
            ->willReturn(new HelperSet());

        $commandDefinition = new InputDefinition();
        $command = new Command('test');
        $command->setApplication($application);
        $command->setDefinition($commandDefinition);

        $this->provider->addGlobalOptions($command);
        $this->assertEquals(
            [OptionalListenersGlobalOptionsProvider::DISABLE_OPTIONAL_LISTENERS],
            array_keys($command->getApplication()->getDefinition()->getOptions())
        );
        $this->assertEquals(
            [OptionalListenersGlobalOptionsProvider::DISABLE_OPTIONAL_LISTENERS],
            array_keys($command->getDefinition()->getOptions())
        );
    }

    public function testResolveGlobalOptionsWhenNoListeners(): void
    {
        $input = $this->createMock(InputInterface::class);
        $input->expects($this->once())
            ->method('getOption')
            ->with(OptionalListenersGlobalOptionsProvider::DISABLE_OPTIONAL_LISTENERS)
            ->willReturn([]);

        $this->listenersManager->expects($this->never())
            ->method('getListeners');
        $this->listenersManager->expects($this->never())
            ->method('disableListeners');
        $this->provider->resolveGlobalOptions($input);
    }

    public function testResolveGlobalOptionsWhenAllListeners(): void
    {
        $input = $this->createMock(InputInterface::class);
        $input->expects($this->once())
            ->method('getOption')
            ->with(OptionalListenersGlobalOptionsProvider::DISABLE_OPTIONAL_LISTENERS)
            ->willReturn(['all']);

        $listeners = ['some_listener_service'];
        $this->listenersManager->expects($this->once())
            ->method('getListeners')
            ->willReturn($listeners);
        $this->listenersManager->expects($this->once())
            ->method('disableListeners')
            ->with($listeners);
        $this->provider->resolveGlobalOptions($input);
    }
    public function testResolveGlobalOptions(): void
    {
        $listeners = ['some_listener_service'];
        $input = $this->createMock(InputInterface::class);
        $input->expects($this->once())
            ->method('getOption')
            ->with(OptionalListenersGlobalOptionsProvider::DISABLE_OPTIONAL_LISTENERS)
            ->willReturn($listeners);

        $this->listenersManager->expects($this->never())
            ->method('getListeners');
        $this->listenersManager->expects($this->once())
            ->method('disableListeners')
            ->with($listeners);
        $this->provider->resolveGlobalOptions($input);
    }
}
