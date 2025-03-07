<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\DataCollector;

use Doctrine\DBAL\Logging\DebugStack;
use Oro\Bundle\EntityBundle\DataCollector\DuplicateQueriesDataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DuplicateQueriesDataCollectorTest extends \PHPUnit\Framework\TestCase
{
    /** @var DuplicateQueriesDataCollector */
    private $collector;

    #[\Override]
    protected function setUp(): void
    {
        $this->collector = new DuplicateQueriesDataCollector();
    }

    public function testGetName()
    {
        $this->assertEquals('duplicate_queries', $this->collector->getName());
    }

    /**
     * @dataProvider collectDataProvider
     */
    public function testCollect(array $loggers, int $expectedCount, array $expectedIdentical, array $expectedSimilar)
    {
        foreach ($loggers as $loggerName => $queries) {
            $logger = new DebugStack();
            $logger->queries = $queries;
            $this->collector->addLogger($loggerName, $logger);
        }
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $this->collector->collect($request, $response);
        $this->assertEquals($expectedCount, $this->collector->getQueriesCount());
        $this->assertEquals($expectedIdentical, $this->collector->getIdenticalQueries());
        $this->assertEquals(count($expectedIdentical), $this->collector->getIdenticalQueriesCount());
        $this->assertEquals($expectedSimilar, $this->collector->getSimilarQueries());
        $this->assertEquals(count($expectedSimilar), $this->collector->getSimilarQueriesCount());
    }

    public function collectDataProvider(): array
    {
        return [
            [
                'loggers' => [
                    'default' => [
                        [
                            'sql' => 'select * from table where id = ?',
                            'params' => [1],
                        ],
                        [
                            'sql' => 'select * from table where id = ?',
                            'params' => [2],
                        ],
                        [
                            'sql' => 'select * from table',
                            'params' => [],
                        ],
                        [
                            'sql' => 'select * from table',
                            'params' => [],
                        ],
                        [
                            'sql' => 'select * from table',
                            'params' => [],
                        ],
                    ],
                    'config' => [
                        [
                            'sql' => 'select * from table where number = ?',
                            'params' => [2],
                        ],
                        [
                            'sql' => 'select * from table where number = ?',
                            'params' => [2],
                        ],
                        [
                            'sql' => 'select * from table where number = ?',
                            'params' => [1],
                        ],
                        [
                            'sql' => 'select * from table order by id',
                            'params' => [],
                        ],
                        [
                            'sql' => 'select * from table where name = ?',
                            'params' => ['name'],
                        ]
                    ]
                ],
                'expectedCount' => 10,
                'expectedIdentical' => [
                    'default' => [
                        [
                            'sql' => 'select * from table',
                            'parameters' => [],
                            'count' => 3,
                        ]
                    ],
                    'config' => [
                        [
                            'sql' => 'select * from table where number = ?',
                            'parameters' => [2],
                            'count' => 2,
                        ]
                    ],
                ],
                'expectedSimilar' => [
                    'default' => [
                        [
                            'sql' => 'select * from table where id = ?',
                            'count' => 2,
                        ]
                    ],
                    'config' => [
                        [
                            'sql' => 'select * from table where number = ?',
                            'count' => 3,
                        ]
                    ]
                ],
            ]
        ];
    }

    public function testGet()
    {
        $this->assertEquals(0, $this->collector->getQueriesCount());
        $this->assertEquals(0, $this->collector->getIdenticalQueriesCount());
        $this->assertEquals([], $this->collector->getIdenticalQueries());
        $this->assertEquals(0, $this->collector->getSimilarQueriesCount());
        $this->assertEquals([], $this->collector->getSimilarQueries());
    }
}
