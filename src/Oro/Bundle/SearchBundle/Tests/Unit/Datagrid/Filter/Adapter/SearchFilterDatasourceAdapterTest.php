<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Datagrid\Filter\Adapter;

use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;
use Oro\Bundle\SearchBundle\Datagrid\Filter\Adapter\SearchFilterDatasourceAdapter;
use Oro\Bundle\SearchBundle\Datagrid\Filter\SearchStringFilter;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SearchFilterDatasourceAdapterTest extends TestCase
{
    private SearchQueryInterface&MockObject $searchQuery;

    #[\Override]
    protected function setUp(): void
    {
        $this->searchQuery = $this->createMock(SearchQueryInterface::class);
    }

    public function testAddRestrictionAndCondition(): void
    {
        $restriction = Criteria::expr()->eq('foo', 'bar');

        $criteria = $this->createMock(Criteria::class);
        $criteria->expects($this->once())
            ->method('andWhere')
            ->with($restriction);

        $query = $this->createMock(Query::class);
        $query->expects($this->once())
            ->method('getCriteria')
            ->willReturn($criteria);

        $this->searchQuery->expects($this->any())
            ->method('getQuery')
            ->willReturn($query);

        $adapter = new SearchFilterDatasourceAdapter($this->searchQuery);
        $adapter->addRestriction($restriction, FilterUtility::CONDITION_AND);
    }

    public function testAddRestrictionOrCondition(): void
    {
        $restriction = Criteria::expr()->eq('foo', 'bar');

        $criteria = $this->createMock(Criteria::class);
        $criteria->expects($this->once())
            ->method('orWhere')
            ->with($restriction);

        $query = $this->createMock(Query::class);
        $query->expects($this->once())
            ->method('getCriteria')
            ->willReturn($criteria);

        $this->searchQuery->expects($this->any())
            ->method('getQuery')
            ->willReturn($query);

        $adapter = new SearchFilterDatasourceAdapter($this->searchQuery);
        $adapter->addRestriction($restriction, FilterUtility::CONDITION_OR);
    }

    public function testAddRestrictionInvalidCondition(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Restriction not supported.');

        $adapter = new SearchFilterDatasourceAdapter($this->searchQuery);
        $adapter->addRestriction(Criteria::expr()->eq('foo', 'bar'), 'invalid_condition');
    }

    public function testAddRestrictionInvalidRestriction(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Restriction not supported.');

        $adapter = new SearchFilterDatasourceAdapter($this->searchQuery);
        $adapter->addRestriction('foo = bar', FilterUtility::CONDITION_OR);
    }

    public function testInApplyFromStringFilter(): void
    {
        $criteria = $this->createMock(Criteria::class);
        $criteria->expects($this->once())
            ->method('andWhere')
            ->with(Criteria::expr()->contains('foo', 'bar'));

        $query = $this->createMock(Query::class);
        $query->expects($this->once())
            ->method('getCriteria')
            ->willReturn($criteria);

        $this->searchQuery->expects($this->any())
            ->method('getQuery')
            ->willReturn($query);

        $formFactory = $this->createMock(FormFactoryInterface::class);

        $stringFilter = new SearchStringFilter($formFactory, new FilterUtility());
        $stringFilter->init('test', [
            FilterUtility::DATA_NAME_KEY => 'foo',
            FilterUtility::MIN_LENGTH_KEY => 0,
            FilterUtility::MAX_LENGTH_KEY => 100,
            FilterUtility::FORCE_LIKE_KEY => false,
        ]);

        $ds = new SearchFilterDatasourceAdapter($this->searchQuery);
        $stringFilter->apply($ds, ['type' => TextFilterType::TYPE_CONTAINS, 'value' => 'bar']);
    }

    public function testGroupBy(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Method currently not supported.');

        $ds = new SearchFilterDatasourceAdapter($this->searchQuery);
        $ds->groupBy('name');
    }

    public function testAddGroupBy(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Method currently not supported.');

        $ds = new SearchFilterDatasourceAdapter($this->searchQuery);
        $ds->addGroupBy('name');
    }

    public function testSetParameter(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Method currently not supported.');

        $ds = new SearchFilterDatasourceAdapter($this->searchQuery);
        $ds->setParameter('key', 'value');
    }

    public function testGetFieldByAlias(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Method currently not supported.');

        $ds = new SearchFilterDatasourceAdapter($this->searchQuery);
        $ds->getFieldByAlias('name');
    }

    public function testGetWrappedSearchQueryNotInitialized(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Query not initialized properly');

        $ds = new SearchFilterDatasourceAdapter($this->searchQuery);
        $ds->getWrappedSearchQuery();
    }

    public function testGetWrappedSearchQuery(): void
    {
        $query = $this->createMock(Query::class);
        $this->searchQuery->expects($this->any())
            ->method('getQuery')
            ->willReturn($query);

        $ds = new SearchFilterDatasourceAdapter($this->searchQuery);
        $this->assertEquals($ds->getWrappedSearchQuery(), $query);
    }

    public function testExpr(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Use Criteria::expr() instead.');

        $ds = new SearchFilterDatasourceAdapter($this->searchQuery);
        $ds->expr();
    }

    public function testGetDatabasePlatform(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Method currently not supported.');

        $ds = new SearchFilterDatasourceAdapter($this->searchQuery);
        $ds->getDatabasePlatform();
    }

    public function testGenerateParameterName(): void
    {
        $name = 'testName';

        $ds = new SearchFilterDatasourceAdapter($this->searchQuery);
        $this->assertEquals($name, $ds->generateParameterName($name));
    }

    public function testGetQuery(): void
    {
        $ds = new SearchFilterDatasourceAdapter($this->searchQuery);
        $this->assertEquals($this->searchQuery, $ds->getSearchQuery());
    }
}
