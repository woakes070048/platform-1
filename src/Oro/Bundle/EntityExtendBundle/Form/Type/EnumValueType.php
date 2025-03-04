<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints as ExtendAssert;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * EnumValue form type
 */
class EnumValueType extends AbstractType
{
    /** @var ConfigProvider */
    protected $configProvider;

    public function __construct(ConfigProvider $configProvider)
    {
        $this->configProvider = $configProvider;
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('id', HiddenType::class)
            ->add('label', TextType::class, [
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['max' => 255]),
                ],
            ])
            ->add('priority', HiddenType::class, ['empty_data' => 9999]);

        if ($options['allow_multiple_selection']) {
            $builder->add('is_default', CheckboxType::class, ['required' => false]);
        } else {
            $builder->add('is_default', RadioType::class, ['required' => false]);
        }
    }

    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['allow_delete'] = $this->isDeletable($form);
    }

    /**
     * Checks that the option can be removed
     *
     * @param FormInterface $form
     * @return boolean
     */
    protected function isDeletable(FormInterface $form)
    {
        $data = $form->getData();

        if (!$data) {
            return true;
        }

        /** @var FieldConfigId $configId */
        $configId = $form->getParent()->getConfig()->getOption('config_id');
        if (!$configId instanceof FieldConfigId) {
            return true;
        }

        if (!$this->configProvider->hasConfigById($configId)) {
            return true;
        }
        $configById = $this->configProvider->getConfigById($configId);
        $enumCode = $configById->get('enum_code');
        if (empty($enumCode) || !$configById->has('immutable_codes')) {
            return true;
        }

        return !in_array($data['id'], $configById->get('immutable_codes', false, []));
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'constraints' => [
                new ExtendAssert\EnumOption(),
            ],
            'allow_multiple_selection' => true
        ]);
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_entity_extend_enum_value';
    }
}
