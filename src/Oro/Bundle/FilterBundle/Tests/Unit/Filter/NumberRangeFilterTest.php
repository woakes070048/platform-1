<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Filter;

use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Filter\NumberRangeFilter;
use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberRangeFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberRangeFilterTypeInterface;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class NumberRangeFilterTest extends NumberFilterTest
{
    /** @var NumberRangeFilter */
    protected $filter;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->filter = new NumberRangeFilter($this->formFactory, new FilterUtility());
        $this->filter->init('test-filter', [
            FilterUtility::DATA_NAME_KEY => 'field_name'
        ]);
    }

    /**
     * @dataProvider parseDataProvider
     */
    #[\Override]
    public function testParseData($data, $expected): void
    {
        $this->assertEquals(
            $expected,
            ReflectionUtil::callMethod($this->filter, 'parseData', [$data])
        );
    }

    /**
     * @dataProvider applyRangeProvider
     */
    public function testApplyRange(array $data, array $expected): void
    {
        $ds = $this->getFilterDatasource();
        $this->filter->apply($ds, $data);

        $where = $this->parseQueryCondition($ds);
        $this->assertEquals($expected['where'], $where);
    }

    public function applyRangeProvider(): array
    {
        return [
            'BETWEEN x AND y'        => [
                'data'     => [
                    'type'      => NumberRangeFilterType::TYPE_BETWEEN,
                    'value'     => 1,
                    'value_end' => 2
                ],
                'expected' => [
                    'where' => 'field_name >= 1 AND field_name <= 2'
                ]
            ],
            'BETWEEN x AND NULL'     => [
                'data'     => [
                    'type'  => NumberRangeFilterType::TYPE_BETWEEN,
                    'value' => 3
                ],
                'expected' => [
                    'where' => 'field_name >= 3'
                ]
            ],
            'BETWEEN NULL AND y'     => [
                'data'     => [
                    'type'      => NumberRangeFilterType::TYPE_BETWEEN,
                    'value_end' => 4
                ],
                'expected' => [
                    'where' => 'field_name <= 4'
                ]
            ],
            'NOT BETWEEN x AND y'    => [
                'data'     => [
                    'type'      => NumberRangeFilterType::TYPE_NOT_BETWEEN,
                    'value'     => 5,
                    'value_end' => 6
                ],
                'expected' => [
                    'where' => 'field_name < 5 OR field_name > 6'
                ]
            ],
            'NOT BETWEEN x AND NULL' => [
                'data'     => [
                    'type'  => NumberRangeFilterType::TYPE_NOT_BETWEEN,
                    'value' => 7
                ],
                'expected' => [
                    'where' => 'field_name < 7'
                ]
            ],
            'NOT BETWEEN NULL AND y' => [
                'data'     => [
                    'type'      => NumberRangeFilterType::TYPE_NOT_BETWEEN,
                    'value_end' => 8
                ],
                'expected' => [
                    'where' => 'field_name > 8'
                ]
            ],
        ];
    }

    #[\Override]
    public function parseDataProvider(): array
    {
        return [
            'invalid data, no value'                 => [
                [],
                false
            ],
            'invalid data, null range start and end' => [
                ['value' => null, 'value_end' => null],
                false
            ],
            'valid data, null type'                  => [
                ['value' => 1, 'value_end' => 2],
                ['value' => 1, 'value_end' => 2, 'type' => null],
            ],
            'valid data, type is TYPE_EMPTY'         => [
                ['value' => null, 'value_end' => null, 'type' => FilterUtility::TYPE_EMPTY],
                ['value' => null, 'value_end' => null, 'type' => FilterUtility::TYPE_EMPTY],
            ],
            'valid data, type is TYPE_NOT_EMPTY'     => [
                ['value' => null, 'value_end' => null, 'type' => FilterUtility::TYPE_NOT_EMPTY],
                ['value' => null, 'value_end' => null, 'type' => FilterUtility::TYPE_NOT_EMPTY],
            ],
            'valid data, empty start range'          => [
                ['value_end' => 2, 'type' => NumberRangeFilterType::TYPE_BETWEEN],
                ['value' => null, 'value_end' => 2, 'type' => NumberRangeFilterType::TYPE_BETWEEN],
            ],
            'valid data, empty end range'            => [
                ['value' => 1, 'value_end' => null, 'type' => NumberRangeFilterType::TYPE_NOT_BETWEEN],
                ['value' => 1, 'value_end' => null, 'type' => NumberRangeFilterType::TYPE_NOT_BETWEEN],
            ],
        ];
    }

    public function testPrepareDataWhenNoValueAndRangeRelatedFilterType(): void
    {
        self::assertSame(
            ['type' => (string)NumberRangeFilterTypeInterface::TYPE_BETWEEN, 'value' => null, 'value_end' => null],
            $this->filter->prepareData([
                'type' => (string)NumberRangeFilterTypeInterface::TYPE_BETWEEN
            ])
        );
    }

    public function testPrepareDataWithNullValueAndRangeRelatedFilterType(): void
    {
        self::assertSame(
            ['value' => null, 'type' => (string)NumberRangeFilterTypeInterface::TYPE_BETWEEN, 'value_end' => null],
            $this->filter->prepareData([
                'value' => null,
                'type'  => (string)NumberRangeFilterTypeInterface::TYPE_BETWEEN
            ])
        );
    }

    public function testPrepareDataWithNullEndValueAndRangeRelatedFilterType(): void
    {
        self::assertSame(
            ['value_end' => null, 'type' => (string)NumberRangeFilterTypeInterface::TYPE_BETWEEN, 'value' => null],
            $this->filter->prepareData([
                'value_end' => null,
                'type'      => (string)NumberRangeFilterTypeInterface::TYPE_BETWEEN
            ])
        );
    }

    public function testPrepareDataWithNullValuesAndRangeRelatedFilterType(): void
    {
        self::assertSame(
            ['value' => null, 'value_end' => null, 'type' => (string)NumberRangeFilterTypeInterface::TYPE_BETWEEN],
            $this->filter->prepareData([
                'value'     => null,
                'value_end' => null,
                'type'      => (string)NumberRangeFilterTypeInterface::TYPE_BETWEEN
            ])
        );
    }

    public function testPrepareDataWithEmptyStringValueAndRangeRelatedFilterType(): void
    {
        self::assertSame(
            ['value' => null, 'type' => (string)NumberRangeFilterTypeInterface::TYPE_BETWEEN, 'value_end' => null],
            $this->filter->prepareData([
                'value' => '',
                'type'  => (string)NumberRangeFilterTypeInterface::TYPE_BETWEEN
            ])
        );
    }

    public function testPrepareDataWithEmptyStringEndValueAndRangeRelatedFilterType(): void
    {
        self::assertSame(
            ['value_end' => null, 'type' => (string)NumberRangeFilterTypeInterface::TYPE_BETWEEN, 'value' => null],
            $this->filter->prepareData([
                'value_end' => '',
                'type'      => (string)NumberRangeFilterTypeInterface::TYPE_BETWEEN
            ])
        );
    }

    public function testPrepareDataWithEmptyStringValuesAndRangeRelatedFilterType(): void
    {
        self::assertSame(
            ['value' => null, 'value_end' => null, 'type' => (string)NumberRangeFilterTypeInterface::TYPE_BETWEEN],
            $this->filter->prepareData([
                'value'     => '',
                'value_end' => '',
                'type'      => (string)NumberRangeFilterTypeInterface::TYPE_BETWEEN
            ])
        );
    }

    public function testPrepareDataWithZeroValueAsStringAndRangeRelatedFilterType(): void
    {
        self::assertSame(
            ['value' => 0.0, 'type' => (string)NumberRangeFilterTypeInterface::TYPE_BETWEEN, 'value_end' => null],
            $this->filter->prepareData([
                'value' => '0',
                'type'  => (string)NumberRangeFilterTypeInterface::TYPE_BETWEEN
            ])
        );
    }

    public function testPrepareDataWithZeroEndValueAsStringAndRangeRelatedFilterType(): void
    {
        self::assertSame(
            ['value_end' => 0.0, 'type' => (string)NumberRangeFilterTypeInterface::TYPE_BETWEEN, 'value' => null],
            $this->filter->prepareData([
                'value_end' => '0',
                'type'      => (string)NumberRangeFilterTypeInterface::TYPE_BETWEEN
            ])
        );
    }

    public function testPrepareDataWithZeroValuesAsStringAndRangeRelatedFilterType(): void
    {
        self::assertSame(
            ['value' => 0.0, 'value_end' => 0.0, 'type' => (string)NumberRangeFilterTypeInterface::TYPE_BETWEEN],
            $this->filter->prepareData([
                'value'     => '0',
                'value_end' => '0',
                'type'      => (string)NumberRangeFilterTypeInterface::TYPE_BETWEEN
            ])
        );
    }

    public function testPrepareDataWithZeroValueAsIntegerAndRangeRelatedFilterType(): void
    {
        self::assertSame(
            ['value' => 0.0, 'type' => (string)NumberRangeFilterTypeInterface::TYPE_BETWEEN, 'value_end' => null],
            $this->filter->prepareData([
                'value' => 0,
                'type'  => (string)NumberRangeFilterTypeInterface::TYPE_BETWEEN
            ])
        );
    }

    public function testPrepareDataWithZeroEndValueAsIntegerAndRangeRelatedFilterType(): void
    {
        self::assertSame(
            ['value_end' => 0.0, 'type' => (string)NumberRangeFilterTypeInterface::TYPE_BETWEEN, 'value' => null],
            $this->filter->prepareData([
                'value_end' => 0,
                'type'      => (string)NumberRangeFilterTypeInterface::TYPE_BETWEEN
            ])
        );
    }

    public function testPrepareDataWithZeroValuesAsIntegerAndRangeRelatedFilterType(): void
    {
        self::assertSame(
            ['value' => 0.0, 'value_end' => 0.0, 'type' => (string)NumberRangeFilterTypeInterface::TYPE_BETWEEN],
            $this->filter->prepareData([
                'value'     => 0,
                'value_end' => 0,
                'type'      => (string)NumberRangeFilterTypeInterface::TYPE_BETWEEN
            ])
        );
    }

    public function testPrepareDataWithIntegerValueAndRangeRelatedFilterType(): void
    {
        self::assertSame(
            ['value' => 123.0, 'type' => (string)NumberRangeFilterTypeInterface::TYPE_BETWEEN, 'value_end' => null],
            $this->filter->prepareData([
                'value' => 123,
                'type'  => (string)NumberRangeFilterTypeInterface::TYPE_BETWEEN
            ])
        );
    }

    public function testPrepareDataWithIntegerEndValueAndRangeRelatedFilterType(): void
    {
        self::assertSame(
            ['value_end' => 123.0, 'type' => (string)NumberRangeFilterTypeInterface::TYPE_BETWEEN, 'value' => null],
            $this->filter->prepareData([
                'value_end' => 123,
                'type'      => (string)NumberRangeFilterTypeInterface::TYPE_BETWEEN
            ])
        );
    }

    public function testPrepareDataWithIntegerValuesAndRangeRelatedFilterType(): void
    {
        self::assertSame(
            ['value' => 123.0, 'value_end' => 234.0, 'type' => (string)NumberRangeFilterTypeInterface::TYPE_BETWEEN],
            $this->filter->prepareData([
                'value'     => 123,
                'value_end' => 234,
                'type'      => (string)NumberRangeFilterTypeInterface::TYPE_BETWEEN
            ])
        );
    }

    public function testPrepareDataWithIntegerValueAsStringAndRangeRelatedFilterType(): void
    {
        self::assertSame(
            ['value' => 123.0, 'type' => (string)NumberRangeFilterTypeInterface::TYPE_BETWEEN, 'value_end' => null],
            $this->filter->prepareData([
                'value' => '123',
                'type'  => (string)NumberRangeFilterTypeInterface::TYPE_BETWEEN
            ])
        );
    }

    public function testPrepareDataWithIntegerEndValueAsStringAndRangeRelatedFilterType(): void
    {
        self::assertSame(
            ['value_end' => 123.0, 'type' => (string)NumberRangeFilterTypeInterface::TYPE_BETWEEN, 'value' => null],
            $this->filter->prepareData([
                'value_end' => '123',
                'type'      => (string)NumberRangeFilterTypeInterface::TYPE_BETWEEN
            ])
        );
    }

    public function testPrepareDataWithIntegerValuesAsStringAndRangeRelatedFilterType(): void
    {
        self::assertSame(
            ['value' => 123.0, 'value_end' => 234.0, 'type' => (string)NumberRangeFilterTypeInterface::TYPE_BETWEEN],
            $this->filter->prepareData([
                'value'     => '123',
                'value_end' => '234',
                'type'      => (string)NumberRangeFilterTypeInterface::TYPE_BETWEEN
            ])
        );
    }

    public function testPrepareDataWithFloatValueAndRangeRelatedFilterType(): void
    {
        self::assertSame(
            ['value' => 123.1, 'type' => (string)NumberRangeFilterTypeInterface::TYPE_BETWEEN, 'value_end' => null],
            $this->filter->prepareData([
                'value' => 123.1,
                'type'  => (string)NumberRangeFilterTypeInterface::TYPE_BETWEEN
            ])
        );
    }

    public function testPrepareDataWithFloatEndValueAndRangeRelatedFilterType(): void
    {
        self::assertSame(
            ['value_end' => 123.1, 'type' => (string)NumberRangeFilterTypeInterface::TYPE_BETWEEN, 'value' => null],
            $this->filter->prepareData([
                'value_end' => 123.1,
                'type'      => (string)NumberRangeFilterTypeInterface::TYPE_BETWEEN
            ])
        );
    }

    public function testPrepareDataWithFloatValuesAndRangeRelatedFilterType(): void
    {
        self::assertSame(
            ['value' => 123.1, 'value_end' => 234.9, 'type' => (string)NumberRangeFilterTypeInterface::TYPE_BETWEEN],
            $this->filter->prepareData([
                'value'     => 123.1,
                'value_end' => 234.9,
                'type'      => (string)NumberRangeFilterTypeInterface::TYPE_BETWEEN
            ])
        );
    }

    public function testPrepareDataWithFloatValueAsStringAndRangeRelatedFilterType(): void
    {
        self::assertSame(
            ['value' => 123.1, 'type' => (string)NumberRangeFilterTypeInterface::TYPE_BETWEEN, 'value_end' => null],
            $this->filter->prepareData([
                'value' => '123.1',
                'type'  => (string)NumberRangeFilterTypeInterface::TYPE_BETWEEN
            ])
        );
    }

    public function testPrepareDataWithFloatEndValueAsStringAndRangeRelatedFilterType(): void
    {
        self::assertSame(
            ['value_end' => 123.1, 'type' => (string)NumberRangeFilterTypeInterface::TYPE_BETWEEN, 'value' => null],
            $this->filter->prepareData([
                'value_end' => '123.1',
                'type'      => (string)NumberRangeFilterTypeInterface::TYPE_BETWEEN
            ])
        );
    }

    public function testPrepareDataWithFloatValuesAsStringAndRangeRelatedFilterType(): void
    {
        self::assertSame(
            ['value' => 123.1, 'type' => (string)NumberRangeFilterTypeInterface::TYPE_BETWEEN, 'value_end' => null],
            $this->filter->prepareData([
                'value' => '123.1',
                'type'  => (string)NumberRangeFilterTypeInterface::TYPE_BETWEEN
            ])
        );
    }

    public function testPrepareDataWithNotNumericStringValueAndRangeRelatedFilterType(): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->filter->prepareData([
            'value' => 'abc',
            'type'  => (string)NumberRangeFilterTypeInterface::TYPE_BETWEEN
        ]);
    }

    public function testPrepareDataWithNotNumericStringEndValueAndRangeRelatedFilterType(): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->filter->prepareData([
            'value_end' => 'abc',
            'type'      => (string)NumberRangeFilterTypeInterface::TYPE_BETWEEN
        ]);
    }

    public function testPrepareDataWithNotNumericStringValuesAndRangeRelatedFilterType(): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->filter->prepareData([
            'value'     => 'abc',
            'value_end' => 'abc',
            'type'      => (string)NumberRangeFilterTypeInterface::TYPE_BETWEEN
        ]);
    }

    public function testPrepareDataWithArrayValueAndRangeRelatedFilterType(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The value is not valid. Expected a scalar value, "array" given');
        $this->filter->prepareData([
            'value' => [123],
            'type'  => (string)NumberRangeFilterTypeInterface::TYPE_BETWEEN
        ]);
    }

    public function testPrepareDataWithArrayEndValueAndRangeRelatedFilterType(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The value is not valid. Expected a scalar value, "array" given');
        $this->filter->prepareData([
            'value_end' => [123],
            'type'      => (string)NumberRangeFilterTypeInterface::TYPE_BETWEEN
        ]);
    }

    public function testPrepareDataWithArrayValuesAndRangeRelatedFilterType(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The value is not valid. Expected a scalar value, "array" given');
        $this->filter->prepareData([
            'value'     => [123],
            'value_end' => [123],
            'type'      => (string)NumberRangeFilterTypeInterface::TYPE_BETWEEN
        ]);
    }
}
