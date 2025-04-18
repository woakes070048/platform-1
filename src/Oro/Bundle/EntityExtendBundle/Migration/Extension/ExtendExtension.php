<?php

namespace Oro\Bundle\EntityExtendBundle\Migration\Extension;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigBag;
use Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigContainer;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Migration\EntityMetadataHelper;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\EntityExtendBundle\Migration\Schema\ExtendTable;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\EntityExtendBundle\Validator\CustomEntityConfigValidatorService;
use Oro\Bundle\MigrationBundle\Migration\Extension\NameGeneratorAwareInterface;

/**
 * Provides an ability to create extended enum tables and fields, add relations between tables.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ExtendExtension implements NameGeneratorAwareInterface
{
    use ExtendNameGeneratorAwareTrait;

    private const array ALLOWED_IDENTITY_FIELDS = ['id', 'name'];
    private const array DEFAULT_IDENTITY_FIELDS = ['id'];
    private const string ENUM_OPTION_TABLE = 'oro_enum_option';

    public function __construct(
        protected ExtendOptionsManager $extendOptionsManager,
        protected EntityMetadataHelper $entityMetadataHelper,
        protected PropertyConfigBag $propertyConfigBag,
        protected CustomEntityConfigValidatorService $customEntityValidator,
    ) {
    }

    public function getNameGenerator(): ExtendDbIdentifierNameGenerator
    {
        return $this->nameGenerator;
    }

    /**
     * Creates a table for a custom entity.
     * The custom entity is an entity which has no PHP class in any bundle. The definition of such entity is
     * created automatically in Symfony cache
     *
     * @throws \InvalidArgumentException
     */
    public function createCustomEntityTable(
        Schema $schema,
        string $entityName,
        array $options = []
    ): Table {
        $className = ExtendHelper::ENTITY_NAMESPACE . $entityName;
        // validate custom entity configuration
        $this->customEntityValidator->checkConfigExists($className);
        $tableName = $this->nameGenerator->generateCustomEntityTableName($className);
        $table = $schema->createTable($tableName);
        $this->entityMetadataHelper->registerEntityClass($tableName, $className);

        $options = new OroOptions($options);
        // set options
        $options->setAuxiliary(ExtendOptionsManager::ENTITY_CLASS_OPTION, $className);
        if ($options->has('extend', 'owner')) {
            if ($options->get('extend', 'owner') !== ExtendScope::OWNER_CUSTOM) {
                throw new \InvalidArgumentException(
                    sprintf('The "extend.owner" option for a custom entity must be "%s".', ExtendScope::OWNER_CUSTOM)
                );
            }
        } else {
            $options->set('extend', 'owner', ExtendScope::OWNER_CUSTOM);
        }
        if ($options->has('extend', 'is_extend')) {
            if ($options->get('extend', 'is_extend') !== true) {
                throw new \InvalidArgumentException(
                    'The "extend.is_extend" option for a custom entity must be TRUE.'
                );
            }
        } else {
            $options->set('extend', 'is_extend', true);
        }
        $table->addOption(OroOptions::KEY, $options);

        // add a primary key
        $primaryKeyColumnName = $this->nameGenerator->getCustomEntityPrimaryKeyColumnName();
        $table->addColumn($primaryKeyColumnName, 'integer', ['autoincrement' => true]);
        $table->setPrimaryKey([$primaryKeyColumnName]);

        return $table;
    }

    /**
     * Creates a table that is used to store enum options for the enum with the given code.
     *
     * @param Schema $schema
     * @param array $options
     * @param array $identityFields
     *
     * @return Table A table that is used to store enum options
     *
     * @throws \InvalidArgumentException
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function createEnum(
        Schema $schema,
        array $options = [],
        array $identityFields = self::DEFAULT_IDENTITY_FIELDS
    ): Table {
        if (empty($identityFields)) {
            throw new \InvalidArgumentException('At least one identify field is required.');
        }
        if ($invalidIdentifyFields = array_diff($identityFields, self::ALLOWED_IDENTITY_FIELDS)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'The identification fields can only be: %s. Current invalid fields: %s.',
                    implode(', ', self::ALLOWED_IDENTITY_FIELDS),
                    implode(', ', $invalidIdentifyFields)
                )
            );
        }

        $tableName = self::ENUM_OPTION_TABLE;
        $options = array_replace_recursive(
            [
                ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_HIDDEN,
                ExtendOptionsManager::ENTITY_CLASS_OPTION => EnumOption::class,
                'entity' => [
                    'label' => ExtendHelper::getEnumTranslationKey('label', 'option'),
                    'plural_label' => ExtendHelper::getEnumTranslationKey('plural_label', 'option'),
                    'description' => ExtendHelper::getEnumTranslationKey('description', 'option')
                ],
                'extend' => [
                    'owner' => ExtendScope::OWNER_SYSTEM,
                    'is_extend' => true,
                    'table' => $tableName,
                    'inherit' => EnumOptionInterface::class
                ],
            ],
            $options
        );

        $table = $schema->createTable($tableName);
        $this->entityMetadataHelper->registerEntityClass($tableName, EnumOption::class);
        $table->addOption(OroOptions::KEY, $options);
        $table->addColumn(
            'id',
            'string',
            [
                'length' => ExtendHelper::MAX_ENUM_ID_LENGTH,
                OroOptions::KEY => [
                    'entity' => [
                        'label' => ExtendHelper::getEnumTranslationKey('label', fieldName: 'id'),
                        'description' => ExtendHelper::getEnumTranslationKey('description', fieldName: 'id')
                    ],
                    'importexport' => [
                        'identity' => in_array('id', $identityFields, true),
                    ],
                ]
            ]
        );
        $table->addColumn('internal_id', 'string', ['length' => ExtendHelper::MAX_ENUM_INTERNAL_ID_LENGTH]);
        $table->addColumn('enum_code', 'string', ['length' => 64]);
        $table->addColumn(
            'name',
            'string',
            [
                'length' => 255,
                OroOptions::KEY => [
                    'entity' => [
                        'label' => ExtendHelper::getEnumTranslationKey('label', fieldName: 'name'),
                        'description' => ExtendHelper::getEnumTranslationKey('description', fieldName: 'name')
                    ],
                    'datagrid' => [
                        'is_visible' => DatagridScope::IS_VISIBLE_FALSE
                    ],
                ],
            ]
        );
        $table->addColumn(
            'priority',
            'integer',
            [
                OroOptions::KEY => [
                    'entity' => [
                        'label' => ExtendHelper::getEnumTranslationKey('label', fieldName:'priority'),
                        'description' => ExtendHelper::getEnumTranslationKey('description', fieldName: 'priority')
                    ],
                    'datagrid' => [
                        'is_visible' => DatagridScope::IS_VISIBLE_FALSE
                    ]
                ]
            ]
        );
        $table->addColumn(
            'is_default',
            'boolean',
            [
                OroOptions::KEY => [
                    ExtendOptionsManager::FIELD_NAME_OPTION => 'default',
                    'entity' => [
                        'label' => ExtendHelper::getEnumTranslationKey('label', fieldName:'default'),
                        'description' => ExtendHelper::getEnumTranslationKey('description', fieldName:'default')
                    ],
                    'datagrid' => [
                        'is_visible' => DatagridScope::IS_VISIBLE_FALSE
                    ]
                ]
            ]
        );
        $table->setPrimaryKey(['id']);
        $table->addIndex(['enum_code'], 'oro_enum_code_idx');

        return $table;
    }

    /**
     * Adds enumerable field
     *
     * Take in attention that this method creates new private enum if the enum with the given code
     * is not exist yet. If you want to create a public enum use {@link createEnum} method before.
     *
     * @param Schema $schema
     * @param Table|string $table A Table object or table name
     * @param string $associationName The name of a relation field
     * @param string $enumCode The target enum identifier
     * @param bool $isMultiple Indicates whether several options can be selected for this enum
     *                                       or it supports only one selected option
     * @param bool|string[] $immutable Indicates whether the changing the list of enum values and
     *                                       public flag is allowed or not. More details can be found
     *                                       in entity_config.yml
     * @param array $options
     * @param array $identityFields
     *
     * @return Table A table that is used to store enum options
     */
    public function addEnumField(
        Schema $schema,
        Table|string $table,
        string $associationName,
        string $enumCode,
        bool $isMultiple = false,
        bool $immutable = false,
        array $options = [],
        array $identityFields = self::DEFAULT_IDENTITY_FIELDS
    ): Table {
        $enumTableName = self::ENUM_OPTION_TABLE;
        $selfTable = $this->getTable($table, $schema);
        // make sure a table that is used to store enum values exists
        $enumTable = !$schema->hasTable($enumTableName)
            ? $this->createEnum($schema, [], $identityFields)
            : $this->getTable($enumTableName, $schema);

        $options['enum']['enum_public'] = $options['enum']['enum_public'] ?? false;
        $options['enum']['immutable'] = $immutable;
        $options['enum']['enum_code'] = $enumCode;
        if ($isMultiple) {
            $options['extend']['without_default'] = true;
        }
        $options['extend']['is_extend'] = true;
        $options['extend']['is_serialized'] = true;
        if (!isset($options['extend']['owner'])) {
            $options['extend']['owner'] = ExtendScope::OWNER_SYSTEM;
        }
        if (!isset($options['extend']['bidirectional'])) {
            $options['extend']['bidirectional'] = false;
        }
        $options[ExtendOptionsManager::TYPE_OPTION] = $isMultiple ? 'multiEnum' : 'enum';

        $this->extendOptionsManager->setColumnOptions(
            $selfTable->getName(),
            $associationName,
            $options
        );

        return $enumTable;
    }

    /**
     * Adds one-to-many relation
     *
     * @param Schema $schema
     * @param Table|string $table A Table object or table name of owning side entity
     * @param string $associationName The name of a relation field
     * @param Table|string $targetTable A Table object or table name of inverse side entity
     * @param string[] $targetTitleColumnNames Column names are used to show a title of related entity
     * @param string[] $targetDetailedColumnNames Column names are used to show detailed info about related entity
     * @param string[] $targetGridColumnNames Column names are used to show related entity in a grid
     * @param array $options Entity config options. [scope => [name => value, ...], ...]
     * @param string $fieldType The field type. By default the field type is oneToMany,
     *                                                but you can specify another type if it is based on oneToMany.
     *                                                In this case this type should be registered
     *                                                in entity_extend.yml under underlying_types section
     * @throws SchemaException
     */
    public function addOneToManyRelation(
        Schema $schema,
        Table|string $table,
        string $associationName,
        Table|string $targetTable,
        array $targetTitleColumnNames,
        array $targetDetailedColumnNames,
        array $targetGridColumnNames,
        array $options = [],
        string $fieldType = RelationType::ONE_TO_MANY
    ): void {
        $this->validateOptions($options, $fieldType);
        $this->ensureExtendFieldSet($options);
        $options['extend']['bidirectional'] = true; // has to be bidirectional

        $selfTableName = $this->getTableName($table);
        $selfTable = $this->getTable($table, $schema);
        $selfClassName = $this->getEntityClassByTableName($selfTableName);

        $targetTableName = $this->getTableName($targetTable);
        $targetTable = $this->getTable($targetTable, $schema);
        $targetColumnName = $this->nameGenerator->generateOneToManyRelationColumnName(
            $selfClassName,
            $associationName,
            '_' . $this->getPrimaryKeyColumnName($selfTable)
        );

        $this->checkColumnsExist($targetTable, $targetTitleColumnNames);
        $this->checkColumnsExist($targetTable, $targetDetailedColumnNames);
        $this->checkColumnsExist($targetTable, $targetGridColumnNames);

        if (!isset($options['extend']['without_default']) || !$options['extend']['without_default']) {
            $this->addDefaultRelation($selfTable, $associationName, $targetTable);
        }

        $this->addRelation(
            $targetTable,
            $targetColumnName,
            $selfTable,
            ['notnull' => false],
            ['onDelete' => 'SET NULL']
        );

        $options[ExtendOptionsManager::TARGET_OPTION] = [
            'table_name' => $targetTableName,
            'columns' => [
                'title' => $targetTitleColumnNames,
                'detailed' => $targetDetailedColumnNames,
                'grid' => $targetGridColumnNames,
            ],
        ];
        $options[ExtendOptionsManager::TYPE_OPTION] = $fieldType;
        $this->extendOptionsManager->setColumnOptions(
            $selfTableName,
            $associationName,
            $options
        );
    }

    /**
     * Adds the inverse side of a one-to-many relation
     *
     * @param Schema $schema
     * @param Table|string $table A Table object or table name of owning side entity
     * @param string $associationName The name of a relation field
     * @param Table|string $targetTable A Table object or table name of inverse side entity
     * @param string $targetAssociationName The name of a relation field on the inverse side
     * @param string $titleColumnName A column name is used to show owning side entity
     * @param array $options Entity config options. [scope => [name => value, ...], ...]
     */
    public function addOneToManyInverseRelation(
        Schema $schema,
        Table|string $table,
        string $associationName,
        Table|string $targetTable,
        string $targetAssociationName,
        string $titleColumnName,
        array $options = []
    ): void {
        $this->ensureTargetNotHidden($table, $associationName);
        $this->ensureExtendFieldSet($options);

        $selfTableName = $this->getTableName($table);
        $selfTable = $this->getTable($table, $schema);
        $selfClassName = $this->getEntityClassByTableName($selfTableName);

        $targetTableName = $this->getTableName($targetTable);
        $targetTable = $this->getTable($targetTable, $schema);
        $targetClassName = $this->getEntityClassByTableName($targetTableName);

        $existingTargetColumnName = $this->nameGenerator->generateOneToManyRelationColumnName(
            $selfClassName,
            $associationName
        );

        $this->checkColumnsExist($selfTable, [$titleColumnName]);
        $this->checkColumnsExist($targetTable, [$existingTargetColumnName]);

        $selfRelationKey = ExtendHelper::buildRelationKey(
            $selfClassName,
            $associationName,
            RelationType::ONE_TO_MANY,
            $targetClassName
        );
        $targetRelationKey = ExtendHelper::toggleRelationKey($selfRelationKey);

        $targetFieldId = new FieldConfigId(
            'extend',
            $targetClassName,
            $targetAssociationName,
            RelationType::MANY_TO_ONE
        );

        $selfTableOptions['extend']['relation.' . $selfRelationKey . '.target_field_id'] = $targetFieldId;
        $this->extendOptionsManager->setTableOptions(
            $selfTableName,
            $selfTableOptions
        );

        $targetTableOptions['extend']['relation.' . $targetRelationKey . '.field_id'] = $targetFieldId;
        $this->extendOptionsManager->setTableOptions(
            $targetTableName,
            $targetTableOptions
        );

        $options[ExtendOptionsManager::TARGET_OPTION] = [
            'table_name' => $selfTableName,
            'relation_key' => $targetRelationKey,
            'column' => $titleColumnName,
        ];
        $options[ExtendOptionsManager::TYPE_OPTION] = RelationType::MANY_TO_ONE;
        $options['extend']['column_name'] = $existingTargetColumnName;
        $this->extendOptionsManager->setColumnOptions(
            $targetTableName,
            $targetAssociationName,
            $options
        );
    }

    /**
     * Adds many-to-many relation
     *
     * @param Schema $schema
     * @param Table|string $table A Table object or table name of owning side entity
     * @param string $associationName The name of a relation field
     * @param Table|string $targetTable A Table object or table name of inverse side entity
     * @param string[] $targetTitleColumnNames Column names are used to show a title of related entity
     * @param string[] $targetDetailedColumnNames Column names are used to show detailed info about related entity
     * @param string[] $targetGridColumnNames Column names are used to show related entity in a grid
     * @param array $options Entity config options. [scope => [name => value, ...], ...]
     * @param string $fieldType The field type. By default the field type is manyToMany,
     *                                                but you can specify another type if it is based on manyToMany.
     *                                                In this case this type should be registered
     *                                                in entity_extend.yml under underlying_types section
     * @throws SchemaException
     */
    public function addManyToManyRelation(
        Schema $schema,
        Table|string $table,
        string $associationName,
        Table|string $targetTable,
        array $targetTitleColumnNames,
        array $targetDetailedColumnNames,
        array $targetGridColumnNames,
        array $options = [],
        string $fieldType = RelationType::MANY_TO_MANY
    ): void {
        $this->validateOptions($options, $fieldType);
        $this->ensureExtendFieldSet($options);
        $selfTableName = $this->getTableName($table);
        $selfTable = $this->getTable($table, $schema);
        $targetTableName = $this->getTableName($targetTable);
        $targetTable = $this->getTable($targetTable, $schema);

        $this->checkColumnsExist($targetTable, $targetTitleColumnNames);
        $this->checkColumnsExist($targetTable, $targetDetailedColumnNames);
        $this->checkColumnsExist($targetTable, $targetGridColumnNames);

        if (!isset($options['extend']['without_default']) || !$options['extend']['without_default']) {
            $this->addDefaultRelation($selfTable, $associationName, $targetTable);
        }
        $selfIdColumn = $this->getPrimaryKeyColumnName($selfTable);
        $targetIdColumn = $this->getPrimaryKeyColumnName($targetTable);
        $selfClassName = $this->getEntityClassByTableName($selfTableName);
        $targetClassName = $this->getEntityClassByTableName($targetTableName);
        $joinTableName = $this->nameGenerator->generateManyToManyJoinTableName(
            $selfClassName,
            $associationName,
            $targetClassName
        );
        $joinTable = $schema->createTable($joinTableName);
        $selfJoinTableColumnNamePrefix = null;
        $targetJoinTableColumnNamePrefix = null;
        if ($selfClassName === $targetClassName) {
            // fix the collision of names if owning side entity equals to inverse side entity
            $selfJoinTableColumnNamePrefix = 'src_';
            $targetJoinTableColumnNamePrefix = 'dest_';
        }
        $selfJoinTableColumnName = $this->nameGenerator->generateManyToManyJoinTableColumnName(
            $selfClassName,
            '_' . $selfIdColumn,
            $selfJoinTableColumnNamePrefix
        );
        $targetJoinTableColumnName = $this->nameGenerator->generateManyToManyJoinTableColumnName(
            $targetClassName,
            '_' . $targetIdColumn,
            $targetJoinTableColumnNamePrefix
        );
        $this->addRelation(
            $joinTable,
            $selfJoinTableColumnName,
            $selfTable,
            [],
            ['onDelete' => 'CASCADE']
        );
        $this->addRelation(
            $joinTable,
            $targetJoinTableColumnName,
            $targetTable,
            [],
            ['onDelete' => 'CASCADE']
        );
        $joinTable->setPrimaryKey([$selfJoinTableColumnName, $targetJoinTableColumnName]);

        $options[ExtendOptionsManager::TARGET_OPTION] = [
            'table_name' => $targetTableName,
            'columns' => [
                'title' => $targetTitleColumnNames,
                'detailed' => $targetDetailedColumnNames,
                'grid' => $targetGridColumnNames,
            ],
        ];
        $options[ExtendOptionsManager::TYPE_OPTION] = $fieldType;
        $this->extendOptionsManager->setColumnOptions(
            $selfTableName,
            $associationName,
            $options
        );
    }

    /**
     * Adds the inverse side of a many-to-many relation
     *
     * @param Schema $schema
     * @param Table|string $table A Table object or table name of owning side entity
     * @param string $associationName The name of a relation field
     * @param Table|string $targetTable A Table object or table name of inverse side entity
     * @param string $targetAssociationName The name of a relation field on the inverse side
     * @param string[] $titleColumnNames Column names are used to show a title of owning side entity
     * @param string[] $detailedColumnNames Column names are used to show detailed info about owning side entity
     * @param string[] $gridColumnNames Column names are used to show owning side entity in a grid
     * @param array $options Entity config options. [scope => [name => value, ...], ...]
     */
    public function addManyToManyInverseRelation(
        Schema $schema,
        Table|string $table,
        string $associationName,
        Table|string $targetTable,
        string $targetAssociationName,
        array $titleColumnNames,
        array $detailedColumnNames,
        array $gridColumnNames,
        array $options = []
    ): void {
        $this->ensureTargetNotHidden($table, $associationName);
        $this->ensureExtendFieldSet($options);

        $selfTableName = $this->getTableName($table);
        $selfTable = $this->getTable($table, $schema);
        $selfClassName = $this->getEntityClassByTableName($selfTableName);

        $targetTableName = $this->getTableName($targetTable);
        $targetClassName = $this->getEntityClassByTableName($targetTableName);

        $this->checkColumnsExist($selfTable, $titleColumnNames);
        $this->checkColumnsExist($selfTable, $detailedColumnNames);
        $this->checkColumnsExist($selfTable, $gridColumnNames);

        $selfRelationKey = ExtendHelper::buildRelationKey(
            $selfClassName,
            $associationName,
            RelationType::MANY_TO_MANY,
            $targetClassName
        );
        $targetRelationKey = ExtendHelper::toggleRelationKey($selfRelationKey);

        $targetFieldId = new FieldConfigId(
            'extend',
            $targetClassName,
            $targetAssociationName,
            RelationType::MANY_TO_MANY
        );

        $selfTableOptions['extend']['relation.' . $selfRelationKey . '.target_field_id'] = $targetFieldId;
        $this->extendOptionsManager->setTableOptions(
            $selfTableName,
            $selfTableOptions
        );

        $this->extendOptionsManager->mergeColumnOptions(
            $selfTableName,
            $associationName,
            ['extend' => ['bidirectional' => true]]
        );

        $targetTableOptions['extend']['relation.' . $targetRelationKey . '.field_id'] = $targetFieldId;
        $this->extendOptionsManager->setTableOptions(
            $targetTableName,
            $targetTableOptions
        );

        $options[ExtendOptionsManager::TARGET_OPTION] = [
            'table_name' => $selfTableName,
            'relation_key' => $targetRelationKey,
            'columns' => [
                'title' => $titleColumnNames,
                'detailed' => $detailedColumnNames,
                'grid' => $gridColumnNames,
            ],
        ];
        $options[ExtendOptionsManager::TYPE_OPTION] = RelationType::MANY_TO_MANY;
        $this->extendOptionsManager->setColumnOptions(
            $targetTableName,
            $targetAssociationName,
            $options
        );
    }

    /**
     * Adds many-to-one relation
     *
     * @param Schema $schema
     * @param Table|string $table A Table object or table name of owning side entity
     * @param string $associationName The name of a relation field
     * @param Table|string $targetTable A Table object or table name of inverse side entity
     * @param string $targetTitleColumnName A column name is used to show related entity
     * @param array $options Entity config options. [scope => [name => value, ...], ...]
     * @param string $fieldType The field type. By default the field type is manyToOne,
     *                                            but you can specify another type if it is based on manyToOne.
     *                                            In this case this type should be registered
     *                                            in entity_extend.yml under underlying_types section
     * @throws SchemaException
     */
    public function addManyToOneRelation(
        Schema $schema,
        Table|string $table,
        string $associationName,
        Table|string $targetTable,
        string $targetTitleColumnName,
        array $options = [],
        string $fieldType = RelationType::MANY_TO_ONE
    ): void {
        $this->validateOptions($options, $fieldType);
        $this->ensureExtendFieldSet($options);

        $selfTableName = $this->getTableName($table);
        $selfTable = $this->getTable($table, $schema);
        $targetTableName = $this->getTableName($targetTable);
        $targetTable = $this->getTable($targetTable, $schema);
        $primaryKeyColumnName = $this->getPrimaryKeyColumnName($targetTable);
        $selfColumnName = $this->nameGenerator->generateRelationColumnName(
            $associationName,
            '_' . $primaryKeyColumnName
        );

        $this->checkColumnsExist($targetTable, [$targetTitleColumnName]);

        $relation = $options['extend'];

        if (array_key_exists('nullable', $relation)) {
            $notnull = !$relation['nullable'];
        } else {
            $notnull = false;
        }

        $this->addRelation(
            $selfTable,
            $selfColumnName,
            $targetTable,
            ['notnull' => $notnull],
            ['onDelete' => $this->getOnDeleteAction($relation)]
        );

        $options[ExtendOptionsManager::TARGET_OPTION] = [
            'table_name' => $targetTableName,
            'column' => $targetTitleColumnName,
        ];
        $options[ExtendOptionsManager::TYPE_OPTION] = $fieldType;
        $this->extendOptionsManager->setColumnOptions(
            $selfTableName,
            $associationName,
            $options
        );
    }

    /**
     * Adds the inverse side of a many-to-one relation
     *
     * @param Schema $schema
     * @param Table|string $table A Table object or table name of owning side entity
     * @param string $associationName The name of a relation field. This field can't be hidden
     * @param Table|string $targetTable A Table object or table name of inverse side entity
     * @param string $targetAssociationName The name of a relation field on the inverse side
     * @param string[] $titleColumnNames Column names are used to show a title of owning side entity
     * @param string[] $detailedColumnNames Column names are used to show detailed info about owning side entity
     * @param string[] $gridColumnNames Column names are used to show owning side entity in a grid
     * @param array $options Entity config options. [scope => [name => value, ...], ...]
     */
    public function addManyToOneInverseRelation(
        Schema $schema,
        Table|string $table,
        string $associationName,
        Table|string $targetTable,
        string $targetAssociationName,
        array $titleColumnNames,
        array $detailedColumnNames,
        array $gridColumnNames,
        array $options = []
    ): void {
        $this->ensureTargetNotHidden($table, $associationName);
        $this->ensureExtendFieldSet($options);

        $selfTableName = $this->getTableName($table);
        $selfTable = $this->getTable($table, $schema);
        $selfClassName = $this->getEntityClassByTableName($selfTableName);

        $targetTableName = $this->getTableName($targetTable);
        $targetClassName = $this->getEntityClassByTableName($targetTableName);

        $this->checkColumnsExist($selfTable, $titleColumnNames);
        $this->checkColumnsExist($selfTable, $detailedColumnNames);
        $this->checkColumnsExist($selfTable, $gridColumnNames);

        $selfRelationKey = ExtendHelper::buildRelationKey(
            $selfClassName,
            $associationName,
            RelationType::MANY_TO_ONE,
            $targetClassName
        );
        $targetRelationKey = ExtendHelper::toggleRelationKey($selfRelationKey);

        $targetFieldId = new FieldConfigId(
            'extend',
            $targetClassName,
            $targetAssociationName,
            RelationType::ONE_TO_MANY
        );

        $selfTableOptions['extend']['relation.' . $selfRelationKey . '.target_field_id'] = $targetFieldId;
        $selfTableOptions['extend']['relation.' . $selfRelationKey . '.on_delete']
            = $this->getOnDeleteAction($options['extend']);
        $this->extendOptionsManager->setTableOptions(
            $selfTableName,
            $selfTableOptions
        );

        $this->extendOptionsManager->mergeColumnOptions(
            $selfTableName,
            $associationName,
            ['extend' => ['bidirectional' => true]]
        );

        $targetTableOptions['extend']['relation.' . $targetRelationKey . '.field_id'] = $targetFieldId;
        if (isset($options['extend']['orphanRemoval'])) {
            $targetTableOptions['extend']['relation.' . $targetRelationKey . '.orphanRemoval']
                = $options['extend']['orphanRemoval'];
        }

        $this->extendOptionsManager->setTableOptions(
            $targetTableName,
            $targetTableOptions
        );

        $options[ExtendOptionsManager::TARGET_OPTION] = [
            'table_name' => $selfTableName,
            'relation_key' => $targetRelationKey,
            'columns' => [
                'title' => $titleColumnNames,
                'detailed' => $detailedColumnNames,
                'grid' => $gridColumnNames,
            ],
        ];
        $options[ExtendOptionsManager::TYPE_OPTION] = RelationType::ONE_TO_MANY;
        $this->extendOptionsManager->setColumnOptions(
            $targetTableName,
            $targetAssociationName,
            $options
        );
    }

    /**
     * Gets an entity full class name by a table name
     */
    public function getEntityClassByTableName(string $tableName): ?string
    {
        $classes = $this->entityMetadataHelper->getEntityClassesByTableName($tableName);

        if (count($classes) > 1) {
            throw new \RuntimeException(sprintf(
                'Table "%s" has more than 1 class. This is not supported by ExtendExtension',
                $tableName
            ));
        }

        return reset($classes) ?: null;
    }

    public function getEntityClassesByTableName(string $tableName): array
    {
        return $this->entityMetadataHelper->getEntityClassesByTableName($tableName);
    }

    /**
     * Gets a table name by entity full class name
     */
    public function getTableNameByEntityClass(string $className): ?string
    {
        return $this->entityMetadataHelper->getTableNameByEntityClass($className);
    }

    protected function getTableName(Table|string $table): string
    {
        return $table instanceof Table ? $table->getName() : $table;
    }

    protected function getTable(Table|string $table, Schema $schema): Table
    {
        return $table instanceof Table ? $table : $schema->getTable($table);
    }

    protected function checkColumnsExist(Table $table, array $columnNames): void
    {
        if (empty($columnNames)) {
            throw new \InvalidArgumentException('At least one column must be specified.');
        }
        foreach ($columnNames as $columnName) {
            $table->getColumn($columnName);
        }
    }

    protected function getPrimaryKeyColumnName(Table $table): string
    {
        if (!$table->hasPrimaryKey()) {
            throw new SchemaException(
                sprintf('The table "%s" must have a primary key.', $table->getName())
            );
        }
        $primaryKeyColumns = $table->getPrimaryKey()->getColumns();
        if (count($primaryKeyColumns) !== 1) {
            throw new SchemaException(
                sprintf('A primary key of "%s" table must include only one column.', $table->getName())
            );
        }

        return array_pop($primaryKeyColumns);
    }

    protected function addRelationColumn(
        Table $table,
        string $columnName,
        Column $targetColumn,
        array $options = []
    ): void {
        $columnTypeName = $targetColumn->getType()->getName();
        if (!in_array($columnTypeName, [Types::INTEGER, Types::STRING, Types::SMALLINT, Types::BIGINT], true)) {
            throw new SchemaException(
                sprintf(
                    'The type of relation column "%s::%s" must be an integer or string. "%s" type is not supported.',
                    $table->getName(),
                    $columnName,
                    $columnTypeName
                )
            );
        }

        if ($columnTypeName === Types::STRING && $targetColumn->getLength() !== null) {
            $options['length'] = $targetColumn->getLength();
        }

        $table->addColumn($columnName, $columnTypeName, $options);
    }

    protected function addRelation(
        Table $table,
        string $columnName,
        Table $targetTable,
        array $columnOptions = [],
        array $foreignKeyOptions = []
    ): void {
        $targetPrimaryKeyColumnName = $this->getPrimaryKeyColumnName($targetTable);
        $targetPrimaryKeyColumn = $targetTable->getColumn($targetPrimaryKeyColumnName);
        $this->addRelationColumn($table, $columnName, $targetPrimaryKeyColumn, $columnOptions);
        $table->addIndex([$columnName]);
        $table->addForeignKeyConstraint(
            $targetTable,
            [$columnName],
            [$targetPrimaryKeyColumnName],
            $foreignKeyOptions
        );
    }

    protected function addDefaultRelation(Table $table, string $associationName, Table $targetTable): void
    {
        $targetPrimaryKeyColumnName = $this->getPrimaryKeyColumnName($targetTable);
        $defaultRelationColumnName = $this->nameGenerator->generateRelationDefaultColumnName(
            $associationName,
            '_' . $targetPrimaryKeyColumnName
        );
        $targetPrimaryKeyColumn = $targetTable->getColumn($targetPrimaryKeyColumnName);
        $this->addRelationColumn($table, $defaultRelationColumnName, $targetPrimaryKeyColumn, ['notnull' => false]);
        $table->addIndex([$defaultRelationColumnName]);
        $table->addForeignKeyConstraint(
            $targetTable,
            [$defaultRelationColumnName],
            [$targetPrimaryKeyColumnName],
            ['onDelete' => 'SET NULL']
        );
    }

    /**
     * Makes sure that required for any extend field attributes are set
     */
    protected function ensureExtendFieldSet(array &$options): void
    {
        if (!isset($options['extend'])) {
            $options['extend'] = [];
        }
        if (!isset($options['extend']['is_extend'])) {
            $options['extend']['is_extend'] = true;
        }
        if (!isset($options['extend']['owner'])) {
            $options['extend']['owner'] = ExtendScope::OWNER_SYSTEM;
        }
        if (!isset($options['extend']['bidirectional'])) {
            $options['extend']['bidirectional'] = false;
        }
        if (!isset($options[ExtendOptionsManager::MODE_OPTION])) {
            $options[ExtendOptionsManager::MODE_OPTION] = ConfigModel::MODE_READONLY;
        }
    }

    private function validateOptions(array $options, string $fieldType): void
    {
        foreach ($options as $scope => $scopeOptions) {
            /** @var PropertyConfigContainer $scopeConfig */
            $scopeConfig = $this->propertyConfigBag->getPropertyConfig($scope);

            if (!is_array($scopeOptions) || count($scopeConfig->getConfig()) === 0) {
                continue;
            }

            foreach ($scopeOptions as $optionName => $optionValue) {
                if (!isset($scopeConfig->getConfig()['field']['items'][$optionName]['options']['allowed_type'])) {
                    continue;
                }

                $allowedTypes = $scopeConfig->getConfig()['field']['items'][$optionName]['options']['allowed_type'];

                if (!in_array($fieldType, $allowedTypes)) {
                    throw new \UnexpectedValueException(sprintf(
                        'Option `%s|%s` is not allowed for field type `%s`. Allowed types [%s]',
                        $scope,
                        $optionName,
                        $fieldType,
                        implode(', ', $allowedTypes)
                    ));
                }
            }
        }
    }

    private function ensureTargetNotHidden(string|ExtendTable $table, string $associationName): void
    {
        $options = $this->extendOptionsManager->getExtendOptions();
        $tableName = $this->getTableName($table);
        $keyName = $tableName . '!' . $associationName;
        if (!empty($options[$keyName][ExtendOptionsManager::MODE_OPTION])
            && $options[$keyName][ExtendOptionsManager::MODE_OPTION] === ConfigModel::MODE_HIDDEN) {
            throw new \InvalidArgumentException('Target field can\'t be hidden.');
        }
    }

    private function getOnDeleteAction(array $relation): mixed
    {
        if (array_key_exists('on_delete', $relation)) {
            return $relation['on_delete'];
        }

        return 'SET NULL';
    }
}
