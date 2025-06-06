<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Util;

use Oro\Bundle\ImapBundle\Util\DateTimeParser;
use PHPUnit\Framework\TestCase;

class DateTimeParserTest extends TestCase
{
    /**
     * @dataProvider parseProvider
     */
    public function testParse(string $strDate, string $expectedDate = '2011-06-30 23:59:59 UTC'): void
    {
        $this->assertEquals(
            new \DateTime($expectedDate),
            DateTimeParser::parse($strDate)
        );
    }

    public function parseProvider(): array
    {
        return [
            ['Thu, 30 Jun 2011 23:59:59'],
            ['Thu, 30 Jun 2011 23:59:59 0000'],
            ['Thu, 30 Jun 2011 23:59:59 GMT'],
            ['Thu, 30 Jun 2011 23:59:59 UTC'],
            ['Thu, 30 Jun 2011 23:59:59 UT'],
            ['Thu, 30 Jun 2011 23:59:59 UT (Universal Time)'],
            ['Thu, 30 Jun 2011 12:59:59 -1100'],
            ['Fri, 31 Jun 2011 10:59:59 +1100'],
            ['Fri, 31 Jun 2011 10:59:59 +1100 (AEDT)'],
            ['Fri, 31 Jun 2011 10:59:59 +1100 (GMT+11:00)'],
            ['Fri, 31 Jun 2011 10:59:59 +1100 (Australian Eastern Daylight Time)'],
            ['Fri, 31 Jun 2011 10:59:59 +11:00 (AEDT)'],
            ['Fri, 31 Jun 2011 10:59:59 +11:00 (GMT+11:00)'],
            ['Fri, 31 Jun 2011 10:59:59 +11:00 (Australian Eastern Daylight Time)'],
            ['Thu, 30 06 2011 23:59:59'],
            ['Fri, 31 06 2011 10:59:59 +1100'],
            ['Fri, 31 06 2011 10:59:59 +1100 (GMT+11:00)'],
            ['Sum, 30 Jun 2011 21:59:59 -0200'],
            ['Fri,  31 Jun 2011 10:59:59 +1100'],
            ['Fri, 31 Jun 2011 10:59:59 +11: 0'],
            ['Fri, 31 Jun 2011 10:59:59 +11:'],
            ['Fri, 31 Jun 2011 10:59:59 +11: UTC'],
            ['Fri, 31 Jun 2011 10:59: 9 +1100', '2011-06-30 23:59:09 UTC'],
            ['Fri, 31 Jun 2011 10: 9: 9 +1100', '2011-06-30 23:09:09 UTC'],
            ['Fri, 31 Jun 2011  1: 9: 9 +1100', '2011-06-30 14:09:09 UTC'],
            ['Fri, 31 Jun 2011  1:09:09 +1100', '2011-06-30 14:09:09 UTC'],
            ['Fri, 31 Jun 2011  1: 9:09 +1100', '2011-06-30 14:09:09 UTC'],
            ['Tue,=2008=20Mar=202016=2021:59:59=20+0200?=', '2016-03-08 19:59:59 UTC'],
            ['=20Tue,=2008=20Mar=202016=2021:59:59=20+020?=', '2016-03-08 19:59:59 UTC'],
        ];
    }

    /**
     * @dataProvider parseFailureProvider
     */
    public function testParseFailure(string $strDate, string $exceptionMessage): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($exceptionMessage);
        DateTimeParser::parse($strDate);
    }

    public function parseFailureProvider(): array
    {
        return [
            [
                'invalid',
                'Failed to parse time string "invalid" at position 0: The timezone could not be found in the database.'
            ],
            [
                'Fri, 31 June 2011 10:59:aa +1100',
                'Failed to parse time string "Fri, 31 June 2011 10:59:aa +1100" at position 23: Unexpected character.'
            ],
            [
                'Fri, 31 06 2011 10:59:aa +1100',
                'Failed to parse time string "Fri, 31 06 2011 10:59:aa +1100" at position 5: Unexpected character.'
            ],
            [
                'Sum, 15/06/2012 20:39:26 -0200',
                'Failed to parse time string "Sum, 15/06/2012 20:39:26 -0200" at position 0:'
                . ' The timezone could not be found in the database.'
            ],
            [
                '0000-01-01 00:00:00',
                'Invalid date. Original value: "0000-01-01 00:00:00".'
            ],
            [
                'year-01-01 00:00:00',
                'Failed to parse time string "year-01-01 00:00:00 UTC" at position 0: The timezone could not be found '
                . 'in the database. Original value: "year-01-01 00:00:00".',
            ],
            [
                '2019-99-01 00:00:00',
                'Failed to parse time string "2019-99-01 00:00:00 UTC" at position 6: Unexpected character. Original '
                . 'value: "2019-99-01 00:00:00".'
            ],
            [
                '2019-01-99 00:00:00',
                'Failed to parse time string "2019-01-99 00:00:00 UTC" at position 9: Unexpected character. Original '
                . 'value: "2019-01-99 00:00:00".'
            ],
        ];
    }
}
