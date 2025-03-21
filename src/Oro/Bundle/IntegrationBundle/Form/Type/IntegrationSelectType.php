<?php

namespace Oro\Bundle\IntegrationBundle\Form\Type;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\FormBundle\Form\Type\Select2ChoiceType;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Form\Choice\Loader;
use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Symfony\Bridge\Doctrine\Form\ChoiceList\DoctrineChoiceLoader;
use Symfony\Component\Asset\Packages as AssetHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for Integration select.
 */
class IntegrationSelectType extends AbstractType
{
    const NAME = 'oro_integration_select';

    /** @var EntityManager */
    protected $em;

    /** @var TypesRegistry */
    protected $typesRegistry;

    /** @var AssetHelper */
    protected $assetHelper;

    /** @var AclHelper */
    protected $aclHelper;

    public function __construct(
        EntityManager $em,
        TypesRegistry $typesRegistry,
        AssetHelper $assetHelper,
        AclHelper $aclHelper
    ) {
        $this->em            = $em;
        $this->typesRegistry = $typesRegistry;
        $this->assetHelper   = $assetHelper;
        $this->aclHelper     = $aclHelper;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $em             = $this->em;
        $defaultConfigs = [
            'placeholder'             => 'oro.form.choose_value',
            'result_template_twig'    => '@OroIntegration/Autocomplete/integration/result.html.twig',
            'selection_template_twig' => '@OroIntegration/Autocomplete/integration/selection.html.twig',
        ];
        // this normalizer allows to add/override config options outside.
        $configsNormalizer = function (Options $options, $configs) use (&$defaultConfigs) {
            return array_merge($defaultConfigs, $configs);
        };
        $choiceLoader = function (Options $options) use ($em) {
            $types = $options['allowed_types'] ?? null;

            return new DoctrineChoiceLoader(
                $em,
                Integration::class,
                null,
                new Loader($this->aclHelper, $em, $types)
            );
        };

        $resolver->setDefaults(
            [
                'placeholder' => '',
                'configs'     => $defaultConfigs,
                'choice_loader' => $choiceLoader,
                'choice_label' => 'name',
                'choice_value' => 'id'
            ]
        );
        $resolver->setDefined(['allowed_types']);
        $resolver->setNormalizer('configs', $configsNormalizer);
    }

    #[\Override]
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $typeData = $this->typesRegistry->getAvailableIntegrationTypesDetailedData();

        /** @var ChoiceView $choiceView */
        foreach ($view->vars['choices'] as $choiceView) {
            /** @var Integration $integration */
            $integration = $choiceView->data;
            $attributes  = ['data-status' => $integration->isEnabled()];
            if (isset($typeData[$integration->getType()], $typeData[$integration->getType()]['icon'])) {
                $attributes['data-icon'] = $this->assetHelper->getUrl($typeData[$integration->getType()]['icon']);
            }

            $choiceView->attr = $attributes;
        }
    }

    #[\Override]
    public function getParent(): ?string
    {
        return Select2ChoiceType::class;
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
