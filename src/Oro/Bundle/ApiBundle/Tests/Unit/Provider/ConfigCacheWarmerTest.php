<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Config\Extension\ConfigExtensionRegistry;
use Oro\Bundle\ApiBundle\Config\Extension\FiltersConfigExtension;
use Oro\Bundle\ApiBundle\Config\Extension\SortersConfigExtension;
use Oro\Bundle\ApiBundle\Filter\FilterOperatorRegistry;
use Oro\Bundle\ApiBundle\Provider\ConfigCacheFactory;
use Oro\Bundle\ApiBundle\Provider\ConfigCacheFile;
use Oro\Bundle\ApiBundle\Provider\ConfigCacheWarmer;
use Oro\Bundle\ApiBundle\Tests\Unit\DependencyInjection\Fixtures;
use Oro\Component\Config\CumulativeResource;
use Oro\Component\Config\CumulativeResourceManager;
use Oro\Component\Config\Loader\CumulativeResourceLoaderCollection;
use Oro\Component\Config\Loader\FolderYamlCumulativeFileLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Oro\Component\Testing\TempDirExtension;
use PHPUnit\Framework\TestCase;

class ConfigCacheWarmerTest extends TestCase
{
    use TempDirExtension;

    #[\Override]
    protected function setUp(): void
    {
        $bundle1 = new Fixtures\BarBundle\BarBundle();
        $bundle2 = new Fixtures\BazBundle\BazBundle();
        $bundle3 = new Fixtures\FooBundle\FooBundle();
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([
                $bundle1->getName() => get_class($bundle1),
                $bundle2->getName() => get_class($bundle2),
                $bundle3->getName() => get_class($bundle3)
            ]);
    }

    #[\Override]
    protected function tearDown(): void
    {
        CumulativeResourceManager::getInstance()->clear();
    }

    private function getConfigExtensionRegistry(): ConfigExtensionRegistry
    {
        $configExtensionRegistry = new ConfigExtensionRegistry(3);
        $configExtensionRegistry->addExtension(new FiltersConfigExtension(new FilterOperatorRegistry([])));
        $configExtensionRegistry->addExtension(new SortersConfigExtension());

        return $configExtensionRegistry;
    }

    private function decodeContent(string $content): array
    {
        $result = null;
        $filename = $this->getTempFile('api_config_cache_warmer') . '.php';
        try {
            file_put_contents($filename, $content);
            $result = require $filename;
        } finally {
            unlink($filename);
        }

        return $result;
    }

