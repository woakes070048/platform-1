<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetConfig\JsonApi;

use Oro\Bundle\ApiBundle\Processor\GetConfig\JsonApi\FixFieldNaming;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetConfig\ConfigProcessorTestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FixFieldNamingTest extends ConfigProcessorTestCase
{
    private FixFieldNaming $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new FixFieldNaming();
    }

    public function testProcessWhenNoFields(): void
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
            ]
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all'
            ],
            $this->context->getResult()
        );
    }

    public function testProcessWithUnknownIdentifierAndFieldNamedId(): void
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'id' => null
            ]
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'id' => null
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessWithIdentifierNamedId(): void
    {
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['id'],
            'fields'                 => [
                'id' => null
            ]
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy'       => 'all',
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id' => null
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessWithIdentifierNotNamedId(): void
    {
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['name'],
            'fields'                 => [
                'name' => null,
                'id'   => null
            ]
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy'       => 'all',
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'      => [
                        'property_path' => 'name'
                    ],
                    'classId' => [
                        'property_path' => 'id'
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessWithIdentifierNotNamedIdAndHasFieldNamedIdButDoesNotHaveIdentifierField(): void
    {
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['name'],
            'fields'                 => [
                'id' => null
            ]
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy'       => 'all',
                'identifier_field_names' => ['name'],
                'fields'                 => [
                    'classId' => [
                        'property_path' => 'id'
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessWithCompositeIdentifierWhenFieldNamedIdIsPartOfIdentifier(): void
    {
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['id', 'id1'],
            'fields'                 => [
                'id'  => null,
                'id1' => null
            ]
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy'       => 'all',
                'identifier_field_names' => ['classId', 'id1'],
                'fields'                 => [
                    'classId' => [
                        'property_path' => 'id'
                    ],
                    'id1'     => null
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessWithCompositeIdentifierWhenFieldNamedIdIsPartOfIdentifierAndHasPropertyPath(): void
    {
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['id', 'id1'],
            'fields'                 => [
                'id'  => [
                    'property_path' => 'realId'
                ],
                'id1' => null
            ]
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy'       => 'all',
                'identifier_field_names' => ['classId', 'id1'],
                'fields'                 => [
                    'classId' => [
                        'property_path' => 'realId'
                    ],
                    'id1'     => null
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessWithCompositeIdentifierWhenNoFieldNamedIdInIdentifier(): void
    {
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['id1', 'id2'],
            'fields'                 => [
                'id1' => null,
                'id2' => null
            ]
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy'       => 'all',
                'identifier_field_names' => ['id1', 'id2'],
                'fields'                 => [
                    'id1' => null,
                    'id2' => null
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessWhenExistsFieldNamedType(): void
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'type' => null
            ]
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'classType' => [
                        'property_path' => 'type'
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessWhenIdentifierFieldNamedIdHasPropertyPath(): void
    {
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['id'],
            'fields'                 => [
                'id' => [
                    'property_path' => 'realId'
                ]
            ]
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy'       => 'all',
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id' => [
                        'property_path' => 'realId'
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessWhenFieldNamedIdHasPropertyPath(): void
    {
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['name'],
            'fields'                 => [
                'name' => null,
                'id'   => [
                    'property_path' => 'realId'
                ]
            ]
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy'       => 'all',
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'      => [
                        'property_path' => 'name'
                    ],
                    'classId' => [
                        'property_path' => 'realId'
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessWhenFieldNamedTypeHasPropertyPath(): void
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'type' => [
                    'property_path' => 'realType'
                ]
            ]
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'classType' => [
                        'property_path' => 'realType'
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessWhenIdFieldWithGuessedNameAlreadyExists(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'The "id" reserved word cannot be used as a field name'
            . ' and it cannot be renamed to "classId" because a field with this name already exists.'
        );

        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['name'],
            'fields'                 => [
                'id'      => null,
                'classId' => null,
                'name'    => null
            ]
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);
    }

    public function testProcessWhenTypeFieldWithGuessedNameAlreadyExists(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'The "type" reserved word cannot be used as a field name'
            . ' and it cannot be renamed to "classType" because a field with this name already exists.'
        );

        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'type'      => null,
                'classType' => null
            ]
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);
    }
}
