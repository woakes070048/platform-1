<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Configuration;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Oro\Bundle\SecurityBundle\Configuration\PermissionConfigurationBuilder;
use Oro\Bundle\SecurityBundle\Entity\Permission;
use Oro\Bundle\SecurityBundle\Entity\PermissionEntity;
use Oro\Bundle\SecurityBundle\Tests\Unit\Stub\StubEntity;
use Oro\Bundle\SecurityBundle\Tests\Unit\Stub\StubInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidatorException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PermissionConfigurationBuilderTest extends TestCase
{
    private ValidatorInterface&MockObject $validator;
    private EntityManagerInterface&MockObject $entityManager;
    private EntityRepository&MockObject $entityRepository;
    private PermissionConfigurationBuilder $builder;

    #[\Override]
    protected function setUp(): void
    {
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->entityRepository = $this->createMock(EntityRepository::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->with(Permission::class)
            ->willReturn($this->entityManager);
        $doctrine->expects($this->any())
            ->method('getRepository')
            ->with(PermissionEntity::class)
            ->willReturn($this->entityRepository);

        $this->builder = new PermissionConfigurationBuilder($this->validator, $doctrine);
    }

    private function assertDefinitionConfiguration(array $expected, Permission $definition): void
    {
        $this->assertSame($expected['label'], $definition->getLabel());
        $this->assertSame($expected['apply_to_all'], $definition->isApplyToAll());
        $this->assertSame($expected['group_names'], $definition->getGroupNames());
        $this->assertEquals($expected['exclude_entities'], $definition->getExcludeEntities());
        $this->assertEquals($expected['apply_to_entities'], $definition->getApplyToEntities());
        $this->assertSame($expected['description'], $definition->getDescription());
    }

    /**
     * @dataProvider buildPermissionsDataProvider
     */
    public function testBuildPermissions(array $configuration, array $expected): void
    {
        $this->mockEntityManager(['Entity1', 'Entity2', StubEntity::class]);

        $this->validator->expects($this->any())
            ->method('validate')
            ->with($this->isInstanceOf(Permission::class))
            ->willReturn(new ConstraintViolationList());

        $permissions = $this->builder->buildPermissions($configuration);

        $this->assertSameSize($expected, $permissions);
        foreach ($permissions as $permission) {
            $this->assertInstanceOf(Permission::class, $permission);
            $this->assertArrayHasKey($permission->getName(), $expected);
            $this->assertDefinitionConfiguration($expected[$permission->getName()], $permission);
        }
    }

    public function testBuildPermissionsException(): void
    {
        $this->mockEntityManager([]);

        $this->validator->expects($this->once())
            ->method('validate')
            ->with($this->isInstanceOf(Permission::class))
            ->willReturn(new ConstraintViolationList([
                new ConstraintViolation('Test message', 'Test template', [], 'root', 'name', 'data')
            ]));

        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage(
            sprintf('Configuration of permission test_permission is invalid:%s    Test message', PHP_EOL)
        );

        $this->builder->buildPermissions(
            [
                'test_permission' => [
                    'name' => 'minimum_name',
                    'label' => 'My Label',
                ]
            ]
        );
    }

    public function buildPermissionsDataProvider(): array
    {
        $permissionEntity1 = (new PermissionEntity())->setName('Entity1');
        $permissionEntity2 = (new PermissionEntity())->setName('Entity2');
        $permissionEntityByInterface = (new PermissionEntity())->setName(StubEntity::class);

        return [
            [
                'configuration' => [
                    'minimum_name' => [
                        'name' => 'minimum_name',
                        'label' => 'My Label',
                    ],
                    'maximum_name' => [
                        'name' => 'maximum_name',
                        'label' => 'My Label',
                        'apply_to_all' => false,
                        'group_names' => ['frontend', 'default'],
                        'exclude_entities' => [$permissionEntity1->getName(), $permissionEntity1->getName()],
                        'apply_to_entities' => [$permissionEntity2->getName(), $permissionEntity2->getName()],
                        'apply_to_interfaces' => [StubInterface::class],
                        'description' => 'Test description',
                    ],
                ],
                'expected' => [
                    'minimum_name' => [
                        'name' => 'minimum_name',
                        'label' => 'My Label',
                        'apply_to_all' => true,
                        'group_names' => [],
                        'exclude_entities' => new ArrayCollection(),
                        'apply_to_entities' => new ArrayCollection(),
                        'description' => '',
                    ],
                    'maximum_name' => [
                        'name' => 'maximum_name',
                        'label' => 'My Label',
                        'apply_to_all' => false,
                        'group_names' => ['frontend', 'default'],
                        'exclude_entities' => new ArrayCollection([$permissionEntity1]),
                        'apply_to_entities' => new ArrayCollection([$permissionEntity2, $permissionEntityByInterface]),
                        'description' => 'Test description',
                    ],
                ]
            ]
        ];
    }

    /**
     * @param string[] $expectedClassNames
     */
    private function mockEntityManager(array $expectedClassNames): void
    {
        $mappingDriver = $this->createMock(MappingDriver::class);
        $mappingDriver->expects($this->once())
            ->method('getAllClassNames')
            ->willReturn($expectedClassNames);

        $configuration = $this->createMock(Configuration::class);
        $configuration->expects($this->once())
            ->method('getMetadataDriverImpl')
            ->willReturn($mappingDriver);

        $this->entityManager->expects($this->once())
            ->method('getConfiguration')
            ->willReturn($configuration);
    }
}
