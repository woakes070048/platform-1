<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\DependencyInjection\Compiler\Mock;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Subscribed topic to test BuildTopicMetaRegistryPass
 */
class SubscribedTopic extends AbstractTopic
{
    #[\Override]
    public static function getName(): string
    {
        return 'subscribed_topic_name';
    }

    #[\Override]
    public static function getDescription(): string
    {
        return 'Subscribed topic to test BuildTopicMetaRegistryPass.';
    }

    #[\Override]
    public function configureMessageBody(OptionsResolver $resolver): void
    {
    }
}
