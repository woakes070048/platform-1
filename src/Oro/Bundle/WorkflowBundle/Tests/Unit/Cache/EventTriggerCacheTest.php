<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Cache;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\WorkflowBundle\Cache\EventTriggerCache;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Entity\Repository\ProcessTriggerRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

class EventTriggerCacheTest extends TestCase
{
    private const TRIGGER_CLASS_NAME = 'stdClass';
    private const DATA = 'data';
    private const BUILT = 'built';

    private ManagerRegistry&MockObject $doctrine;
    private EventTriggerCache $cache;

    private array $testTriggerData = [
        'FirstEntity'  => [ProcessTrigger::EVENT_CREATE, ProcessTrigger::EVENT_UPDATE],
        'SecondEntity' => [ProcessTrigger::EVENT_DELETE],
    ];

    private CacheItemPoolInterface&MockObject $cacheProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->cache = new EventTriggerCache($this->doctrine);
        $this->cache->setTriggerClassName(self::TRIGGER_CLASS_NAME);
        $this->cacheProvider = $this->createMock(CacheItemPoolInterface::class);
    }

    public function testBuild(): void
    {
        $cacheItem1 = $this->createMock(CacheItemInterface::class);
        $cacheItem2 = $this->createMock(CacheItemInterface::class);
        $this->cacheProvider->expects(self::once())
            ->method('clear');
        $this->cacheProvider->expects(self::exactly(2))
            ->method('getItem')
            ->withConsecutive(
                [self::DATA],
                [self::BUILT]
            )->willReturnOnConsecutiveCalls($cacheItem1, $cacheItem2);
        $cacheItem1->expects(self::once())
            ->method('set')
            ->with($this->testTriggerData)
            ->willReturn($cacheItem1);
        $cacheItem2->expects(self::once())
            ->method('set')
            ->with(true)
            ->willReturn($cacheItem2);
        $this->cacheProvider->expects(self::exactly(2))
            ->method('save')
            ->withConsecutive([$cacheItem1], [$cacheItem2]);

        $this->prepareRegistryForBuild($this->testTriggerData);
        $this->cache->setProvider($this->cacheProvider);

        self::assertEquals($this->testTriggerData, $this->cache->build());
    }

    public function testBuildNoProvider(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Event trigger cache provider is not defined');

        $this->cache->build();
    }

    public function testHasTrigger(): void
    {
        $cacheItem1 = $this->createMock(CacheItemInterface::class);
        $cacheItem2 = $this->createMock(CacheItemInterface::class);
        $this->cacheProvider->expects(self::exactly(2))
            ->method('clear');
        $this->cacheProvider->expects(self::exactly(8))
            ->method('getItem')
            ->withConsecutive(
                [self::DATA],
                [self::BUILT],
                [self::DATA],
                [self::BUILT],
                [self::DATA],
                [self::BUILT],
                [self::DATA],
                [self::BUILT],
            )->willReturnOnConsecutiveCalls(
                $cacheItem1,
                $cacheItem2,
                $cacheItem1,
                $cacheItem2,
                $cacheItem1,
                $cacheItem2,
                $cacheItem1,
                $cacheItem2
            );
        $cacheItem2->expects(self::exactly(2))
            ->method('isHit')
            ->willReturnOnConsecutiveCalls(false, true);
        $cacheItem1->expects(self::exactly(2))
            ->method('set')
            ->with($this->testTriggerData)
            ->willReturn($cacheItem1);
        $cacheItem2->expects(self::exactly(2))
            ->method('set')
            ->with(true)
            ->willReturn($cacheItem2);

        $this->cacheProvider->expects(self::exactly(4))
            ->method('save')
            ->with($cacheItem1);
        $this->cacheProvider->expects(self::exactly(4))
            ->method('save')
            ->with($cacheItem2);

        $this->prepareRegistryForBuild($this->testTriggerData);
        $this->cache->setProvider($this->cacheProvider);

        self::assertTrue($this->cache->hasTrigger('FirstEntity', ProcessTrigger::EVENT_CREATE));
        self::assertFalse($this->cache->hasTrigger('UnknownEntity', ProcessTrigger::EVENT_DELETE));
    }

    public function testHasTriggerBuiltCached(): void
    {
        $cacheItem1 = $this->createMock(CacheItemInterface::class);
        $cacheItem2 = $this->createMock(CacheItemInterface::class);
        $this->cacheProvider->expects(self::never())
            ->method('clear');
        $this->cacheProvider->expects(self::exactly(2))
            ->method('getItem')
            ->withConsecutive(
                [self::DATA],
                [self::BUILT]
            )->willReturn($cacheItem1, $cacheItem2);
        $cacheItem1->expects(self::once())
            ->method('isHit')
            ->willReturn(true);
        $cacheItem2->expects(self::once())
            ->method('isHit')
            ->willReturn(true);
        $cacheItem1->expects(self::once())
            ->method('get')
            ->willReturn($this->testTriggerData);
        $cacheItem2->expects(self::once())
            ->method('get')
            ->willReturn(true);
        $this->cacheProvider->expects(self::never())
            ->method('save');

        $this->prepareRegistryForBuild($this->testTriggerData);
        $this->cache->setProvider($this->cacheProvider);

        self::assertTrue($this->cache->hasTrigger('FirstEntity', ProcessTrigger::EVENT_CREATE));
    }

    public function testHasTriggerNoProvider(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Event trigger cache provider is not defined');

        $this->cache->hasTrigger('UnknownEntity', ProcessTrigger::EVENT_DELETE);
    }

    public function testNoTriggerClassNameException(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Event trigger class name is not defined');

        $this->cache->setProvider($this->cacheProvider);
        $this->cache->setTriggerClassName(null);

        $this->cache->build();
    }

    public function testInvalidTriggerRepository(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid repository');

        $this->cache->setProvider($this->cacheProvider);
        $this->cache->setTriggerClassName(self::TRIGGER_CLASS_NAME);

        $repository = $this->createMock(ObjectRepository::class);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::any())
            ->method('getRepository')
            ->with(self::TRIGGER_CLASS_NAME)
            ->willReturn($repository);

        $this->doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->with(self::TRIGGER_CLASS_NAME)
            ->willReturn($entityManager);

        $this->cache->build();
    }

    private function prepareRegistryForBuild(array $data): void
    {
        // generate triggers
        $triggers = [];
        foreach ($data as $entityClass => $events) {
            $definition = new ProcessDefinition();
            $definition->setRelatedEntity($entityClass);

            foreach ($events as $event) {
                $trigger = new ProcessTrigger();
                $trigger->setDefinition($definition)
                    ->setEvent($event);

                $triggers[] = $trigger;
            }
        }

        // set mocks
        $repository = $this->createMock(ProcessTriggerRepository::class);
        $repository->expects(self::any())
            ->method('getAvailableEventTriggers')
            ->willReturn($triggers);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::any())
            ->method('getRepository')
            ->with(self::TRIGGER_CLASS_NAME)
            ->willReturn($repository);

        $this->doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->with(self::TRIGGER_CLASS_NAME)
            ->willReturn($entityManager);
    }
}
