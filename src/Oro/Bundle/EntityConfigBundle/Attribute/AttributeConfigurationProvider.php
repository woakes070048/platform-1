<?php

namespace Oro\Bundle\EntityConfigBundle\Attribute;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

/**
 * Provides a set of method to simplify working with attribute configuration data.
 */
class AttributeConfigurationProvider implements AttributeConfigurationProviderInterface
{
    /** @var ConfigManager */
    protected $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @param FieldConfigModel $attribute
     *
     * @return string
     */
    #[\Override]
    public function getAttributeLabel(FieldConfigModel $attribute)
    {
        return $this->getConfig($attribute, 'entity')->get('label', false, $attribute->getFieldName());
    }

    /**
     * @param FieldConfigModel $attribute
     *
     * @return bool
     */
    #[\Override]
    public function isAttributeActive(FieldConfigModel $attribute)
    {
        return $this->getConfig($attribute, 'extend')
            ->in('state', [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_UPDATE]);
    }

    /**
     * @param FieldConfigModel $attribute
     *
     * @return bool
     */
    #[\Override]
    public function isAttributeCustom(FieldConfigModel $attribute)
    {
        return $this->getConfig($attribute, 'extend')
            ->is('owner', ExtendScope::OWNER_CUSTOM);
    }

    /**
     * @param FieldConfigModel $attribute
     *
     * @return bool
     */
    #[\Override]
    public function isAttributeSearchable(FieldConfigModel $attribute)
    {
        return $this->getConfig($attribute)->is('searchable');
    }

    /**
     * @param FieldConfigModel $attribute
     *
     * @return bool
     */
    #[\Override]
    public function isAttributeFilterable(FieldConfigModel $attribute)
    {
        return $this->getConfig($attribute)->is('filterable');
    }

    /**
     * @param FieldConfigModel $attribute
     *
     * @return bool
     */
    #[\Override]
    public function isAttributeFilterByExactValue(FieldConfigModel $attribute)
    {
        return $this->getConfig($attribute)->is('filter_by', 'exact_value');
    }

    /**
     * @param FieldConfigModel $attribute
     *
     * @return bool
     */
    #[\Override]
    public function isAttributeSortable(FieldConfigModel $attribute)
    {
        return $this->getConfig($attribute)->is('sortable');
    }

    /**
     * @param FieldConfigModel $attribute
     * @param string $scope
     *
     * @return ConfigInterface
     */
    protected function getConfig(FieldConfigModel $attribute, $scope = 'attribute')
    {
        $className = $attribute->getEntity()->getClassName();
        $fieldName = $attribute->getFieldName();

        return $this->configManager->getProvider($scope)->getConfig($className, $fieldName);
    }
}
