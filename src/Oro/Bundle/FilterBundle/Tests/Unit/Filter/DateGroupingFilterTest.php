<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Filter;

use Doctrine\ORM\Query\Expr\Select;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\FilterBundle\Filter\DateGroupingFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;

class DateGroupingFilterTest extends TestCase
{
    private FormFactoryInterface&MockObject $formFactory;
    private ManagerRegistry&MockObject $doctrine;
    private DateGroupingFilter $filter;

    #[\Override]
    protected function setUp(): void
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->filter = new DateGroupingFilter(
            $this->formFactory,
            new FilterUtility(),
            $this->doctrine
        );
    }

    public function testApplyOrderByWhenNoAdded(): void
    {
        $datasource = $this->createMock(OrmDatasource::class);
        $sortKey = 'someKey';
        $direction = 'DESC';

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $datasource->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($queryBuilder);
        $queryBuilder->expects($this->any())
            ->method('getDQLPart')
            ->with('select')
            ->willReturn([]);

        $queryBuilder->expects($this->once())
            ->method('addOrderBy')
            ->with($sortKey, $direction);
        $this->filter->init('someFilterName', [
            DateGroupingFilter::COLUMN_NAME => 'someColumn',
            FilterUtility::DATA_NAME_KEY => 'someData'
        ]);
        $this->filter->applyOrderBy($datasource, $sortKey, $direction);
    }

    public function testApplyOrderBy(): void
    {
        $datasource = $this->createMock(OrmDatasource::class);
        $sortKey = 'someKey';
        $direction = 'DESC';

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $datasource->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($queryBuilder);
        $queryBuilder->expects($this->any())
            ->method('getDQLPart')
            ->with('select')
            ->willReturn([new Select(['year(someData)']), new Select('day(someData) as someColumnDay')]);

        $queryBuilder->expects($this->exactly(2))
            ->method('addOrderBy')
            ->withConsecutive(
                ['someColumnYear', $direction],
                ['someColumnDay', $direction]
            );
        $this->filter->init('anyFilterName', [
            DateGroupingFilter::COLUMN_NAME => 'someColumn',
            FilterUtility::DATA_NAME_KEY => 'someData'
        ]);
        $this->filter->applyOrderBy($datasource, $sortKey, $direction);
    }
}
