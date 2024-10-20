<?php

namespace Oro\Bundle\FormBundle\Form\Extension;

use Oro\Bundle\FormBundle\Form\Extension\Traits\FormExtendedTypeTrait;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SortableExtension extends AbstractTypeExtension
{
    use FormExtendedTypeTrait;

    const POSITION_FIELD_NAME = '_position';

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!$options['sortable']) {
            return;
        }

        $builder->add(self::POSITION_FIELD_NAME, HiddenType::class, [
            'property_path' => $options['sortable_property_path'],
            'block_name' => 'hidden',
            'empty_data' => '0',
            'attr' => [
                'class' => 'position-input',
            ],
        ]);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'sortable' => false,
            'sortable_property_path' => 'position',
        ]);
    }
}
