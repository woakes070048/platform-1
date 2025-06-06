<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetConfig;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\FiltersConfig;
use Oro\Bundle\ApiBundle\Processor\GetConfig\NormalizeFilters;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class NormalizeFiltersTest extends ConfigProcessorTestCase
{
    private const string IDENTIFIER_FILTER_NAME = 'id';

    private DoctrineHelper&MockObject $doctrineHelper;
    private NormalizeFilters $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->processor = new NormalizeFilters($this->doctrineHelper);
    }

    public function testRemoveExcludedFilters(): void
    {
        $filters = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1' => [
                    'data_type' => 'integer'
                ],
                'field2' => [
                    'data_type' => 'integer',
                    'exclude'   => true
                ]
            ]
        ];

        $this->context->setResult($this->createConfigObject([]));
        $this->context->setFilters($this->createConfigObject($filters, ConfigUtil::FILTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'field1' => [
                        'data_type' => 'integer'
                    ]
                ]
            ],
            $this->context->getFilters()
        );
    }

    /**
     * @dataProvider processNotManageableEntityProvider
     */
    public function testProcessForNotManageableEntity(array $definition, array $filters, array $expectedFilters): void
    {
        $this->doctrineHelper->expects(self::any())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);

        $this->context->setResult($this->createConfigObject($definition));
        $this->context->setFilters($this->createConfigObject($filters, ConfigUtil::FILTERS));
        $this->processor->process($this->context);
        self::assertEquals($expectedFilters, $this->context->getFilters()->toArray());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function processNotManageableEntityProvider(): array
    {
        return [
            'empty'                                                          => [
                'definition'      => [],
                'filters'         => [],
                'expectedFilters' => []
            ],
            'no child filters'                                               => [
                'definition'      => [
                    'fields' => []
                ],
                'filters'         => [
                    'fields' => [
                        'field1' => [
                            'data_type' => 'string'
                        ]
                    ]
                ],
                'expectedFilters' => [
                    'fields' => [
                        'field1' => [
                            'data_type' => 'string'
                        ]
                    ]
                ]
            ],
            'child filters'                                                  => [
                'definition'      => [
                    'fields' => [
                        'field2' => [
                            'filters' => [
                                'fields' => [
                                    'field21' => [
                                        'data_type' => 'string'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'filters'         => [
                    'fields' => [
                        'field1' => [
                            'data_type' => 'string'
                        ]
                    ]
                ],
                'expectedFilters' => [
                    'fields' => [
                        'field1'         => [
                            'data_type' => 'string'
                        ],
                        'field2.field21' => [
                            'data_type' => 'string'
                        ]
                    ]
                ]
            ],
            'child filters, fields property paths'                           => [
                'definition'      => [
                    'fields' => [
                        'field1' => [
                            'property_path' => 'realField1'
                        ],
                        'field2' => [
                            'property_path' => 'realField2',
                            'fields'        => [
                                'field21' => [
                                    'property_path' => 'realField21'
                                ]
                            ],
                            'filters'       => [
                                'fields' => [
                                    'field21' => [
                                        'data_type' => 'string'
                                    ]
                                ]
                            ]
                        ],
                        'field3' => [
                            'property_path' => 'realField3',
                            'fields'        => [
                                'field31' => [
                                    'property_path' => 'realField31'
                                ],
                                'field32' => [
                                    'property_path' => 'realField32',
                                    'fields'        => [
                                        'field321' => [
                                            'property_path' => 'realField321'
                                        ]
                                    ],
                                    'filters'       => [
                                        'fields' => [
                                            'field321' => [
                                                'data_type' => 'string'
                                            ]
                                        ]
                                    ]
                                ]
                            ],
                            'filters'       => [
                                'fields' => [
                                    'field32' => [
                                        'data_type' => 'string'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'filters'         => [
                    'fields' => [
                        'field1' => [
                            'data_type' => 'string'
                        ]
                    ]
                ],
                'expectedFilters' => [
                    'fields' => [
                        'field1'                  => [
                            'data_type'     => 'string',
                            'property_path' => 'realField1'
                        ],
                        'field2.field21'          => [
                            'data_type'     => 'string',
                            'property_path' => 'realField2.realField21'
                        ],
                        'field3.field32'          => [
                            'data_type'     => 'string',
                            'property_path' => 'realField3.realField32'
                        ],
                        'field3.field32.field321' => [
                            'data_type'     => 'string',
                            'property_path' => 'realField3.realField32.realField321'
                        ]
                    ]
                ]
            ],
            'child filters, filters property paths should not be overridden' => [
                'definition'      => [
                    'fields' => [
                        'field1' => [
                            'property_path' => 'realField1'
                        ],
                        'field2' => [
                            'property_path' => 'realField2',
                            'fields'        => [
                                'field21' => [
                                    'property_path' => 'realField21'
                                ]
                            ],
                            'filters'       => [
                                'fields' => [
                                    'field21' => [
                                        'data_type'     => 'string',
                                        'property_path' => 'filterRealField21'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'filters'         => [
                    'fields' => [
                        'field1' => [
                            'data_type'     => 'string',
                            'property_path' => 'filterRealField1'
                        ]
                    ]
                ],
                'expectedFilters' => [
                    'fields' => [
                        'field1'         => [
                            'data_type'     => 'string',
                            'property_path' => 'filterRealField1'
                        ],
                        'field2.field21' => [
                            'data_type'     => 'string',
                            'property_path' => 'realField2.filterRealField21'
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider processManageableEntityProvider
     */
    public function testProcessForManageableEntity(array $definition, array $filters, array $expectedFilters): void
    {
        $rootMetadata = $this->getClassMetadataMock();
        $toOne1Metadata = $this->getClassMetadataMock();
        $toOne1Metadata->expects(self::any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['toOne1_id']);
        $toOne1toOne11Metadata = $this->getClassMetadataMock();
        $toOne1toOne11Metadata->expects(self::any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['toOne11_id']);
        $toOne1toMany11Metadata = $this->getClassMetadataMock();
        $toOne1toMany11Metadata->expects(self::any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['toMany11_id']);
        $toMany1Metadata = $this->getClassMetadataMock();
        $toMany1Metadata->expects(self::any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['toMany1_id']);

        $rootMetadata->expects(self::any())
            ->method('hasAssociation')
            ->willReturnMap([
                ['toOne1', true],
                ['toMany1', true]
            ]);
        $rootMetadata->expects(self::any())
            ->method('isCollectionValuedAssociation')
            ->willReturnMap([
                ['toOne1', false],
                ['toMany1', true]
            ]);

        $toOne1Metadata->expects(self::any())
            ->method('hasAssociation')
            ->willReturnMap([
                ['toOne1_toOne11', true],
                ['toOne1_toMany11', true]
            ]);
        $toOne1Metadata->expects(self::any())
            ->method('isCollectionValuedAssociation')
            ->willReturnMap([
                ['toOne1_toOne11', false],
                ['toOne1_toMany11', true]
            ]);

        $this->doctrineHelper->expects(self::any())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::any())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootMetadata);
        $this->doctrineHelper->expects(self::any())
            ->method('findEntityMetadataByPath')
            ->willReturnMap([
                [self::TEST_CLASS_NAME, ['toOne1'], $toOne1Metadata],
                [self::TEST_CLASS_NAME, ['toOne1', 'toOne1_toOne11'], $toOne1toOne11Metadata],
                [self::TEST_CLASS_NAME, ['toOne1', 'toOne1_toMany11'], $toOne1toMany11Metadata],
                [self::TEST_CLASS_NAME, ['toMany1'], $toMany1Metadata]
            ]);

        $this->context->setResult($this->createConfigObject($definition));
        $this->context->setFilters($this->createConfigObject($filters, ConfigUtil::FILTERS));
        $this->processor->process($this->context);
        self::assertEquals($expectedFilters, $this->context->getFilters()->toArray());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function processManageableEntityProvider(): array
    {
        return [
            'child filters' => [
                'definition'      => [
                    'fields' => [
                        'toOne1'  => [
                            'fields'  => [
                                'toOne1_toOne11'  => [
                                    'filters' => [
                                        'fields' => [
                                            'toOne11_id'              => [
                                                'data_type' => 'integer'
                                            ],
                                            'toOne1_toOne11_field111' => [
                                                'data_type' => 'string'
                                            ]
                                        ]
                                    ]
                                ],
                                'toOne1_toMany11' => [
                                    'filters' => [
                                        'fields' => [
                                            'toMany11_id'              => [
                                                'data_type' => 'integer'
                                            ],
                                            'toOne1_toMany11_field111' => [
                                                'data_type' => 'string'
                                            ]
                                        ]
                                    ]
                                ]
                            ],
                            'filters' => [
                                'fields' => [
                                    'toOne1_id'       => [
                                        'data_type' => 'integer'
                                    ],
                                    'toOne1_field1'   => [
                                        'data_type' => 'string'
                                    ],
                                    'toOne1_toOne11'  => [
                                        'data_type' => 'string'
                                    ],
                                    'toOne1_toMany11' => [
                                        'data_type' => 'string'
                                    ]
                                ]
                            ]
                        ],
                        'toMany1' => [
                            'filters' => [
                                'fields' => [
                                    'toMany1_id'     => [
                                        'data_type' => 'integer'
                                    ],
                                    'toMany1_field1' => [
                                        'data_type' => 'string'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'filters'         => [
                    'fields' => [
                        'field1' => [
                            'data_type' => 'string'
                        ]
                    ]
                ],
                'expectedFilters' => [
                    'fields' => [
                        'field1'                                        => [
                            'data_type' => 'string'
                        ],
                        'toOne1.toOne1_field1'                          => [
                            'data_type' => 'string'
                        ],
                        'toOne1.toOne1_toOne11'                         => [
                            'data_type' => 'string'
                        ],
                        'toOne1.toOne1_toOne11.toOne1_toOne11_field111' => [
                            'data_type' => 'string'
                        ]
                    ]
                ]
            ]
        ];
    }

    public function testFiltersByRenamedField(): void
    {
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['renamedIdField'],
            'fields'                 => [
                'field1'         => [
                    'property_path' => 'realField1'
                ],
                'renamedIdField' => [
                    'property_path' => 'field2'
                ],
                'renamedField'   => [
                    'property_path' => 'field3'
                ],
                'field10'        => [
                    'property_path' => 'realField10'
                ],
                'anotherField11' => [
                    'property_path' => 'field11'
                ]
            ]
        ];
        $filters = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1'  => [
                    'data_type' => 'string'
                ],
                'field2'  => [
                    'data_type' => 'string'
                ],
                'field3'  => [
                    'data_type' => 'string'
                ],
                'field10' => [
                    'data_type'     => 'string',
                    'property_path' => 'filterField10'
                ],
                'field11' => [
                    'data_type'     => 'string',
                    'property_path' => 'filterField11'
                ]
            ]
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setFilters($this->createConfigObject($filters, ConfigUtil::FILTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'field1'         => [
                        'data_type'     => 'string',
                        'property_path' => 'realField1'
                    ],
                    'renamedIdField' => [
                        'data_type'     => 'string',
                        'property_path' => 'field2'
                    ],
                    'renamedField'   => [
                        'data_type'     => 'string',
                        'property_path' => 'field3'
                    ],
                    'field10'        => [
                        'data_type'     => 'string',
                        'property_path' => 'filterField10'
                    ],
                    'field11'        => [
                        'data_type'     => 'string',
                        'property_path' => 'filterField11'
                    ]
                ]
            ],
            $this->context->getFilters()
        );
    }

    public function testCompleteCompositeIdFiltersForEntityWithSingleIdentifier(): void
    {
        $definition = new EntityDefinitionConfig();
        $definition->setIdentifierFieldNames(['id']);

        $this->context->setResult($definition);
        $this->context->setFilters(new FiltersConfig());
        $this->processor->process($this->context);
    }

    public function testCompleteCompositeIdFiltersWhenIdFilterAlreadyExists(): void
    {
        $definition = new EntityDefinitionConfig();
        $definition->setIdentifierFieldNames(['id1', 'id2']);
        $filters = new FiltersConfig();
        $filter = $filters->addField(self::IDENTIFIER_FILTER_NAME);
        $filter->setType('custom_filter');

        $this->context->setResult($definition);
        $this->context->setFilters($filters);
        $this->processor->process($this->context);

        self::assertEquals('custom_filter', $filters->getField(self::IDENTIFIER_FILTER_NAME)->getType());
    }

    public function testCompleteCompositeIdFiltersWhenIdFilterDoesNotExist(): void
    {
        $definition = new EntityDefinitionConfig();
        $definition->setIdentifierFieldNames(['id1', 'id2']);
        $filters = new FiltersConfig();

        $this->context->setResult($definition);
        $this->context->setFilters($filters);
        $this->processor->process($this->context);

        $filter = $filters->getField(self::IDENTIFIER_FILTER_NAME);
        self::assertNotNull($filter);
        self::assertEquals('composite_identifier', $filter->getType());
        self::assertEquals(DataType::STRING, $filter->getDataType());
        self::assertTrue($filter->isArrayAllowed());
    }

    public function testCompleteCompositeIdFiltersForAssociationWithSingleIdentifier(): void
    {
        $definition = new EntityDefinitionConfig();
        $definition->addField('field');
        $association = $definition->addField('association');
        $association->setTargetClass('Test\TargetEntity');
        $associationTargetEntity = $association->createAndSetTargetEntity();
        $associationTargetEntity->setIdentifierFieldNames(['id']);
        $filters = new FiltersConfig();

        $this->context->setResult($definition);
        $this->context->setFilters($filters);
        $this->processor->process($this->context);

        self::assertFalse($this->context->getFilters()->hasField('field'));
        self::assertFalse($this->context->getFilters()->hasField('association'));
    }

    public function testCompleteCompositeIdFiltersForAssociationWithCompositeIdentifier(): void
    {
        $definition = new EntityDefinitionConfig();
        $definition->addField('field');
        $association = $definition->addField('association');
        $association->setTargetClass('Test\TargetEntity');
        $associationTargetEntity = $association->createAndSetTargetEntity();
        $associationTargetEntity->setIdentifierFieldNames(['id1', 'id2']);
        $filters = new FiltersConfig();

        $this->context->setResult($definition);
        $this->context->setFilters($filters);
        $this->processor->process($this->context);

        self::assertFalse($this->context->getFilters()->hasField('field'));
        $filter = $this->context->getFilters()->getField('association');
        self::assertNotNull($filter);
        self::assertEquals('association_composite_identifier', $filter->getType());
        self::assertEquals(DataType::STRING, $filter->getDataType());
        self::assertTrue($filter->isArrayAllowed());
    }

    public function testCompleteCompositeIdFiltersForExcludedAssociationWithCompositeIdentifier(): void
    {
        $definition = new EntityDefinitionConfig();
        $definition->addField('field');
        $association = $definition->addField('association');
        $association->setExcluded();
        $association->setTargetClass('Test\TargetEntity');
        $associationTargetEntity = $association->createAndSetTargetEntity();
        $associationTargetEntity->setIdentifierFieldNames(['id1', 'id2']);
        $filters = new FiltersConfig();

        $this->context->setResult($definition);
        $this->context->setFilters($filters);
        $this->processor->process($this->context);

        self::assertFalse($this->context->getFilters()->hasField('field'));
        self::assertFalse($this->context->getFilters()->hasField('association'));
    }

    public function testCompleteCompositeIdFiltersForAssociationWithCompositeIdentifierWhenFilterAlreadyExists(): void
    {
        $definition = new EntityDefinitionConfig();
        $definition->addField('field');
        $association = $definition->addField('association');
        $association->setTargetClass('Test\TargetEntity');
        $associationTargetEntity = $association->createAndSetTargetEntity();
        $associationTargetEntity->setIdentifierFieldNames(['id1', 'id2']);
        $filters = new FiltersConfig();
        $associationFilter = $filters->addField('association');
        $associationFilter->setType('custom_filter');

        $this->context->setResult($definition);
        $this->context->setFilters($filters);
        $this->processor->process($this->context);

        self::assertFalse($this->context->getFilters()->hasField('field'));
        self::assertEquals('custom_filter', $filters->getField('association')->getType());
    }
}
