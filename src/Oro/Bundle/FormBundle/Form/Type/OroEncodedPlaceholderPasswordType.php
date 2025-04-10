<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * This class provides ability to encrypt/decrypt form fields
 *
 * This form type supports placeholder for entered password.
 * Placeholder("*") will be used to indicate that password has been entered
 */
class OroEncodedPlaceholderPasswordType extends AbstractType
{
    /** @internal */
    const PASSWORD_PLACEHOLDER = '*';

    /**
     * @var SymmetricCrypterInterface
     */
    private $crypter;

    public function __construct(SymmetricCrypterInterface $crypter)
    {
        $this->crypter = $crypter;
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) {
                $form = $event->getForm();
                $password = $event->getData();
                $oldPassword = $form->getData();

                if ($password === $this->getPlaceholder($password)) {
                    $actualPassword = $oldPassword;
                } else {
                    $actualPassword = $this->crypter->encryptData($password);
                }

                $event->setData($actualPassword);
            }
        );
    }

    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $password = $form->getData();

        if ($password) {
            $password = $this->crypter->decryptData($password);
        }

        $view->vars['value'] = $this->getPlaceholder($password);

        if (false === $options['browser_autocomplete']) {
            $view->vars['attr']['autocomplete'] = 'new-password';
        }
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(['browser_autocomplete' => false]);

        $resolver->setAllowedTypes('browser_autocomplete', 'bool');
    }

    #[\Override]
    public function getParent(): ?string
    {
        return PasswordType::class;
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_encoded_placeholder_password';
    }

    /**
     * @param string $data
     *
     * @return string
     */
    private function getPlaceholder($data)
    {
        return str_repeat(self::PASSWORD_PLACEHOLDER, strlen((string)$data));
    }
}
