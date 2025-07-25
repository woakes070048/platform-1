<?php

namespace Oro\Component\PhpUtils\Tests\Unit;

use Oro\Component\PhpUtils\ArrayUtil;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ArrayUtilTest extends TestCase
{
    /**
     * @dataProvider interposeDataProvider
     */
    public function testInterpose($separator, $array, $expectedResult): void
    {
        $this->assertEquals($expectedResult, ArrayUtil::interpose($separator, $array));
    }

    public function interposeDataProvider(): array
    {
        return [
            [
                ',',
                ['a', 'b', 'c', 'd'],
                ['a', ',', 'b', ',', 'c', ',', 'd'],
            ],
            [
                ['array'],
                ['a', 'b', 'c', ['d']],
                ['a', ['array'], 'b', ['array'], 'c', ['array'], ['d']],
            ],
        ];
    }

    public function testCreateOrderedComparator(): void
    {
        $order = array_flip(['a', 'z', 'd', 'e']);
        $array = [
            'b' => 'val b',
            'd' => 'val d',
            'z' => 'val z',
            'c' => 'val c',
            'e' => 'val e',
        ];
        $expectedResult = [
            'z' => 'val z',
            'd' => 'val d',
            'e' => 'val e',
            'b' => 'val b',
            'c' => 'val c',
        ];

        uksort($array, ArrayUtil::createOrderedComparator($order));
        $this->assertEquals(array_keys($expectedResult), array_keys($array));
        $this->assertEquals(array_values($expectedResult), array_values($array));
    }

    /**
     * @dataProvider isAssocDataProvider
     */
    public function testIsAssoc($array, $expectedResult): void
    {
        $this->assertEquals($expectedResult, ArrayUtil::isAssoc($array));
    }

    public function isAssocDataProvider(): array
    {
        return [
            [[1, 2, 3], false],
            [[0 => 1, 1 => 2, 2 => 3], false],
            [['a' => 1, 'b' => 2, 'c' => 3], true],
            [[1, 'b' => 2, 3], true],
            [[1 => 1, 2 => 2, 3 => 3], true]
        ];
    }

    public function testSortByEmpty(): void
    {
        $array = [];

        ArrayUtil::sortBy($array);
        $this->assertSame([], $array);
    }

    public function testSortByArrayNoOrder(): void
    {
        $array = [
            ['name' => '1'],
            ['name' => '2'],
            ['name' => '3'],
        ];

        ArrayUtil::sortBy($array);
        $this->assertSame(
            [
                ['name' => '1'],
                ['name' => '2'],
                ['name' => '3'],
            ],
            $array
        );
    }

    public function testSortByArrayNoOrderReverse(): void
    {
        $array = [
            ['name' => '1'],
            ['name' => '2'],
            ['name' => '3'],
        ];

        ArrayUtil::sortBy($array, true);
        $this->assertSame(
            [
                ['name' => '1'],
                ['name' => '2'],
                ['name' => '3'],
            ],
            $array
        );
    }

    public function testSortByArraySameOrder(): void
    {
        $array = [
            ['name' => '1', 'priority' => 100],
            ['name' => '2', 'priority' => 100],
            ['name' => '3', 'priority' => 100],
        ];

        ArrayUtil::sortBy($array);
        $this->assertSame(
            [
                ['name' => '1', 'priority' => 100],
                ['name' => '2', 'priority' => 100],
                ['name' => '3', 'priority' => 100],
            ],
            $array
        );
    }

    public function testSortByArraySameOrderReverse(): void
    {
        $array = [
            ['name' => '1', 'priority' => 100],
            ['name' => '2', 'priority' => 100],
            ['name' => '3', 'priority' => 100],
        ];

        ArrayUtil::sortBy($array, true);
        $this->assertSame(
            [
                ['name' => '1', 'priority' => 100],
                ['name' => '2', 'priority' => 100],
                ['name' => '3', 'priority' => 100],
            ],
            $array
        );
    }

    public function testSortByArrayNumeric(): void
    {
        $array = [
            ['name' => '1'],
            ['name' => '2'],
            ['name' => '3', 'priority' => 100],
            ['name' => '4'],
            ['name' => '5', 'priority' => -100],
            ['name' => '6', 'priority' => 100],
            ['name' => '7', 'priority' => -100],
            ['name' => '8', 'priority' => 0],
            ['name' => '9'],
        ];

        ArrayUtil::sortBy($array);
        $this->assertSame(
            [
                ['name' => '5', 'priority' => -100],
                ['name' => '7', 'priority' => -100],
                ['name' => '1'],
                ['name' => '2'],
                ['name' => '4'],
                ['name' => '8', 'priority' => 0],
                ['name' => '9'],
                ['name' => '3', 'priority' => 100],
                ['name' => '6', 'priority' => 100],
            ],
            $array
        );
    }

    public function testSortByArrayNumericReverse(): void
    {
        $array = [
            ['name' => '1'],
            ['name' => '2'],
            ['name' => '3', 'priority' => 100],
            ['name' => '4'],
            ['name' => '5', 'priority' => -100],
            ['name' => '6', 'priority' => 100],
            ['name' => '7', 'priority' => -100],
            ['name' => '8', 'priority' => 0],
            ['name' => '9'],
        ];

        ArrayUtil::sortBy($array, true);
        $this->assertSame(
            [
                ['name' => '3', 'priority' => 100],
                ['name' => '6', 'priority' => 100],
                ['name' => '1'],
                ['name' => '2'],
                ['name' => '4'],
                ['name' => '8', 'priority' => 0],
                ['name' => '9'],
                ['name' => '5', 'priority' => -100],
                ['name' => '7', 'priority' => -100],
            ],
            $array
        );
    }

    public function testSortByAssocArrayNumeric(): void
    {
        $array = [
            'i1' => ['name' => '1'],
            'i2' => ['name' => '2'],
            'i3' => ['name' => '3', 'priority' => 100],
            'i4' => ['name' => '4'],
            'i5' => ['name' => '5', 'priority' => -100],
            'i6' => ['name' => '6', 'priority' => 100],
            'i7' => ['name' => '7', 'priority' => -100],
            'i8' => ['name' => '8', 'priority' => 0],
            'i9' => ['name' => '9'],
        ];

        ArrayUtil::sortBy($array);
        $this->assertSame(
            [
                'i5' => ['name' => '5', 'priority' => -100],
                'i7' => ['name' => '7', 'priority' => -100],
                'i1' => ['name' => '1'],
                'i2' => ['name' => '2'],
                'i4' => ['name' => '4'],
                'i8' => ['name' => '8', 'priority' => 0],
                'i9' => ['name' => '9'],
                'i3' => ['name' => '3', 'priority' => 100],
                'i6' => ['name' => '6', 'priority' => 100],
            ],
            $array
        );
    }

    public function testSortByAssocArrayNumericReverse(): void
    {
        $array = [
            'i1' => ['name' => '1'],
            'i2' => ['name' => '2'],
            'i3' => ['name' => '3', 'priority' => 100],
            'i4' => ['name' => '4'],
            'i5' => ['name' => '5', 'priority' => -100],
            'i6' => ['name' => '6', 'priority' => 100],
            'i7' => ['name' => '7', 'priority' => -100],
            'i8' => ['name' => '8', 'priority' => 0],
            'i9' => ['name' => '9'],
        ];

        ArrayUtil::sortBy($array, true);
        $this->assertSame(
            [
                'i3' => ['name' => '3', 'priority' => 100],
                'i6' => ['name' => '6', 'priority' => 100],
                'i1' => ['name' => '1'],
                'i2' => ['name' => '2'],
                'i4' => ['name' => '4'],
                'i8' => ['name' => '8', 'priority' => 0],
                'i9' => ['name' => '9'],
                'i5' => ['name' => '5', 'priority' => -100],
                'i7' => ['name' => '7', 'priority' => -100],
            ],
            $array
        );
    }

    public function testSortByArrayString(): void
    {
        $array = [
            ['name' => 'a'],
            ['name' => 'c'],
            ['name' => 'b'],
        ];

        ArrayUtil::sortBy($array, false, 'name', SORT_STRING);
        $this->assertSame(
            [
                ['name' => 'a'],
                ['name' => 'b'],
                ['name' => 'c'],
            ],
            $array
        );
    }

    public function testSortByArrayStringReverse(): void
    {
        $array = [
            ['name' => 'a'],
            ['name' => 'c'],
            ['name' => 'b'],
        ];

        ArrayUtil::sortBy($array, true, 'name', SORT_STRING);
        $this->assertSame(
            [
                ['name' => 'c'],
                ['name' => 'b'],
                ['name' => 'a'],
            ],
            $array
        );
    }

    public function testSortByArrayStringCaseInsensitive(): void
    {
        $array = [
            ['name' => 'a'],
            ['name' => 'C'],
            ['name' => 'B'],
        ];

        ArrayUtil::sortBy($array, false, 'name', SORT_STRING | SORT_FLAG_CASE);
        $this->assertSame(
            [
                ['name' => 'a'],
                ['name' => 'B'],
                ['name' => 'C'],
            ],
            $array
        );
    }

    public function testSortByArrayStringCaseInsensitiveReverse(): void
    {
        $array = [
            ['name' => 'a'],
            ['name' => 'C'],
            ['name' => 'B'],
        ];

        ArrayUtil::sortBy($array, true, 'name', SORT_STRING | SORT_FLAG_CASE);
        $this->assertSame(
            [
                ['name' => 'C'],
                ['name' => 'B'],
                ['name' => 'a'],
            ],
            $array
        );
    }

    public function testSortByArrayPath(): void
    {
        $array = [
            ['name' => '1', 'child' => ['priority' => 1]],
            ['name' => '2', 'child' => ['priority' => 3]],
            ['name' => '3', 'child' => ['priority' => 2]],
        ];

        ArrayUtil::sortBy($array, false, '[child][priority]');
        $this->assertSame(
            [
                ['name' => '1', 'child' => ['priority' => 1]],
                ['name' => '3', 'child' => ['priority' => 2]],
                ['name' => '2', 'child' => ['priority' => 3]],
            ],
            $array
        );
    }

    public function testSortByObject(): void
    {
        $obj1 = $this->createObject(['name' => '1', 'priority' => null]);
        $obj2 = $this->createObject(['name' => '2', 'priority' => 100]);
        $obj3 = $this->createObject(['name' => '3', 'priority' => 0]);
        $array = [$obj1, $obj2, $obj3];

        ArrayUtil::sortBy($array);
        $this->assertSame(
            [
                $obj1,
                $obj3,
                $obj2,
            ],
            $array
        );
    }

    public function testSortByObjectPath(): void
    {
        $obj1 = $this->createObject(
            ['name' => '1', 'child' => $this->createObject(['priority' => null])]
        );
        $obj2 = $this->createObject(
            ['name' => '2', 'child' => $this->createObject(['priority' => 100])]
        );
        $obj3 = $this->createObject(
            ['name' => '3', 'child' => $this->createObject(['priority' => 0])]
        );
        $array = [$obj1, $obj2, $obj3];

        ArrayUtil::sortBy($array, false, 'child.priority');
        $this->assertSame(
            [
                $obj1,
                $obj3,
                $obj2,
            ],
            $array
        );
    }

    public function testSortByClosure(): void
    {
        $obj1 = $this->createObject(['name' => '1', 'priority' => null]);
        $obj2 = $this->createObject(['name' => '2', 'priority' => 100]);
        $obj3 = $this->createObject(['name' => '3', 'priority' => 0]);
        $array = [$obj1, $obj2, $obj3];

        ArrayUtil::sortBy(
            $array,
            false,
            function ($item) {
                return $item->priority;
            }
        );
        $this->assertSame(
            [
                $obj1,
                $obj3,
                $obj2,
            ],
            $array
        );
    }

    public function testSortByCallable(): void
    {
        $obj1 = $this->createObject(['name' => '1', 'priority' => null]);
        $obj2 = $this->createObject(['name' => '2', 'priority' => 100]);
        $obj3 = $this->createObject(['name' => '3', 'priority' => 0]);
        $array = [$obj1, $obj2, $obj3];

        ArrayUtil::sortBy(
            $array,
            false,
            [$this, 'getObjectPriority']
        );
        $this->assertSame(
            [
                $obj1,
                $obj3,
                $obj2,
            ],
            $array
        );
    }

    /**
     * @dataProvider someProvider
     */
    public function testSome(callable $callback, array $array, $expectedResult): void
    {
        $this->assertSame($expectedResult, ArrayUtil::some($callback, $array));
    }

    public function someProvider(): array
    {
        return [
            [
                function ($item) {
                    return $item === 1;
                },
                [0, 1, 2, 3, 4],
                true,
            ],
            [
                function ($item) {
                    return $item === 0;
                },
                [0, 1, 2, 3, 4],
                true,
            ],
            [
                function ($item) {
                    return $item === 4;
                },
                [0, 1, 2, 3, 4],
                true,
            ],
            [
                function ($item) {
                    return $item === 5;
                },
                [0, 1, 2, 3, 4],
                false,
            ],
        ];
    }

    /**
     * @dataProvider findProvider
     */
    public function testFind(callable $callback, array $array, $expectedResult): void
    {
        $this->assertSame($expectedResult, ArrayUtil::find($callback, $array));
    }

    public function findProvider(): array
    {
        return [
            [
                function ($item) {
                    return $item === 1;
                },
                [0, 1, 2, 3, 4],
                1,
            ],
            [
                function ($item) {
                    return $item === 0;
                },
                [0, 1, 2, 3, 4],
                0,
            ],
            [
                function ($item) {
                    return $item === 4;
                },
                [0, 1, 2, 3, 4],
                4,
            ],
            [
                function ($item) {
                    return $item === 5;
                },
                [0, 1, 2, 3, 4],
                null,
            ],
        ];
    }

    /**
     * @dataProvider dropWhileProvider
     */
    public function testDropWhile(callable $callback, array $array, $expectedResult): void
    {
        $this->assertEquals($expectedResult, ArrayUtil::dropWhile($callback, $array));
    }

    public function dropWhileProvider(): array
    {
        return [
            [
                function ($item) {
                    return $item !== 2;
                },
                [],
                [],
            ],
            [
                function ($item) {
                    return $item !== 2;
                },
                [0, 1, 2, 3, 4, 5],
                [2, 3, 4, 5],
            ],
            [
                function ($item) {
                    return $item !== 0;
                },
                [0, 1, 2, 3, 4, 5],
                [0, 1, 2, 3, 4, 5],
            ],
            [
                function ($item) {
                    return $item !== 6;
                },
                [0, 1, 2, 3, 4, 5],
                [],
            ],
        ];
    }

    /**
     * @dataProvider shiftRangeProvider
     */
    public function testShiftRange(array $sortedUniqueInts, $expectedResult, $expectedShiftedUniqueInts): void
    {
        $this->assertEquals($expectedResult, ArrayUtil::shiftRange($sortedUniqueInts));
        $this->assertEquals($expectedShiftedUniqueInts, $sortedUniqueInts);
    }

    public function shiftRangeProvider(): array
    {
        return [
            'empty' => [
                [],
                false,
                [],
            ],
            '1 item' => [
                [5],
                [5, 5],
                [],
            ],
            '2 items' => [
                [5, 6],
                [5, 6],
                [],
            ],
            'first' => [
                [1, 3, 5],
                [1, 1],
                [3, 5],
            ],
            'first to last' => [
                [1, 2, 3, 4, 5],
                [1, 5],
                [],
            ],
            'first to gap' => [
                [1, 2, 3, 5, 6],
                [1, 3],
                [5, 6],
            ],
        ];
    }

    /**
     * @dataProvider intRangesProvider
     */
    public function testIntRanges($ints, array $expectedResult): void
    {
        $this->assertEquals($expectedResult, ArrayUtil::intRanges($ints));
    }

    public function intRangesProvider(): array
    {
        return [
            [
                [],
                [],
            ],
            [
                [1],
                [
                    [1, 1],
                ]
            ],
            [
                [5, 5, 3, 1, 6, 4, 100],
                [
                    [1, 1],
                    [3, 6],
                    [100, 100],
                ]
            ]
        ];
    }

    /**
     * @dataProvider unsetPathDataProvider
     */
    public function testUnsetPath(array $array, array $path, array $expectedValue): void
    {
        $this->assertEquals($expectedValue, ArrayUtil::unsetPath($array, $path));
    }

    public function unsetPathDataProvider(): array
    {
        return [
            'unset with empty path' => [
                ['a' => 'aval'],
                [],
                ['a' => 'aval'],
            ],
            'unset with path having 1 element' => [
                ['a' => 'aval'],
                ['a'],
                [],
            ],
            'unset with invalid path having 1 element' => [
                ['a' => 'aval'],
                ['b'],
                ['a' => 'aval'],
            ],
            'unset with path having more elements' => [
                [
                    'a' => 'aval',
                    'b' => [
                        'c' => 'cval',
                        'd' => [
                            'e' => 'eval',
                        ],
                    ],
                ],
                ['b', 'c'],
                [
                    'a' => 'aval',
                    'b' => [
                        'd' => [
                            'e' => 'eval',
                        ],
                    ],
                ],
            ],
            'unset with invalid path having more elements' => [
                [
                    'a' => 'aval',
                    'b' => [
                        'c' => 'cval',
                        'd' => [
                            'e' => 'eval',
                        ],
                    ],
                ],
                ['a', 'b', 'c'],
                [
                    'a' => 'aval',
                    'b' => [
                        'c' => 'cval',
                        'd' => [
                            'e' => 'eval',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider getInDataProvider
     */
    public function testGetIn(array $array, array $path, $defaultValue, $expectedValue): void
    {
        $this->assertEquals($expectedValue, ArrayUtil::getIn($array, $path, $defaultValue));
    }

    public function getInDataProvider(): array
    {
        return [
            'reading non existing key from empty array' => [
                [],
                ['k2', 'k2.2', 'nonExistent'],
                null,
                null,
            ],
            'reading non existing key from array' => [
                ['k1' => 'v1', 'k2' => ['k2.1' => 'v2.1', 'k2.2' => 'v2.2']],
                ['k2', 'k2.2', 'nonExistent'],
                null,
                null,
            ],
            'reading non existing key from array with overwritten default value' => [
                ['k1' => 'v1', 'k2' => ['k2.1' => 'v2.1', 'k2.2' => 'v2.2']],
                ['k2', 'k2.2', 'nonExistent'],
                'default',
                'default',
            ],
            'reading simple key from array' => [
                ['k1' => 'v1', 'k2' => ['k2.1' => 'v2.1', 'k2.2' => 'v2.2']],
                ['k1'],
                null,
                'v1',
            ],
            'reading multivalue key from array' => [
                ['k1' => 'v1', 'k2' => ['k2.1' => 'v2.1', 'k2.2' => 'v2.2']],
                ['k2', 'k2.2'],
                null,
                'v2.2',
            ],
        ];
    }

    /**
     * @dataProvider mergeDataProvider
     */
    public function testArrayMergeRecursiveDistinct(array $expected, array $first, array $second): void
    {
        $this->assertEquals($expected, ArrayUtil::arrayMergeRecursiveDistinct($first, $second));
    }

    public function mergeDataProvider(): array
    {
        return [
            [
                [
                    'a',
                    'b',
                    'c' => [
                        'd' => 'd2',
                        'e' => 'e1'
                    ]
                ],
                ['a', 'c' => ['d' => 'd1', 'e' => 'e1']],
                ['b', 'c' => ['d' => 'd2']]
            ],
            [
                [
                    'a',
                    'b',
                    'c' => ['e1','e2']
                ],
                [
                    'a',
                    'c' => 'e1',
                ],
                [
                    'b',
                    'c' => ['e1','e2']
                ],
            ]
        ];
    }

    /**
     * @param object $obj
     *
     * @return mixed
     */
    public function getObjectPriority($obj)
    {
        return $obj->priority;
    }

    /**
     * @param array $properties
     *
     * @return object
     */
    protected function createObject($properties)
    {
        $obj = new \stdClass();
        foreach ($properties as $name => $val) {
            $obj->$name = $val;
        }

        return $obj;
    }
}
