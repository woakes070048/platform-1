<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Async\Topic;

use Oro\Bundle\ApiBundle\Batch\Async\Topic\UpdateListCreateChunkJobsTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class UpdateListCreateChunkJobsTopicTest extends AbstractTopicTestCase
{
    #[\Override]
    protected function getTopic(): TopicInterface
    {
        return new UpdateListCreateChunkJobsTopic();
    }

    #[\Override]
    public function validBodyDataProvider(): array
    {
        $requiredOptionsSet = [
            'operationId' => 1,
            'entityClass' => \stdClass::class,
            'requestType' => ['bar', 'baz'],
            'version' => 'latest',
            'rootJobId' => 1,
            'chunkJobNameTemplate' => 'templateName'
        ];
        $fullOptionsSet = array_merge(
            $requiredOptionsSet,
            [
                'synchronousMode' => true,
                'firstChunkFileIndex' => 1,
                'aggregateTime' => 1
            ]
        );

        return [
            'only required options' => [
                'body' => $requiredOptionsSet,
                'expectedBody' => array_merge(
                    $requiredOptionsSet,
                    [
                        'synchronousMode' => false,
                        'firstChunkFileIndex' => 0,
                        'aggregateTime' => 0
                    ]
                )
            ],
            'full set of options' => [
                'body' => $fullOptionsSet,
                'expectedBody' => $fullOptionsSet
            ]
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    #[\Override]
    public function invalidBodyDataProvider(): array
    {
        return [
            'empty' => [
                'body' => [],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' =>
                    '/The required options "chunkJobNameTemplate", "entityClass", "operationId", "requestType", '
                        . '"rootJobId", "version" are missing./'
            ],
            'wrong operationId type' => [
                'body' => [
                    'operationId' => '1',
                    'entityClass' => \stdClass::class,
                    'requestType' => ['bar', 'baz'],
                    'version' => 'latest',
                    'rootJobId' => 1,
                    'chunkJobNameTemplate' => 'templateName'
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "operationId" with value "1" is expected to be of type "int"/'
            ],
            'wrong entityClass type' => [
                'body' => [
                    'operationId' => 1,
                    'entityClass' => 1,
                    'requestType' => ['bar', 'baz'],
                    'version' => 'latest',
                    'rootJobId' => 1,
                    'chunkJobNameTemplate' => 'templateName'
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "entityClass" with value 1 is expected to be of type "string"/'
            ],
            'wrong requestType type' => [
                'body' => [
                    'operationId' => 1,
                    'entityClass' => \stdClass::class,
                    'requestType' => 'bar',
                    'version' => 'latest',
                    'rootJobId' => 1,
                    'chunkJobNameTemplate' => 'templateName'
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "requestType" with value "bar" is expected '
                    . 'to be of type "string\[\]"/'
            ],
            'wrong version type' => [
                'body' => [
                    'operationId' => 1,
                    'entityClass' => \stdClass::class,
                    'requestType' => ['bar', 'baz'],
                    'version' => 1,
                    'rootJobId' => 1,
                    'chunkJobNameTemplate' => 'templateName'
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "version" with value 1 is expected to be of type "string"/'
            ],
            'wrong synchronousMode type' => [
                'body' => [
                    'operationId' => 1,
                    'entityClass' => \stdClass::class,
                    'requestType' => ['bar', 'baz'],
                    'version' => 'latest',
                    'synchronousMode' => 1,
                    'rootJobId' => 1,
                    'chunkJobNameTemplate' => 'templateName'
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "synchronousMode" with value 1 is expected to be of type "bool"/'
            ],
            'wrong rootJobId type' => [
                'body' => [
                    'operationId' => 1,
                    'entityClass' => \stdClass::class,
                    'requestType' => ['bar', 'baz'],
                    'version' => 'latest',
                    'rootJobId' => '1',
                    'chunkJobNameTemplate' => 100
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "rootJobId" with value "1" is expected to be of type "int"/'
            ],
            'wrong chunkJobNameTemplate type' => [
                'body' => [
                    'operationId' => 1,
                    'entityClass' => \stdClass::class,
                    'requestType' => ['bar', 'baz'],
                    'version' => 'latest',
                    'rootJobId' => 1,
                    'chunkJobNameTemplate' => 100
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "chunkJobNameTemplate" with value 100 is expected '
                    . 'to be of type "string"/'
            ],
            'wrong firstChunkFileIndex type' => [
                'body' => [
                    'operationId' => 1,
                    'entityClass' => \stdClass::class,
                    'requestType' => ['bar', 'baz'],
                    'version' => 'latest',
                    'rootJobId' => 1,
                    'chunkJobNameTemplate' => 100,
                    'firstChunkFileIndex' => '1'
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "firstChunkFileIndex" with value "1" is expected '
                    . 'to be of type "int"/'
            ],
            'wrong aggregateTime type' => [
                'body' => [
                    'operationId' => 1,
                    'entityClass' => \stdClass::class,
                    'requestType' => ['bar', 'baz'],
                    'version' => 'latest',
                    'rootJobId' => 1,
                    'chunkJobNameTemplate' => 100,
                    'aggregateTime' => '1'
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "aggregateTime" with value "1" is expected to be of type "int"/'
            ]
        ];
    }
}
