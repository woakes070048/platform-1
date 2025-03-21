<?php

namespace Oro\Bundle\IntegrationBundle\Provider\Rest\Client;

/**
 * Iterates rows using REST API Client
 */
abstract class AbstractRestIterator implements \Iterator
{
    /**
     * @var RestClientInterface
     */
    protected $client;

    /**
     * @var bool
     */
    protected $firstLoaded = false;

    /**
     * Results of page data
     *
     * @var array
     */
    protected $rows = array();

    /**
     * Total count of items in response
     *
     * @var int
     */
    protected $totalCount = null;

    /**
     * Offset of current item in current page
     *
     * @var int
     */
    protected $offset = -1;

    /**
     * A position of a current item within the current page
     *
     * @var int
     */
    protected $position = -1;

    /**
     * Current item, populated from request response
     *
     * @var mixed
     */
    protected $current = null;

    public function __construct(RestClientInterface $client)
    {
        $this->client = $client;
    }

    #[\Override]
    public function current(): mixed
    {
        return $this->current;
    }

    #[\Override]
    public function next(): void
    {
        $this->offset++;

        if (!isset($this->rows[$this->offset]) && !$this->loadNextPage()) {
            $this->current = null;
        } else {
            $this->current  = $this->rows[$this->offset];
        }
        $this->position++;
    }

    #[\Override]
    public function key(): int
    {
        return $this->position;
    }

    #[\Override]
    public function valid(): bool
    {
        if (!$this->firstLoaded) {
            $this->rewind();
        }

        return null !== $this->current;
    }

    #[\Override]
    public function rewind(): void
    {
        $this->firstLoaded  = false;
        $this->totalCount   = null;
        $this->offset       = -1;
        $this->position     = -1;
        $this->current      = null;
        $this->rows         = array();

        $this->next();
    }

    public function count()
    {
        if (!$this->firstLoaded) {
            $this->rewind();
        }

        return $this->totalCount;
    }

    /**
     * Attempts to load next page
     *
     * @return bool If page loaded successfully
     */
    protected function loadNextPage()
    {
        $this->rows = array();
        $this->offset = null;

        $pageData = $this->loadPage($this->client);
        $this->firstLoaded = true;
        if ($pageData) {
            $this->rows = $this->getRowsFromPageData($pageData);
            $this->totalCount = $this->getTotalCountFromPageData($pageData, $this->totalCount);
            if (null == $this->totalCount && is_array($this->rows)) {
                $this->totalCount = count($this->rows);
            }
            $this->offset = 0;
        }

        return count($this->rows) > 0 && $this->totalCount;
    }

    /**
     * Load page
     *
     * @param RestClientInterface $client
     * @return array|null
     */
    abstract protected function loadPage(RestClientInterface $client);

    /**
     * Get rows from page data
     *
     * @param array $data
     * @return array|null
     */
    abstract protected function getRowsFromPageData(array $data);

    /**
     * Get total count from page data
     *
     * @param array $data
     * @param integer $previousValue
     * @return array|null
     */
    abstract protected function getTotalCountFromPageData(array $data, $previousValue);
}
