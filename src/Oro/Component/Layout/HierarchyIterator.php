<?php

namespace Oro\Component\Layout;

/**
 * Represents an iterator which can be used to get child elements of a hierarchy
 * The iteration is performed from parent to child
 */
class HierarchyIterator implements \Iterator
{
    /** @var mixed */
    protected $id;

    /** @var \RecursiveIteratorIterator */
    protected $iterator;

    /**
     * @param mixed $id
     * @param array $children
     */
    public function __construct($id, array $children)
    {
        $this->id = $id;

        $this->iterator = new \RecursiveIteratorIterator(
            new \RecursiveArrayIterator($children),
            \RecursiveIteratorIterator::SELF_FIRST
        );
    }

    /**
     * Return the parent element
     *
     * @return mixed
     */
    public function getParent()
    {
        $depth = $this->iterator->getDepth();

        return $depth === 0
            ? $this->id
            : $this->iterator->getSubIterator($depth - 1)->key();
    }

    #[\Override]
    public function current(): mixed
    {
        return $this->iterator->key();
    }

    #[\Override]
    public function key(): mixed
    {
        return $this->iterator->key();
    }

    #[\Override]
    public function valid(): bool
    {
        return $this->iterator->valid();
    }

    #[\Override]
    public function next(): void
    {
        $this->iterator->next();
    }

    #[\Override]
    public function rewind(): void
    {
        $this->iterator->rewind();
    }
}
