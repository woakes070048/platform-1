<?php

namespace Oro\Bundle\ConfigBundle\Form\Type;

use Oro\Bundle\AttachmentBundle\Form\Type\FileType;
use Oro\Bundle\ConfigBundle\Form\DataTransformer\ConfigFileDataTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType as ComponentFileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConfigFileType extends AbstractType
{
    /**
     * @var ConfigFileDataTransformer
     */
    private $transformer;

    public function __construct(ConfigFileDataTransformer $transformer)
    {
        $this->transformer = $transformer;
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'file',
                ComponentFileType::class,
                [
                    'constraints' => $options['fileConstraints']
                ]
            )
            ->add(
                'emptyFile',
                HiddenType::class,
                [
                    'required' => false,
                ]
            );

        $this->transformer->setFileConstraints($builder->get('file')->getOption('constraints'));
        $builder->addModelTransformer($this->transformer);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'addEventSubscriber' => false,
            'fileConstraints' => [],
            'allowDelete' => true
        ]);
    }

    #[\Override]
    public function getParent(): ?string
    {
        return FileType::class;
    }
}
