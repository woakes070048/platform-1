<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Locator;

use Oro\Bundle\MigrationBundle\Locator\FixturePathLocator;
use PHPUnit\Framework\TestCase;

class FixturePathLocatorTest extends TestCase
{
    /**
     * @param string $type
     * @param string $expected
     *
     * @dataProvider pathDataProvider
     */
    public function testGetPath($type, $expected): void
    {
        $locator = new FixturePathLocator();

        $result = $locator->getPath($type);

        $this->assertEquals($result, $expected);
    }

    public function pathDataProvider(): array
    {
        return [
            'demo type'          => [
                'demo',
                'Migrations/Data/Demo/ORM',
            ],
            'main type'          => [
                'main',
                'Migrations/Data/ORM',
            ],
            'empty type'         => [
                '',
                'Migrations/Data/ORM',
            ],
            'not presented type' => [
                'someType',
                'Migrations/Data/ORM',
            ],
        ];
    }
}
