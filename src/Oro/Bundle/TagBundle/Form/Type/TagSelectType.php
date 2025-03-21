<?php

namespace Oro\Bundle\TagBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroJquerySelect2HiddenType;
use Oro\Bundle\TagBundle\Form\EventSubscriber\TagSubscriber;
use Oro\Bundle\TagBundle\Form\Transformer\TagTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Form type for Tag select.
 */
class TagSelectType extends AbstractType
{
    /** @var TagSubscriber */
    protected $subscriber;

    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var TagTransformer */
    protected $tagTransformer;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        TagTransformer $tagTransformer,
        TagSubscriber $subscriber
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->tagTransformer = $tagTransformer;
        $this->subscriber = $subscriber;
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->resetViewTransformers();
        $builder->addViewTransformer($this->tagTransformer);
        $builder->addEventSubscriber($this->subscriber);
    }

    #[\Override]
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['component_options']['oro_tag_create_granted'] =
            $this->authorizationChecker->isGranted('oro_tag_create');
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'required'           => false,
                'configs'            => [
                    'placeholder'             => 'oro.tag.form.choose_or_create_tag',
                    'component'               => 'multi-autocomplete',
                    'multiple'                => true,
                    'result_template_twig'    => '@OroTag/Tag/Autocomplete/result.html.twig',
                    'selection_template_twig' => '@OroTag/Tag/Autocomplete/selection.html.twig',
                    'properties'              => ['id', 'name'],
                    'separator'               => ';;',
                ],
                'autocomplete_alias' => 'tags'
            ]
        );
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_tag_select';
    }

    #[\Override]
    public function getParent(): ?string
    {
        return OroJquerySelect2HiddenType::class;
    }
}
