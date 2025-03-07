<?php

namespace Oro\Bundle\OrganizationBundle\Form\Type;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntitiesToIdsTransformer;
use Oro\Bundle\FormBundle\Form\Type\OroJquerySelect2HiddenType;
use Oro\Bundle\OrganizationBundle\Entity\Manager\BusinessUnitManager;
use Oro\Bundle\OrganizationBundle\Form\Transformer\BusinessUnitTreeTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Select business unit with autocomplete form type.
 */
class BusinessUnitSelectAutocomplete extends AbstractType
{
    const NAME = 'oro_type_business_unit_select_autocomplete';

    public function getName()
    {
        return self::NAME;
    }

    /** @var EntityManager */
    protected $entityManager;

    /** @var BusinessUnitManager */
    protected $businessUnitManager;

    /** @var string */
    protected $entityClass;

    /**
     * BusinessUnitSelectAutocomplete constructor.
     */
    public function __construct(
        EntityManager $entityManager,
        $entityClass,
        BusinessUnitManager $businessUnitManager
    ) {
        $this->entityManager = $entityManager;
        $this->entityClass = $entityClass;
        $this->businessUnitManager = $businessUnitManager;
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (isset($options['configs']['multiple']) &&  $options['configs']['multiple'] === true) {
            $builder->addModelTransformer(
                new EntitiesToIdsTransformer($this->entityManager, $this->entityClass)
            );
        } else {
            $builder->resetModelTransformers();
            $builder->addModelTransformer(
                new BusinessUnitTreeTransformer($this->businessUnitManager)
            );
        }
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'configs'            => [
                    'multiple'    => true,
                    'component'   => 'tree-autocomplete',
                    'placeholder' => 'oro.dashboard.form.choose_business_unit',
                    'allowClear'  => true,
                    'entity_id'   => null
                ]
            ]
        );
    }

    #[\Override]
    public function getParent(): ?string
    {
        return OroJquerySelect2HiddenType::class;
    }
}
