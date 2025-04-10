<?php

namespace Oro\Bundle\DataGridBundle\Event;

use Doctrine\ORM\AbstractQuery;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Symfony\Contracts\EventDispatcher\Event;

class OrmResultAfter extends Event implements GridEventInterface
{
    const NAME = 'oro_datagrid.orm_datasource.result.after';

    /**
     * @var DatagridInterface
     */
    protected $datagrid;

    /**
     * @var ResultRecordInterface[]
     */
    protected $records;

    /**
     * @var AbstractQuery
     */
    protected $query;

    public function __construct(
        DatagridInterface $datagrid,
        array $records = [],
        ?AbstractQuery $query = null
    ) {
        $this->datagrid = $datagrid;
        $this->records  = $records;
        $this->query    = $query;
    }

    #[\Override]
    public function getDatagrid()
    {
        return $this->datagrid;
    }

    /**
     * @return ResultRecordInterface[]
     */
    public function getRecords()
    {
        return $this->records;
    }

    public function setRecords(array $records)
    {
        $this->records = $records;
    }

    /**
     * @return AbstractQuery
     */
    public function getQuery()
    {
        return $this->query;
    }
}
