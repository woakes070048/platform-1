<?php

namespace Oro\Bundle\EntityBundle\Fallback\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Exception\Fallback\FallbackFieldConfigurationMissingException;

/**
 * Entity fallback provider which fetches data from system config.
 */
class SystemConfigFallbackProvider extends AbstractEntityFallbackProvider
{
    public const CONFIG_NAME_KEY = 'configName';
    public const FALLBACK_ID = 'systemConfig';

    public function __construct(
        protected ConfigManager $configManager
    ) {
    }

    /**
     * @throws FallbackFieldConfigurationMissingException
     */
    #[\Override]
    public function getFallbackHolderEntity($object, $objectFieldName): mixed
    {
        $fallbackConfig = $this->getEntityConfig($object, $objectFieldName);

        if (!array_key_exists(EntityFieldFallbackValue::FALLBACK_LIST, $fallbackConfig)) {
            throw new FallbackFieldConfigurationMissingException(
                sprintf(
                    "You must define the fallback configuration '%s' for the class '%s' field '%s'",
                    EntityFieldFallbackValue::FALLBACK_LIST,
                    get_class($object),
                    $objectFieldName
                )
            );
        }

        $fallbackListConfig = $fallbackConfig[EntityFieldFallbackValue::FALLBACK_LIST];
        if (!array_key_exists(self::FALLBACK_ID, $fallbackListConfig)) {
            throw new FallbackFieldConfigurationMissingException(
                sprintf(
                    "You must define the fallback id configuration '%s' for the class '%s' field '%s'",
                    self::FALLBACK_ID,
                    get_class($object),
                    $objectFieldName
                )
            );
        }

        $systemConfig = $fallbackListConfig[self::FALLBACK_ID];
        if (!array_key_exists(self::CONFIG_NAME_KEY, $systemConfig)) {
            throw new FallbackFieldConfigurationMissingException(
                sprintf(
                    "You must define the '%s' fallback option for entity '%s' field '%s', fallback id '%s'",
                    self::CONFIG_NAME_KEY,
                    get_class($object),
                    $objectFieldName,
                    self::FALLBACK_ID
                )
            );
        }

        return $this->configManager->get($systemConfig[self::CONFIG_NAME_KEY]);
    }

    #[\Override]
    public function getFallbackLabel(): string
    {
        return 'oro.entity.fallback.system_config.label';
    }

    #[\Override]
    public function getFallbackEntityClass(): ?string
    {
        return null;
    }
}
