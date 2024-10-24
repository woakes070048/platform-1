<?php

namespace Oro\Bundle\SearchBundle\Datagrid\Extension;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\TwigTemplateProperty;
use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Security\Acl\Util\ClassUtils;

/**
 * Property formatter for datagrid based on search mapping configuration
 */
class SearchResultProperty extends TwigTemplateProperty
{
    /**  @var SearchMappingProvider */
    protected $mappingProvider;

    public function setMappingProvider(SearchMappingProvider $mappingProvider)
    {
        $this->mappingProvider = $mappingProvider;
    }

    #[\Override]
    public function getValue(ResultRecordInterface $record)
    {
        $entity = $record->getValue('entity');
        $entityClass = ClassUtils::getRealClass($entity);

        if (!$this->mappingProvider->isClassSupported($entityClass)) {
            throw new InvalidConfigurationException(
                sprintf('Unknown entity type %s, unable to find search configuration', $entityClass)
            );
        } else {
            $searchTemplate = $this->mappingProvider->getMappingConfig()[$entityClass]['search_template'];
        }

        if (!$this->params->offsetGetOr('template', false)) {
            $this->params->offsetSet('template', $searchTemplate);
        }

        return $this->environment->render(
            $this->get(self::TEMPLATE_KEY),
            [
                'indexer_item' => $record->getValue('indexer_item'),
                'entity'       => $record->getValue('entity'),
            ]
        );
    }
}
