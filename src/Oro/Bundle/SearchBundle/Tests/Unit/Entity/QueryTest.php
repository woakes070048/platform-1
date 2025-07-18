<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Entity;

use Oro\Bundle\SearchBundle\Entity\Query;
use PHPUnit\Framework\TestCase;

class QueryTest extends TestCase
{
    private Query $query;

    #[\Override]
    protected function setUp(): void
    {
        $this->query = new Query();
    }

    public function testEntityId(): void
    {
        $this->assertNull($this->query->getEntity());
        $this->query->setEntity('test_entity');
        $this->assertEquals('test_entity', $this->query->getEntity());
    }

    public function testQuery(): void
    {
        $this->assertNull($this->query->getQuery());
        $this->query->setQuery('test_query');
        $this->assertEquals('test_query', $this->query->getQuery());
    }

    public function testResultCount(): void
    {
        $this->assertNull($this->query->getResultCount());
        $this->query->setResultCount(10);
        $this->assertEquals(10, $this->query->getResultCount());
    }

    public function testCreatedAt(): void
    {
        $this->assertNull($this->query->getCreatedAt());
        $this->query->setCreatedAt(new \DateTime('2013-01-01'));
        $this->assertEquals('2013-01-01', $this->query->getCreatedAt()->format('Y-m-d'));
    }

    public function testBeforeSave(): void
    {
        $this->assertNull($this->query->getCreatedAt());
        $this->query->beforeSave();
        $currentDate = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->assertEquals($currentDate->format('Y-m-d'), $this->query->getCreatedAt()->format('Y-m-d'));
    }

    public function testGetId(): void
    {
        $this->assertNull($this->query->getId());
    }
}
