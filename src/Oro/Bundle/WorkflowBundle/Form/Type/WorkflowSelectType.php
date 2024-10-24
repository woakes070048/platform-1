<?php

namespace Oro\Bundle\WorkflowBundle\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Form type provides functionality to select a Workflow.
 */
class WorkflowSelectType extends AbstractType
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /** @var TranslatorInterface */
    protected $translator;

    public function __construct(ManagerRegistry $registry, TranslatorInterface $translator)
    {
        $this->registry = $registry;
        $this->translator = $translator;
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_workflow_select';
    }

    #[\Override]
    public function getParent(): ?string
    {
        return ChoiceType::class;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'entity_class' => null,
                'config_id' => null, // can be extracted from parent form
            ]
        );

        $resolver->setNormalizer(
            'choices',
            function (Options $options, $value) {
                if (!empty($value)) {
                    return $value;
                }

                $entityClass = $options['entity_class'];
                if (!$entityClass && isset($options['config_id'])) {
                    $configId = $options['config_id'];
                    if ($configId instanceof ConfigIdInterface) {
                        $entityClass = $configId->getClassName();
                    }
                }

                $choices = [];
                if ($entityClass) {
                    /** @var WorkflowDefinition[] $definitions */
                    $definitions = $this->registry->getRepository(WorkflowDefinition::class)
                        ->findBy(['relatedEntity' => $entityClass]);

                    foreach ($definitions as $definition) {
                        $name = $definition->getName();
                        $label = $definition->getLabel();
                        $choices[$label] = $name;
                    }
                }

                return $choices;
            }
        );
    }

    #[\Override]
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        /** @var ChoiceView $choiceView */
        foreach ($view->vars['choices'] as $choiceView) {
            $choiceView->label = $this->translator->trans(
                (string) $choiceView->label,
                [],
                WorkflowTranslationHelper::TRANSLATION_DOMAIN
            );
        }
    }
}
