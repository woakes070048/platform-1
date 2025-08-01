<?php

declare(strict_types=1);

namespace Oro\Bundle\MigrationBundle\Tests\Functional\Migration;

use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @group demo-fixtures
 */
class LoadDataFixturesTest extends WebTestCase
{
    use MessageQueueExtension;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
    }

    public function testLoadDemoDataFixtures(): void
    {
        // for manual execution needs reset autoincrements, like that ALTER TABLE `<table_name>` AUTO_INCREMENT=2
        self::runCommand('oro:migration:data:load', ['--fixtures-type=demo', '-vvv'], true, true);

        $messages = [];
        foreach (self::getSentMessages() as $message) {
            $topic = $message['topic'];
            $messages[$topic][] = $message;
        }

        foreach ($messages as $topic => $items) {
            $messages[$topic] = sprintf('Topic: %s, messages: %d', $topic, count($items));
        }

        $this->assertSame([], array_values($messages), 'Message queue must be empty');
    }
}
