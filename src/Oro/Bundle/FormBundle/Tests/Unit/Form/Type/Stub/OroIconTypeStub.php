<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type\Stub;

use Oro\Bundle\FormBundle\Form\Type\Select2ChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OroIconTypeStub extends AbstractType
{
    /**
     * @var KernelInterface
     */
    protected $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'placeholder' => 'oro.form.choose_value',
                'choices' => ['fa-anchor' => 'fa-anchor'],
                'placeholder' => '',
                'configs' => [
                    'placeholder' => 'oro.form.choose_value',
                    'result_template_twig' => '@OroForm/Autocomplete/icon/result.html.twig',
                    'selection_template_twig' => '@OroForm/Autocomplete/icon/selection.html.twig',
                ]
            ]
        );
    }

    /**
     * @return string
     */
    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_icon_select';
    }

    #[\Override]
    public function getParent(): ?string
    {
        return Select2ChoiceType::class;
    }
}
