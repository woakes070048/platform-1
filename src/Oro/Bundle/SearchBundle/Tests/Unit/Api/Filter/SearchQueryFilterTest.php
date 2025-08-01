<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Api\Filter;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Doctrine\Common\Collections\Expr\Value;
use Oro\Bundle\ApiBundle\Exception\InvalidFilterException;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\SearchBundle\Api\Filter\SearchFieldResolver;
use Oro\Bundle\SearchBundle\Api\Filter\SearchFieldResolverFactory;
use Oro\Bundle\SearchBundle\Api\Filter\SearchQueryFilter;
use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;
use PHPUnit\Framework\TestCase;

class SearchQueryFilterTest extends TestCase
{
    private const ENTITY_CLASS = 'Test\Entity';
    private const ENTITY_ALIAS = 'test_entity';

    private SearchQueryFilter $filter;

    #[\Override]
    protected function setUp(): void
    {
        $fieldMappings = ['field1' => 'field_1'];

        $searchMappingProvider = $this->createMock(AbstractSearchMappingProvider::class);
        $searchMappingProvider->expects(self::any())
            ->method('getEntityConfig')
            ->with(self::ENTITY_CLASS)
            ->willReturn(['alias' => self::ENTITY_ALIAS]);

        $searchFieldResolver = $this->createMock(SearchFieldResolver::class);
        $searchFieldResolver->expects(self::any())
            ->method('resolveFieldName')
            ->willReturnCallback(function ($fieldName) use ($fieldMappings) {
                if (isset($fieldMappings[$fieldName])) {
                    $fieldName = $fieldMappings[$fieldName];
                }

                return $fieldName;
            });
        $searchFieldResolver->expects(self::any())
            ->method('resolveFieldType')
            ->willReturn('text');

        $searchFieldResolverFactory = $this->createMock(SearchFieldResolverFactory::class);
        $searchFieldResolverFactory->expects(self::any())
            ->method('createFieldResolver')
            ->with(self::ENTITY_CLASS, $fieldMappings)
            ->willReturn($searchFieldResolver);

        $this->filter = new SearchQueryFilter(DataType::STRING);
        $this->filter->setSearchMappingProvider($searchMappingProvider);
        $this->filter->setSearchFieldResolverFactory($searchFieldResolverFactory);
        $this->filter->setEntityClass(self::ENTITY_CLASS);
        $this->filter->setFieldMappings($fieldMappings);
    }

    public function testValidFilter(): void
    {
        $criteria = new Criteria();
        $this->filter->apply($criteria, new FilterValue('searchQuery', 'field1 = "test"'));

        self::assertEquals(
            new Comparison('text.field_1', '=', new Value('test')),
            $criteria->getWhereExpression()
        );
    }

    public function testValidFilterWithSearchQueryCriteriaVisitor(): void
    {
        $criteria = new Criteria();
        $searchQueryCriteriaVisitor = $this->createMock(ExpressionVisitor::class);
        $this->filter->setSearchQueryCriteriaVisitor($searchQueryCriteriaVisitor);

        $searchQueryCriteriaVisitor->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(function (Comparison $expr) {
                return new Comparison($expr->getField() . '_updated', $expr->getOperator(), $expr->getValue());
            });

        $this->filter->apply($criteria, new FilterValue('searchQuery', 'field1 = "test"'));

        self::assertEquals(
            new Comparison('text.field_1_updated', '=', new Value('test')),
            $criteria->getWhereExpression()
        );
    }

    public function testInvalidFilter(): void
    {
        $this->expectException(InvalidFilterException::class);
        $this->expectExceptionMessage('Not allowed operator.');

        $criteria = new Criteria();
        $this->filter->apply($criteria, new FilterValue('searchQuery', 'field1 . "test"'));
    }

    public function testEmptyFilterValue(): void
    {
        $criteria = new Criteria();
        $this->filter->apply($criteria, new FilterValue('searchQuery', ''));

        self::assertNull($criteria->getWhereExpression());
    }

    public function testEmptyFilterValueWithSearchQueryCriteriaVisitor(): void
    {
        $criteria = new Criteria();
        $searchQueryCriteriaVisitor = $this->createMock(ExpressionVisitor::class);
        $this->filter->setSearchQueryCriteriaVisitor($searchQueryCriteriaVisitor);

        $searchQueryCriteriaVisitor->expects(self::never())
            ->method('dispatch');

        $this->filter->apply($criteria, new FilterValue('searchQuery', ''));

        self::assertNull($criteria->getWhereExpression());
    }
}
