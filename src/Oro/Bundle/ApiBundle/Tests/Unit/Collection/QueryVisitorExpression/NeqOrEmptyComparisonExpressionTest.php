<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Collection\QueryVisitorExpression;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Collection\QueryExpressionVisitor;
use Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression\NeqOrEmptyComparisonExpression;
use Oro\Bundle\ApiBundle\Model\Range;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Bundle\ApiBundle\Tests\Unit\OrmRelatedTestCase;
use Oro\Bundle\ApiBundle\Tests\Unit\Stub\FieldDqlExpressionProviderStub;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;

class NeqOrEmptyComparisonExpressionTest extends OrmRelatedTestCase
{
    public function testWalkComparisonExpressionForNullValue(): void
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('The value for "e.test" must not be NULL.');

        $expression = new NeqOrEmptyComparisonExpression();
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            [],
            new FieldDqlExpressionProviderStub(),
            $this->createMock(EntityClassResolver::class)
        );
        $field = 'e.test';
        $expr = $field;
        $parameterName = 'test_1';
        $value = null;

        $expression->walkComparisonExpression(
            $expressionVisitor,
            $field,
            $expr,
            $parameterName,
            $value
        );
    }

    public function testWalkComparisonExpression(): void
    {
        $expression = new NeqOrEmptyComparisonExpression();
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            [],
            new FieldDqlExpressionProviderStub(),
            new EntityClassResolver($this->doctrine)
        );
        $field = 'e.groups';
        $expr = $field;
        $parameterName = 'groups_1';
        $value = 'text';

        $qb = new QueryBuilder($this->em);
        $qb
            ->select('e')
            ->from(Entity\User::class, 'e')
            ->innerJoin('e.groups', 'groups');

        $expressionVisitor->setQuery($qb);
        $expressionVisitor->setQueryAliases(['e', 'groups']);
        $expressionVisitor->setQueryJoinMap(['groups' => 'groups']);

        $result = $expression->walkComparisonExpression(
            $expressionVisitor,
            $field,
            $expr,
            $parameterName,
            $value
        );

        $expectedSubquery = 'SELECT groups_subquery1'
            . ' FROM Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group groups_subquery1'
            . ' WHERE groups_subquery1 = groups AND groups_subquery1 IN(:groups_1)';

        self::assertEquals(
            new Expr\Func('NOT', [new Expr\Func('EXISTS', [$expectedSubquery])]),
            $result
        );
        self::assertEquals(
            [new Parameter($parameterName, $value)],
            $expressionVisitor->getParameters()
        );
    }

    public function testWalkComparisonExpressionForRangeValue(): void
    {
        $expression = new NeqOrEmptyComparisonExpression();
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            [],
            new FieldDqlExpressionProviderStub(),
            new EntityClassResolver($this->doctrine)
        );
        $field = 'e.groups';
        $expr = $field;
        $parameterName = 'groups_1';
        $fromValue = 123;
        $toValue = 234;
        $value = new Range($fromValue, $toValue);

        $qb = new QueryBuilder($this->em);
        $qb
            ->select('e')
            ->from(Entity\User::class, 'e')
            ->innerJoin('e.groups', 'groups');

        $expressionVisitor->setQuery($qb);
        $expressionVisitor->setQueryAliases(['e', 'groups']);
        $expressionVisitor->setQueryJoinMap(['groups' => 'groups']);

        $result = $expression->walkComparisonExpression(
            $expressionVisitor,
            $field,
            $expr,
            $parameterName,
            $value
        );

        $expectedSubquery = 'SELECT groups_subquery1'
            . ' FROM Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group groups_subquery1'
            . ' WHERE groups_subquery1 = groups'
            . ' AND (groups_subquery1 BETWEEN :groups_1_from AND :groups_1_to)';

        self::assertEquals(
            new Expr\Func('NOT', [new Expr\Func('EXISTS', [$expectedSubquery])]),
            $result
        );
        self::assertEquals(
            [
                new Parameter('groups_1_from', $fromValue),
                new Parameter('groups_1_to', $toValue)
            ],
            $expressionVisitor->getParameters()
        );
    }

    public function testWalkComparisonExpressionWhenLastElementInPathIsField(): void
    {
        $expression = new NeqOrEmptyComparisonExpression();
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            [],
            new FieldDqlExpressionProviderStub(),
            new EntityClassResolver($this->doctrine)
        );
        $field = 'e.groups.name';
        $expr = $field;
        $parameterName = 'groups_1';
        $value = 'text';

        $qb = new QueryBuilder($this->em);
        $qb
            ->select('e')
            ->from(Entity\User::class, 'e')
            ->innerJoin('e.groups', 'groups');

        $expressionVisitor->setQuery($qb);
        $expressionVisitor->setQueryAliases(['e', 'groups']);
        $expressionVisitor->setQueryJoinMap(['groups' => 'groups']);

        $result = $expression->walkComparisonExpression(
            $expressionVisitor,
            $field,
            $expr,
            $parameterName,
            $value
        );

        $expectedSubquery = 'SELECT groups_subquery1'
            . ' FROM Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group groups_subquery1'
            . ' WHERE groups_subquery1 = groups AND groups_subquery1.name IN(:groups_1)';

        self::assertEquals(
            new Expr\Func('NOT', [new Expr\Func('EXISTS', [$expectedSubquery])]),
            $result
        );
        self::assertEquals(
            [new Parameter($parameterName, $value)],
            $expressionVisitor->getParameters()
        );
    }

    public function testWalkComparisonExpressionForRangeValueWhenLastElementInPathIsField(): void
    {
        $expression = new NeqOrEmptyComparisonExpression();
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            [],
            new FieldDqlExpressionProviderStub(),
            new EntityClassResolver($this->doctrine)
        );
        $field = 'e.groups.name';
        $expr = $field;
        $parameterName = 'groups_1';
        $fromValue = 123;
        $toValue = 234;
        $value = new Range($fromValue, $toValue);

        $qb = new QueryBuilder($this->em);
        $qb
            ->select('e')
            ->from(Entity\User::class, 'e')
            ->innerJoin('e.groups', 'groups');

        $expressionVisitor->setQuery($qb);
        $expressionVisitor->setQueryAliases(['e', 'groups']);
        $expressionVisitor->setQueryJoinMap(['groups' => 'groups']);

        $result = $expression->walkComparisonExpression(
            $expressionVisitor,
            $field,
            $expr,
            $parameterName,
            $value
        );

        $expectedSubquery = 'SELECT groups_subquery1'
            . ' FROM Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group groups_subquery1'
            . ' WHERE groups_subquery1 = groups'
            . ' AND (groups_subquery1.name BETWEEN :groups_1_from AND :groups_1_to)';

        self::assertEquals(
            new Expr\Func('NOT', [new Expr\Func('EXISTS', [$expectedSubquery])]),
            $result
        );
        self::assertEquals(
            [
                new Parameter('groups_1_from', $fromValue),
                new Parameter('groups_1_to', $toValue)
            ],
            $expressionVisitor->getParameters()
        );
    }

    public function testWalkComparisonExpressionForCustomExpression(): void
    {
        $expression = new NeqOrEmptyComparisonExpression();
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            [],
            new FieldDqlExpressionProviderStub(),
            new EntityClassResolver($this->doctrine)
        );
        $field = 'e.groups';
        $expr = sprintf('e.id = {entity:%s}.id', Entity\Group::class);
        $parameterName = 'groups_1';
        $value = 'text';

        $qb = new QueryBuilder($this->em);
        $qb
            ->select('e')
            ->from(Entity\User::class, 'e');

        $expressionVisitor->setQuery($qb);
        $expressionVisitor->setQueryAliases(['e']);
        $expressionVisitor->setQueryJoinMap([]);

        $result = $expression->walkComparisonExpression(
            $expressionVisitor,
            $field,
            $expr,
            $parameterName,
            $value
        );

        $expectedSubquery = 'SELECT groups_subquery1'
            . ' FROM Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group groups_subquery1'
            . ' WHERE e.id = groups_subquery1.id AND groups_subquery1 IN(:groups_1)';

        self::assertEquals(
            new Expr\Func('NOT', [new Expr\Func('EXISTS', [$expectedSubquery])]),
            $result
        );
        self::assertEquals(
            [new Parameter($parameterName, $value)],
            $expressionVisitor->getParameters()
        );
    }

    public function testWalkComparisonExpressionForCustomExpressionWhenAssociationAlreadyJoined(): void
    {
        $expression = new NeqOrEmptyComparisonExpression();
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            [],
            new FieldDqlExpressionProviderStub(),
            new EntityClassResolver($this->doctrine)
        );
        $field = 'e.groups';
        $expr = sprintf('e.id = {entity:%s}.id', Entity\Group::class);
        $parameterName = 'groups_1';
        $value = 'text';

        $qb = new QueryBuilder($this->em);
        $qb
            ->select('e')
            ->from(Entity\User::class, 'e')
            ->innerJoin('e.groups', 'groups');

        $expressionVisitor->setQuery($qb);
        $expressionVisitor->setQueryAliases(['e', 'groups']);
        $expressionVisitor->setQueryJoinMap(['groups' => 'groups']);

        $result = $expression->walkComparisonExpression(
            $expressionVisitor,
            $field,
            $expr,
            $parameterName,
            $value
        );

        $expectedSubquery = 'SELECT groups_subquery1'
            . ' FROM Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group groups_subquery1'
            . ' WHERE groups_subquery1 = groups AND groups_subquery1 IN(:groups_1)';

        self::assertEquals(
            new Expr\Func('NOT', [new Expr\Func('EXISTS', [$expectedSubquery])]),
            $result
        );
        self::assertEquals(
            [new Parameter($parameterName, $value)],
            $expressionVisitor->getParameters()
        );
    }
}
