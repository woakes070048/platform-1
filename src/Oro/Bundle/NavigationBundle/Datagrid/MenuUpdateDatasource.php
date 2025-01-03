<?php

namespace Oro\Bundle\NavigationBundle\Datagrid;

use Knp\Menu\Util\MenuManipulator;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\NavigationBundle\Configuration\ConfigurationProvider;
use Oro\Bundle\NavigationBundle\Provider\BuilderChainProvider;

/**
 * The datasource for datagrid that is used to update menu.
 */
class MenuUpdateDatasource implements DatasourceInterface
{
    /** @var BuilderChainProvider */
    protected $chainProvider;

    /** @var MenuManipulator */
    protected $menuManipulator;

    /** @var string */
    protected $scopeType;

    /** @var ConfigurationProvider */
    protected $configurationProvider;

    /**
     * @param BuilderChainProvider  $chainProvider
     * @param MenuManipulator       $menuManipulator
     * @param string                $scopeType
     * @param ConfigurationProvider $configurationProvider
     */
    public function __construct(
        BuilderChainProvider $chainProvider,
        MenuManipulator $menuManipulator,
        $scopeType,
        ConfigurationProvider $configurationProvider
    ) {
        $this->chainProvider = $chainProvider;
        $this->menuManipulator = $menuManipulator;
        $this->scopeType = $scopeType;
        $this->configurationProvider = $configurationProvider;
    }

    #[\Override]
    public function process(DatagridInterface $grid, array $config)
    {
        $datasource = clone $this;
        if (isset($config['scope_type'])) {
            $datasource->scopeType = $config['scope_type'];
        }
        $grid->setDatasource($datasource);
    }

    /**
     * @return array
     */
    #[\Override]
    public function getResults()
    {
        $rows = [];

        $tree = $this->configurationProvider->getMenuTree();
        foreach ($tree as $name => $item) {
            $menuItem = $this->chainProvider->get($name);
            if ($menuItem->getExtra('scope_type') === $this->scopeType && !$menuItem->getExtra('read_only')) {
                $rows[] = new ResultRecord($this->menuManipulator->toArray($menuItem));
            }
        }

        return $rows;
    }
}
