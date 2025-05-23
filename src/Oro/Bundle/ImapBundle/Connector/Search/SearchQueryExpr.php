<?php

namespace Oro\Bundle\ImapBundle\Connector\Search;

/**
 * Represents the search query expression
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SearchQueryExpr implements SearchQueryExprInterface, \Iterator, \ArrayAccess
{
    /**
     * @var SearchQueryExprInterface[]
     */
    private $items;

    /** @var int The current position of the iterator */
    private $position = 0;

    public function __construct()
    {
        $this->position = 0;
    }

    public function add(SearchQueryExprInterface $item)
    {
        $this->items[] = $item;
    }

    /**
     * @param SearchQueryExprInterface[] $items
     */
    public function setItems(array $items)
    {
        $this->items = $items;
    }

    /**
     * @return SearchQueryExprInterface[]
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * Checks if this object has no any expressions.
     *
     * @return bool
     */
    public function isEmpty()
    {
        if (empty($this->items)) {
            return true;
        }
        $isEmpty = true;
        foreach ($this->items as $item) {
            if ($item instanceof SearchQueryExprValueBase) {
                $value = $item->getValue();
                $isEmpty = ($value instanceof SearchQueryExpr)
                    ? $value->isEmpty()
                    : false;
            } elseif ($item instanceof SearchQueryExpr) {
                $isEmpty = $item->isEmpty();
            } else {
                $isEmpty = false;
            }
            if (!$isEmpty) {
                break;
            }
        }

        return $isEmpty;
    }

    /**
     * Checks if this object has more than one expression.
     *
     * @return bool
     */
    public function isComplex()
    {
        if (empty($this->items)) {
            return false;
        }
        if (count($this->items) > 1) {
            return true;
        }
        $isComplex = false;
        $item = $this->items[0];
        if ($item instanceof SearchQueryExprValueBase) {
            $value = $item->getValue();
            if ($value instanceof SearchQueryExpr) {
                $isComplex = $value->isComplex();
            }
        } elseif ($item instanceof SearchQueryExpr) {
            $isComplex = $item->isComplex();
        }

        return $isComplex;
    }

    #[\Override]
    public function current(): SearchQueryExprInterface
    {
        return $this->items[$this->position];
    }

    #[\Override]
    public function next(): void
    {
        ++$this->position;
    }

    #[\Override]
    public function key(): int
    {
        return $this->position;
    }

    #[\Override]
    public function valid(): bool
    {
        return isset($this->items[$this->position]);
    }

    #[\Override]
    public function rewind(): void
    {
        $this->position = 0;
    }

    #[\Override]
    public function offsetExists($offset): bool
    {
        return isset($this->items[$offset]);
    }

    #[\Override]
    public function offsetGet($offset): SearchQueryExprInterface
    {
        return $this->items[$offset];
    }

    #[\Override]
    public function offsetSet($offset, $value): void
    {
        if (is_null($offset)) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    #[\Override]
    public function offsetUnset($offset): void
    {
        unset($this->items[$offset]);
    }
}
