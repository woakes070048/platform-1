<?php

namespace Oro\Bundle\SoapBundle\Tests\Unit\Event;

use Doctrine\Common\Collections\Criteria;
use Oro\Bundle\SoapBundle\Event\GetListBefore;
use PHPUnit\Framework\TestCase;

class GetListBeforeTest extends TestCase
{
    public function testEventClass(): void
    {
        $criteria = new Criteria();
        $testClassName = 'Oro\TestBundle\TestClass';
        $event = new GetListBefore($criteria, $testClassName);
        $this->assertSame($criteria, $event->getCriteria());
        $anotherCriteria = new Criteria();
        $event->setCriteria($anotherCriteria);
        $this->assertSame($anotherCriteria, $event->getCriteria());
        $this->assertEquals($testClassName, $event->getClassName());
    }
}