    private function getDefaultConfig(): array
    {
        return [
            'entities'  => [
                'Test\Entity1'          => [],
                'Test\Entity2'          => [],
                'Test\Entity3'          => [],
                'Test\Entity4'          => [
                    'fields'  => [
                        'field1' => [],
                        'field2' => [
                            'exclude' => true
                        ],
                        'field3' => [
                            'exclude'  => true,
                            'order_by' => ['name' => 'ASC']
                        ],
                        'field4' => [
                            'fields' => [
                                'field41' => [
                                    'order_by' => ['name' => 'DESC']
                                ]
                            ]
                        ],
                        'field5' => [
                            'fields' => [
                                'field51' => [
                                    'fields' => [
                                        'field511' => [
                                            'hints' => [['name' => 'HINT_TRANSLATABLE']]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'filters' => [
                        'fields' => [
                            'field1' => [],
                            'field2' => [
                                'data_type' => 'string',
                                'exclude'   => true
                            ],
                            'field3' => [
                                'exclude' => true
                            ]
                        ]
                    ],
                    'sorters' => [
                        'fields' => [
                            'field1' => [],
                            'field2' => [
                                'exclude' => true
                            ]
                        ]
                    ]
                ],
                'Test\Entity5'          => [
                    'fields' => [
                        'field1' => []
                    ]
                ],
                'Test\Entity6'          => [
                    'fields' => [
                        'field1' => []
                    ]
                ],
                'Test\Entity7'          => [
                    'documentation_resource' => ['entity7_bar.md', 'entity7_foo.md']
                ],
                'Test\Entity10'         => [],
                'Test\Entity11'         => [],
                'Test\Entity12'         => [
                    'fields' => [
                        'field1' => [
                            'exclude' => false
                        ]
                    ]
                ],
                'Test\Entity30'         => [],
                'Test\Entity31'         => [],
                'Test\Entity3Undefined' => []
            ]
        ];
    }

    private function getFirstConfig(): array
    {
        return [
            'entities'  => [
                'Test\Entity1'  => [],
                'Test\Entity2'  => [
                    'fields' => [
                        'field1' => [],
                        'field2' => [
                            'exclude' => false
                        ]
                    ]
                ],
                'Test\Entity3'  => [],
                'Test\Entity4'  => [],
                'Test\Entity5'  => [],
                'Test\Entity6'  => [],
                'Test\Entity10' => [],
                'Test\Entity11' => []
            ]
        ];
    }

    private function getSecondConfig(): array
    {
        return [
            'entities'  => [
                'Test\Entity1'  => [],
                'Test\Entity2'  => [
                    'fields' => [
                        'field2' => [
                            'exclude' => true
                        ]
                    ]
                ],
                'Test\Entity3'  => [],
                'Test\Entity4'  => [],
                'Test\Entity5'  => [],
                'Test\Entity6'  => [],
                'Test\Entity12' => [],
                'Test\Entity13' => []
            ]
        ];
    }

    private function getDefaultAliases(): array
    {
        return [
            'Test\Entity4'          => [
                'alias'        => 'entity4',
                'plural_alias' => 'entity4_plural'
            ],
            'Test\Entity5'          => [
                'alias'        => 'entity5',
                'plural_alias' => 'entity5_plural'
            ],
            'Test\Entity30'         => [
                'alias'        => 'entity30',
                'plural_alias' => 'entity30_plural'
            ],
            'Test\Entity31'         => [
                'alias'        => 'entity31',
                'plural_alias' => 'entity31_plural'
            ],
            'Test\Entity3'          => [],
            'Test\Entity3Undefined' => []
        ];
    }

    private function getFirstAliases(): array
    {
        return [
            'Test\Entity2' => [
                'alias'        => 'entity2',
                'plural_alias' => 'entity2_plural'
            ]
        ];
    }

    private function getDefaultSubstitutions(): array
    {
        return [
            'Test\Entity3'          => 'Test\Entity30',
            'Test\Entity3Undefined' => 'Test\Entity31'
        ];
    }

    private function getDefaultExcludedEntities(): array
    {
        return [
            'Test\Entity1',
            'Test\Entity2',
            'Test\Entity3'
        ];
    }

    private function getFirstExcludedEntities(): array
    {
        return [
            'Test\Entity1',
            'Test\Entity4',
            'Test\Entity6',
            'Test\Entity11'
        ];
    }

    private function getSecondExcludedEntities(): array
    {
        return [
            'Test\Entity4',
            'Test\Entity5',
            'Test\Entity13'
        ];
    }

    private function getDefaultExclusions(): array
    {
        return [
            ['entity' => 'Test\Entity1'],
            ['entity' => 'Test\Entity2'],
            ['entity' => 'Test\Entity3']
        ];
    }

    private function getFirstExclusions(): array
    {
        return [
            ['entity' => 'Test\Entity1'],
            ['entity' => 'Test\Entity4'],
            ['entity' => 'Test\Entity6'],
            ['entity' => 'Test\Entity11']
        ];
    }

    private function getSecondExclusions(): array
    {
        return [
            ['entity' => 'Test\Entity4'],
            ['entity' => 'Test\Entity5'],
            ['entity' => 'Test\Entity13']
        ];
    }

    private function getDefaultInclusions(): array
    {
        return [
            ['entity' => 'Test\Entity12'],
            ['entity' => 'Test\Entity12', 'field' => 'field1']
        ];
    }

    private function getFirstInclusions(): array
    {
        return [
            ['entity' => 'Test\Entity2', 'field' => 'field2'],
            ['entity' => 'Test\Entity3'],
            ['entity' => 'Test\Entity5'],
            ['entity' => 'Test\Entity10']
        ];
    }

    private function getSecondInclusions(): array
    {
        return [
            ['entity' => 'Test\Entity3'],
            ['entity' => 'Test\Entity6'],
            ['entity' => 'Test\Entity12']
        ];
    }

    public function testWarmUpWithDefaultConfigFile(): void
    {
        $configFiles = [
            'default' => ['api.yml']
        ];
        $expectedResult = [
            'config'            => ['api.yml' => $this->getDefaultConfig()],
            'aliases'           => $this->getDefaultAliases(),
            'substitutions'     => $this->getDefaultSubstitutions(),
            'excluded_entities' => $this->getDefaultExcludedEntities(),
            'exclusions'        => $this->getDefaultExclusions(),
            'inclusions'        => $this->getDefaultInclusions()
        ];

        $result = null;
        $cache = $this->createMock(ConfigCacheFile::class);
        $cache->expects(self::once())
            ->method('write')
            ->with(self::isType('string'), self::isNull())
            ->willReturnCallback(function ($content, $resources) use (&$result) {
                $result = $this->decodeContent($content);
            });
        $configCacheFactory = $this->createMock(ConfigCacheFactory::class);
        $configCacheFactory->expects(self::once())
            ->method('getCache')
            ->with('default')
            ->willReturn($cache);

        $configCacheWarmer = new ConfigCacheWarmer(
            $configFiles,
            $this->getConfigExtensionRegistry(),
            $configCacheFactory,
            false,
            'prod'
        );
        $configCacheWarmer->warmUp();

        self::assertEquals($expectedResult, $result);
    }

    public function testWarmUpWithSeveralTypesOfConfigFilesInAdditionalToDefaultConfigFile(): void
    {
        $configFiles = [
            'default' => ['api.yml'],
            'first'   => ['api_first.yml'],
            'second'  => ['api_second.yml']
        ];
        $expectedResultDefault = [
            'config'            => ['api.yml' => $this->getDefaultConfig()],
            'aliases'           => $this->getDefaultAliases(),
            'substitutions'     => $this->getDefaultSubstitutions(),
            'excluded_entities' => $this->getDefaultExcludedEntities(),
            'exclusions'        => $this->getDefaultExclusions(),
            'inclusions'        => $this->getDefaultInclusions()
        ];
        $expectedResultFirst = [
            'config'            => ['api_first.yml' => $this->getFirstConfig()],
            'aliases'           => $this->getFirstAliases(),
            'substitutions'     => [],
            'excluded_entities' => $this->getFirstExcludedEntities(),
            'exclusions'        => $this->getFirstExclusions(),
            'inclusions'        => $this->getFirstInclusions()
        ];
        $expectedResultSecond = [
            'config'            => ['api_second.yml' => $this->getSecondConfig()],
            'aliases'           => [],
            'substitutions'     => [],
            'excluded_entities' => $this->getSecondExcludedEntities(),
            'exclusions'        => $this->getSecondExclusions(),
            'inclusions'        => $this->getSecondInclusions()
        ];

        $resultDefault = null;
        $resultFirst = null;
        $resultSecond = null;
        $cacheDefault = $this->createMock(ConfigCacheFile::class);
        $cacheDefault->expects(self::once())
            ->method('write')
            ->with(self::isType('string'), self::isNull())
            ->willReturnCallback(function ($content, $resources) use (&$resultDefault) {
                $resultDefault = $this->decodeContent($content);
            });
        $cacheFirst = $this->createMock(ConfigCacheFile::class);
        $cacheFirst->expects(self::once())
            ->method('write')
            ->with(self::isType('string'), self::isNull())
            ->willReturnCallback(function ($content, $resources) use (&$resultFirst) {
                $resultFirst = $this->decodeContent($content);
            });
        $cacheSecond = $this->createMock(ConfigCacheFile::class);
        $cacheSecond->expects(self::once())
            ->method('write')
            ->with(self::isType('string'), self::isNull())
            ->willReturnCallback(function ($content, $resources) use (&$resultSecond) {
                $resultSecond = $this->decodeContent($content);
            });
        $configCacheFactory = $this->createMock(ConfigCacheFactory::class);
        $configCacheFactory->expects(self::exactly(3))
            ->method('getCache')
            ->willReturnMap([
                ['default', $cacheDefault],
                ['first', $cacheFirst],
                ['second', $cacheSecond]
            ]);

        $configCacheWarmer = new ConfigCacheWarmer(
            $configFiles,
            $this->getConfigExtensionRegistry(),
            $configCacheFactory,
            false,
            'prod'
        );
        $configCacheWarmer->warmUp();

        self::assertEquals($expectedResultDefault, $resultDefault, 'default');
        self::assertEquals($expectedResultFirst, $resultFirst, 'first');
        self::assertEquals($expectedResultSecond, $resultSecond, 'second');
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testWarmUpForConfigWithSeveralConfigFiles(): void
    {
        $configFiles = [
            'default' => ['api.yml'],
            'first'   => ['api_first.yml'],
            'second'  => ['api_second.yml', 'api_first.yml']
        ];
        $expectedResultDefault = [
            'config'            => ['api.yml' => $this->getDefaultConfig()],
            'aliases'           => $this->getDefaultAliases(),
            'substitutions'     => $this->getDefaultSubstitutions(),
            'excluded_entities' => $this->getDefaultExcludedEntities(),
            'exclusions'        => $this->getDefaultExclusions(),
            'inclusions'        => $this->getDefaultInclusions()
        ];
        $expectedResultFirst = [
            'config'            => ['api_first.yml' => $this->getFirstConfig()],
            'aliases'           => $this->getFirstAliases(),
            'substitutions'     => [],
            'excluded_entities' => $this->getFirstExcludedEntities(),
            'exclusions'        => $this->getFirstExclusions(),
            'inclusions'        => $this->getFirstInclusions()
        ];
        $expectedResultSecond = [
            'config'            => [
                'api_second.yml' => $this->getSecondConfig(),
                'api_first.yml'  => $this->getFirstConfig()
            ],
            'aliases'           => $this->getFirstAliases(),
            'substitutions'     => [],
            'excluded_entities' => [
                'Test\Entity4',
                'Test\Entity5',
                'Test\Entity13',
                'Test\Entity1',
                'Test\Entity11'
            ],
            'exclusions'        => [
                ['entity' => 'Test\Entity4'],
                ['entity' => 'Test\Entity5'],
                ['entity' => 'Test\Entity13'],
                ['entity' => 'Test\Entity1'],
                ['entity' => 'Test\Entity11']
            ],
            'inclusions'        => [
                ['entity' => 'Test\Entity3'],
                ['entity' => 'Test\Entity6'],
                ['entity' => 'Test\Entity12'],
                ['entity' => 'Test\Entity2', 'field' => 'field2'],
                ['entity' => 'Test\Entity10']
            ]
        ];

        $resultDefault = null;
        $resultFirst = null;
        $resultSecond = null;
        $cacheDefault = $this->createMock(ConfigCacheFile::class);
        $cacheDefault->expects(self::once())
            ->method('write')
            ->with(self::isType('string'), self::isNull())
            ->willReturnCallback(function ($content, $resources) use (&$resultDefault) {
                $resultDefault = $this->decodeContent($content);
            });
        $cacheFirst = $this->createMock(ConfigCacheFile::class);
        $cacheFirst->expects(self::once())
            ->method('write')
            ->with(self::isType('string'), self::isNull())
            ->willReturnCallback(function ($content, $resources) use (&$resultFirst) {
                $resultFirst = $this->decodeContent($content);
            });
        $cacheSecond = $this->createMock(ConfigCacheFile::class);
        $cacheSecond->expects(self::once())
            ->method('write')
            ->with(self::isType('string'), self::isNull())
            ->willReturnCallback(function ($content, $resources) use (&$resultSecond) {
                $resultSecond = $this->decodeContent($content);
            });
        $configCacheFactory = $this->createMock(ConfigCacheFactory::class);
        $configCacheFactory->expects(self::exactly(3))
            ->method('getCache')
            ->willReturnMap([
                ['default', $cacheDefault],
                ['first', $cacheFirst],
                ['second', $cacheSecond]
            ]);

        $configCacheWarmer = new ConfigCacheWarmer(
            $configFiles,
            $this->getConfigExtensionRegistry(),
            $configCacheFactory,
            false,
            'prod'
        );
        $configCacheWarmer->warmUp();

        self::assertEquals($expectedResultDefault, $resultDefault, 'default');
        self::assertEquals($expectedResultFirst, $resultFirst, 'first');
        self::assertEquals($expectedResultSecond, $resultSecond, 'second');
    }

    public function testWarmUpByConfigKey(): void
    {
        $configFiles = [
            'default' => ['api.yml'],
            'first'   => ['api_first.yml'],
            'second'  => ['api_second.yml']
        ];
        $expectedResultFirst = [
            'config'            => ['api_first.yml' => $this->getFirstConfig()],
            'aliases'           => $this->getFirstAliases(),
            'substitutions'     => [],
            'excluded_entities' => $this->getFirstExcludedEntities(),
            'exclusions'        => $this->getFirstExclusions(),
            'inclusions'        => $this->getFirstInclusions()
        ];

        $resultFirst = null;
        $cacheFirst = $this->createMock(ConfigCacheFile::class);
        $cacheFirst->expects(self::once())
            ->method('write')
            ->with(self::isType('string'), self::isNull())
            ->willReturnCallback(function ($content, $resources) use (&$resultFirst) {
                $resultFirst = $this->decodeContent($content);
            });
        $configCacheFactory = $this->createMock(ConfigCacheFactory::class);
        $configCacheFactory->expects(self::once())
            ->method('getCache')
            ->with('first')
            ->willReturn($cacheFirst);

        $configCacheWarmer = new ConfigCacheWarmer(
            $configFiles,
            $this->getConfigExtensionRegistry(),
            $configCacheFactory,
            false,
            'prod'
        );
        $configCacheWarmer->warmUp('first');

        self::assertEquals($expectedResultFirst, $resultFirst, 'first');
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testWarmUpShouldSaveResourcesWhenDebugIsEnabled(): void
    {
        $configFiles = [
            'default' => ['api.yml'],
            'first'   => ['api_first.yml'],
            'second'  => ['api_second.yml', 'api_first.yml']
        ];

        $bundle1 = new Fixtures\BarBundle\BarBundle();
        $bundle2 = new Fixtures\BazBundle\BazBundle();
        $bundle3 = new Fixtures\FooBundle\FooBundle();

        $pathDefault = '/Resources/config/oro/api.yml';
        $pathFirst = '/Resources/config/oro/api_first.yml';
        $pathSecond = '/Resources/config/oro/api_second.yml';
        $expectedResourcesDefault = new CumulativeResource(
            'oro_api',
            new CumulativeResourceLoaderCollection(
                [
                    new YamlCumulativeFileLoader($pathDefault),
                    new FolderYamlCumulativeFileLoader('../config/oro/api')
                ]
            )
        );
        $expectedResourcesDefault->addFound(
            get_class($bundle1),
            str_replace('/', DIRECTORY_SEPARATOR, $bundle1->getPath() . $pathDefault)
        );
        $expectedResourcesDefault->addFound(
            get_class($bundle2),
            str_replace('/', DIRECTORY_SEPARATOR, $bundle2->getPath() . $pathDefault)
        );
        $expectedResourcesDefault->addFound(
            get_class($bundle3),
            str_replace('/', DIRECTORY_SEPARATOR, $bundle3->getPath() . $pathDefault)
        );

        $expectedResourcesFirst = new CumulativeResource(
            'oro_api',
            new CumulativeResourceLoaderCollection(
                [
                    new YamlCumulativeFileLoader($pathFirst),
                    new FolderYamlCumulativeFileLoader('../config/oro/api_first')
                ]
            )
        );
        $expectedResourcesFirst->addFound(
            get_class($bundle1),
            str_replace('/', DIRECTORY_SEPARATOR, $bundle1->getPath() . $pathFirst)
        );

        $expectedResourcesSecond = new CumulativeResource(
            'oro_api',
            new CumulativeResourceLoaderCollection(
                [
                    new YamlCumulativeFileLoader($pathSecond),
                    new FolderYamlCumulativeFileLoader('../config/oro/api_second')
                ]
            )
        );
        $expectedResourcesSecond->addFound(
            get_class($bundle1),
            str_replace('/', DIRECTORY_SEPARATOR, $bundle1->getPath() . $pathSecond)
        );

        $resourcesDefault = null;
        $resourcesFirst = null;
        $resourcesSecond = null;
        $cacheDefault = $this->createMock(ConfigCacheFile::class);
        $cacheDefault->expects(self::once())
            ->method('write')
            ->with(self::isType('string'), self::isType('array'))
            ->willReturnCallback(function ($content, $resources) use (&$resourcesDefault) {
                $resourcesDefault = $resources;
            });
        $cacheFirst = $this->createMock(ConfigCacheFile::class);
        $cacheFirst->expects(self::once())
            ->method('write')
            ->with(self::isType('string'), self::isType('array'))
            ->willReturnCallback(function ($content, $resources) use (&$resourcesFirst) {
                $resourcesFirst = $resources;
            });
        $cacheSecond = $this->createMock(ConfigCacheFile::class);
        $cacheSecond->expects(self::once())
            ->method('write')
            ->with(self::isType('string'), self::isType('array'))
            ->willReturnCallback(function ($content, $resources) use (&$resourcesSecond) {
                $resourcesSecond = $resources;
            });
        $configCacheFactory = $this->createMock(ConfigCacheFactory::class);
        $configCacheFactory->expects(self::exactly(3))
            ->method('getCache')
            ->willReturnMap([
                ['default', $cacheDefault],
                ['first', $cacheFirst],
                ['second', $cacheSecond]
            ]);

        $configCacheWarmer = new ConfigCacheWarmer(
            $configFiles,
            $this->getConfigExtensionRegistry(),
            $configCacheFactory,
            true,
            'dev'
        );
        $configCacheWarmer->warmUp();

        self::assertEquals([$expectedResourcesDefault], $resourcesDefault, 'default');
        self::assertEquals([$expectedResourcesFirst], $resourcesFirst, 'first');
        self::assertEquals([$expectedResourcesSecond, $expectedResourcesFirst], $resourcesSecond, 'second');
    }
}
