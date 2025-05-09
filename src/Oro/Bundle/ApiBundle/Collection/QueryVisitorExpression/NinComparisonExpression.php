<?php

namespace Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression;

use Oro\Bundle\ApiBundle\Collection\QueryExpressionVisitor;

/**
 * Represents NOT IN comparison expression.
 */
class NinComparisonExpression implements ComparisonExpressionInterface
{
    #[\Override]
    public function walkComparisonExpression(
        QueryExpressionVisitor $visitor,
        string $field,
        string $expression,
        string $parameterName,
        mixed $value
    ): mixed {
        $visitor->addParameter($parameterName, $value);

        return $visitor->getExpressionBuilder()
            ->notIn($expression, $visitor->buildParameterExpression($parameterName, $value));
    }
}
