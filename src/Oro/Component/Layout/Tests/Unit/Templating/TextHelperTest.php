<?php

namespace Oro\Component\Layout\Tests\Unit\Templating;

use Oro\Component\Layout\Templating\TextHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class TextHelperTest extends TestCase
{
    private TextHelper $helper;

    #[\Override]
    protected function setUp(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(function ($id, $parameters, $domain) {
                return strtr('trans!' . $domain . '!' . $id, $parameters);
            });

        $this->helper = new TextHelper($translator);
    }

    /**
     * @dataProvider processTextDataProvider
     */
    public function testProcessText(array|string|null $value, array|string|null $expected, ?string $domain = null): void
    {
        $this->assertSame($expected, $this->helper->processText($value, $domain));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function processTextDataProvider(): array
    {
        return [
            [null, null],
            ['', ''],
            ['foo', 'trans!messages!foo'],
            ['foo', 'trans!my_domain!foo', 'my_domain'],
            [
                ['foo'],
                ['foo']
            ],
            [
                ['foo' => 'bar'],
                ['foo' => 'bar']
            ],
            [
                ['label' => null],
                null
            ],
            [
                ['label' => null, 'translatable' => false],
                null
            ],
            [
                ['label' => ''],
                ''
            ],
            [
                ['label' => '', 'translatable' => false],
                ''
            ],
            [
                ['label' => ' '],
                'trans!messages! '
            ],
            [
                ['label' => ' ', 'translatable' => false],
                ' '
            ],
            [
                ['label' => 123],
                ['label' => 123]
            ],
            [
                ['label' => 123, 'translatable' => false],
                ['label' => 123, 'translatable' => false]
            ],
            [
                ['label' => 'foo'],
                'trans!messages!foo'
            ],
            [
                ['label' => 'foo', 'translatable' => false],
                'foo'
            ],
            [
                ['label' => 'foo', 'translation_domain' => 'domain'],
                'trans!domain!foo'
            ],
            [
                ['label' => 'foo %param1%', 'parameters' => ['%param1%' => 'bar']],
                'trans!messages!foo bar'
            ],
            [
                ['label' => 'foo %param1%', 'parameters' => ['%param1%' => 'bar'], 'translatable' => false],
                'foo bar'
            ],
            [
                [
                    'label'      => 'foo %param1%',
                    'parameters' => [
                        '%param1%' => [
                            'label' => 'bar'
                        ]
                    ]
                ],
                'trans!messages!foo trans!messages!bar'
            ],
            [
                [
                    'label'      => 'foo %param1%',
                    'parameters' => [
                        '%param1%' => [
                            'label'        => 'bar',
                            'translatable' => false
                        ]
                    ]
                ],
                'trans!messages!foo bar'
            ],
            [
                [
                    'label'      => 'foo %param1%',
                    'parameters' => [
                        '%param1%' => [
                            'label'              => 'bar',
                            'translation_domain' => 'domain'
                        ]
                    ]
                ],
                'trans!messages!foo trans!domain!bar'
            ],
            [
                [
                    'label'      => 'foo %param1%',
                    'parameters' => [
                        '%param1%' => [
                            'label'      => 'param1 %item1%',
                            'parameters' => [
                                '%item1%' => 'val1'
                            ]
                        ]
                    ]
                ],
                'trans!messages!foo trans!messages!param1 val1'
            ],
            [
                [
                    'label'      => 'foo %param1%',
                    'parameters' => [
                        '%param1%' => [
                            'label'      => 'param1 %item1%',
                            'parameters' => [
                                '%item1%' => [
                                    'label' => 'val1'
                                ]
                            ]
                        ]
                    ]
                ],
                'trans!messages!foo trans!messages!param1 trans!messages!val1'
            ],
            [
                [
                    'label'      => 'foo %param1%',
                    'parameters' => [
                        '%param1%' => [
                            'label'      => 'param1 %item1%',
                            'parameters' => [
                                '%item1%' => [
                                    'label' => 'val1'
                                ]
                            ]
                        ]
                    ]
                ],
                'trans!my_domain!foo trans!my_domain!param1 trans!my_domain!val1',
                'my_domain'
            ],
            [
                [
                    'item1' => 'val1',
                    'item2' => ['label' => 'val2'],
                    'label' => ['label' => 'val3']
                ],
                [
                    'item1' => 'val1',
                    'item2' => 'trans!messages!val2',
                    'label' => 'trans!messages!val3'
                ]
            ],
        ];
    }
}
