<?php

namespace Oro\Bundle\ImportExportBundle\Async\Topic;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Topic for importing a file.
 */
class ImportTopic extends AbstractImportTopic
{
    public const NAME = 'oro.importexport.import';

    #[\Override]
    public static function getName(): string
    {
        return self::NAME;
    }

    #[\Override]
    public static function getDescription(): string
    {
        return 'Imports a file';
    }

    #[\Override]
    public function configureMessageBody(OptionsResolver $resolver): void
    {
        parent::configureMessageBody($resolver);

        $resolver
            ->setDefined([
                'jobId',
                'attempts'
            ])
            ->setRequired([
                'jobId',
            ])
            ->addAllowedTypes('jobId', 'int')
            ->addAllowedTypes('attempts', 'int');
    }
}
