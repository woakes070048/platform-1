<?php

namespace Oro\Bundle\EntityExtendBundle\Migration\Schema;

use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\MigrationBundle\Migration\Schema\TableWithNameGenerator;

/**
 * Adds handling of extended options to the Table class that is used in migrations.
 */
class ExtendTable extends TableWithNameGenerator
{
    const COLUMN_CLASS = 'Oro\Bundle\EntityExtendBundle\Migration\Schema\ExtendColumn';

    /**
     * @var ExtendOptionsManager
     */
    protected $extendOptionsManager;

    public function __construct(array $args)
    {
        $this->extendOptionsManager = $args['extendOptionsManager'];

        parent::__construct($args);
    }

    #[\Override]
    protected function createColumnObject(array $args)
    {
        $args['tableName'] = $this->getName();
        $args['extendOptionsManager'] = $this->extendOptionsManager;

        return parent::createColumnObject($args);
    }

    /**
     * @param string $name
     * @param mixed $value Can be scalar type, array or OroOptions object
     *
     * @return \Doctrine\DBAL\Schema\Table
     */
    #[\Override]
    public function addOption($name, $value)
    {
        if ($name === OroOptions::KEY) {
            if ($value instanceof OroOptions) {
                $value = $value->toArray();
            }
            $this->extendOptionsManager->setTableOptions($this->getName(), $value);

            return $this;
        }

        return parent::addOption($name, $value);
    }

    public function addExtendColumnOption(
        string $assocationColumn,
        string $scope,
        string $propertyPath,
        array $optionValue
    ): self {
        $existendOptions = [];
        $extendColumnOptions = $this->extendOptionsManager->getColumnOptions($this->getName(), $assocationColumn);
        if (isset($extendColumnOptions[$scope][$propertyPath])) {
            $existendOptions = $extendColumnOptions[$scope][$propertyPath];
        }
        $appendOptions = [
            $scope => [
                $propertyPath => array_merge($existendOptions, $optionValue)
            ]
        ];
        $this->extendOptionsManager->setColumnOptions($this->getName(), $assocationColumn, $appendOptions);

        return $this;
    }

    #[\Override]
    public function addColumn($columnName, $typeName, array $options = [])
    {
        $oroOptions = null;
        if (isset($options[OroOptions::KEY])) {
            $oroOptions = $options[OroOptions::KEY];
            if ($oroOptions instanceof OroOptions) {
                $oroOptions = $oroOptions->toArray();
            }
            unset($options[OroOptions::KEY]);
        }

        if (null !== $oroOptions && isset($oroOptions['extend'])) {
            if (!isset($oroOptions['extend']['is_extend'])) {
                $oroOptions['extend']['is_extend'] = true;
            }
            $options['notnull'] = $options['notnull'] ?? false;
        }

        $column = parent::addColumn($columnName, $typeName, $options);

        if (null !== $oroOptions) {
            if (empty($oroOptions[ExtendOptionsManager::TYPE_OPTION])) {
                $oroOptions[ExtendOptionsManager::TYPE_OPTION] = $column->getType()->getName();
            }

            $options[OroOptions::KEY] = $oroOptions;
            $column->setOptions($options);
        }

        return $column;
    }

    #[\Override]
    public function dropColumn($columnName)
    {
        $this->extendOptionsManager->removeColumnOptions($this->getName(), $columnName);

        return parent::dropColumn($columnName);
    }
}
