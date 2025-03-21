<?php

namespace Oro\Bundle\ApiBundle\Util;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Doctrine\Common\Collections\Expr\Value;

/**
 * Walks an expression graph and collects fields are used in all comparison expressions.
 */
class FieldVisitor extends ExpressionVisitor
{
    private array $fields = [];

    /**
     * Gets all fields are used in a visited expression graph.
     *
     * @return string[]
     */
    public function getFields(): array
    {
        return array_keys($this->fields);
    }

    #[\Override]
    public function walkComparison(Comparison $comparison)
    {
        $field = $comparison->getField();
        if (!isset($this->fields[$field])) {
            $this->fields[$field] = true;
        }
    }

    #[\Override]
    public function walkValue(Value $value)
    {
    }

    #[\Override]
    public function walkCompositeExpression(CompositeExpression $expr)
    {
        $expressionList = $expr->getExpressionList();
        foreach ($expressionList as $child) {
            $this->dispatch($child);
        }
    }
}
