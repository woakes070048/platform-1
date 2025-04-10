<?php

namespace Oro\Bundle\DashboardBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroDateType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class WidgetDateType
 * @package Oro\Bundle\DashboardBundle\Form\Type
 */
class WidgetDateType extends AbstractType
{
    const NAME = 'oro_type_widget_date';

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'useDate',
            CheckboxType::class,
            [
                'label'      => false,
                'required'   => false
            ]
        );

        if ($options['enable_date']) {
            $builder->add(
                'date',
                OroDateType::class,
                [
                    'required' => false,
                    'label' => false
                ]
            );
        }
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('enable_date', true);
    }
}
