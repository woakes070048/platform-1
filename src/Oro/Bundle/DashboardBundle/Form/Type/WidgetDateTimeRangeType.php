<?php

namespace Oro\Bundle\DashboardBundle\Form\Type;

use Oro\Bundle\FilterBundle\Form\Type\Filter\DateTimeRangeFilterType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Date time range form for dashboard widget.
 */
class WidgetDateTimeRangeType extends AbstractType
{
    const NAME = 'oro_type_widget_datetime_range';

    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['datetime_range_metadata'] = [
            'name'       => $view->vars['full_name'] . '[type]',
            'label'      => $view->vars['label'],
            'typeValues' => $view->vars['type_values'],
            'dateParts'  => $view->vars['date_parts'],
            'externalWidgetOptions'  => array_merge(
                $view->vars['widget_options'],
                ['dateVars' => $view->vars['date_vars']]
            ),
            'templateSelector'       => '#date-filter-template-wo-actions',
            'criteriaValueSelectors' => [
                'type'      => 'select',
                'date_type' => 'select[name][name!=date_part]',
                'date_part' => 'select[name=date_part]',
                'value'     => [
                    'start' => 'input[name="' . $view->vars['full_name'] . '[value][start]"]',
                    'end'   => 'input[name="' . $view->vars['full_name'] . '[value][end]"]'
                ]
            ]
        ];
    }

    #[\Override]
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['datetime_range_metadata'] = array_merge(
            $view->vars['datetime_range_metadata'],
            ['choices' => $view->children['type']->vars['choices']]
        );
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'compile_date' => false,
                'field_type'   => WidgetDateRangeValueType::class
            ]
        );
    }

    #[\Override]
    public function getParent(): ?string
    {
        return DateTimeRangeFilterType::class;
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return static::NAME;
    }
}
