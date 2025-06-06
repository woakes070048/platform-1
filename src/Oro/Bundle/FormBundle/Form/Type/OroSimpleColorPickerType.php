<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * SimpleColorPicker form type
 */
class OroSimpleColorPickerType extends AbstractSimpleColorPickerType
{
    /** @var TranslatorInterface */
    protected $translator;

    public function __construct(ConfigManager $configManager, TranslatorInterface $translator)
    {
        parent::__construct($configManager);
        $this->translator = $translator;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver
            ->setDefaults(
                [
                    'colors'               => [],
                    'empty_value'          => null,
                    'allow_custom_color'   => false,
                    'show_input_control'   => false,
                    'custom_color_control' => null // hue, brightness, saturation, or wheel. defaults wheel
                ]
            )
            ->setNormalizer(
                'colors',
                function (Options $options, $colors) {
                    return $options['color_schema'] === 'custom'
                        ? $colors
                        : $this->getColors($options['color_schema']);
                }
            );
    }

    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);

        $pickerData = $this->convertColorsToPickerData($options['colors'], $options['translatable']);

        if ($options['allow_empty_color']) {
            array_unshift(
                $pickerData,
                [
                    'id'    => $options['empty_color'],
                    'text'  => $this->translator->trans((string) $options['empty_value']),
                    'class' => 'empty-color'
                ],
                []
            );
        }

        $view->vars['allow_custom_color'] = $options['allow_custom_color'];
        $view->vars['configs']['show_input_control'] = $options['show_input_control'];

        if ($options['allow_custom_color']) {
            $this->appendTheme($view->vars['configs'], 'with-custom-color');
            array_push(
                $pickerData,
                [],
                [
                    'id'    => null,
                    'text'  => $this->translator->trans('oro.form.color.custom'),
                    'class' => 'custom-color'
                ]
            );

            $view->vars['configs']['custom_color'] = [];
            if ($options['custom_color_control']) {
                $view->vars['configs']['custom_color']['control'] = $options['custom_color_control'];
            }
        }

        $view->vars['configs']['data'] = $pickerData;
    }

    #[\Override]
    public function getParent(): ?string
    {
        return HiddenType::class;
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_simple_color_picker';
    }

    /**
     * @param array $colors
     * @param bool  $translatable
     *
     * @return array
     */
    protected function convertColorsToPickerData($colors, $translatable)
    {
        $data = [];
        foreach ($colors as $clr => $text) {
            $data[] = [
                'id'   => $clr,
                'text' => $translatable ? $this->translator->trans($text) : $text
            ];
        }

        return $data;
    }
}
