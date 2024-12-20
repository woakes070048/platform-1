<?php

namespace Oro\Bundle\SearchBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Index range of entities of specified class.
 */
class IndexEntitiesByRangeTopic extends AbstractTopic
{
    #[\Override]
    public static function getName(): string
    {
        return 'oro.search.index_entity_by_range';
    }

    #[\Override]
    public static function getDescription(): string
    {
        return 'Index range of entities of specified class';
    }

    #[\Override]
    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired(['jobId', 'entityClass', 'offset', 'limit'])
            ->addAllowedTypes('jobId', 'int')
            ->addAllowedTypes('entityClass', 'string')
            ->addAllowedTypes('offset', 'int')
            ->addAllowedTypes('limit', 'int');
    }
}
