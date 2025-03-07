<?php

namespace Oro\Bundle\EntityBundle\Form\Type;

use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\EntityBundle\Provider\EntityProvider;
use Oro\Bundle\FormBundle\Form\Type\Select2ChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Form type for entity field choice.
 */
class EntityFieldChoiceType extends AbstractType
{
    const NAME = 'oro_entity_field_choice';

    /** @var EntityProvider */
    protected $entityProvider;

    /** @var EntityFieldProvider */
    protected $entityFieldProvider;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var array */
    protected $itemsCache;

    public function __construct(
        EntityProvider $entityProvider,
        EntityFieldProvider $entityFieldProvider,
        TranslatorInterface $translator
    ) {
        $this->entityProvider      = $entityProvider;
        $this->entityFieldProvider = $entityFieldProvider;
        $this->translator          = $translator;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $defaultConfigs = [
            'placeholder'             => 'oro.entity.form.choose_entity_field',
            'result_template_twig'    => '@OroEntity/Choice/entity_field/result.html.twig',
            'selection_template_twig' => '@OroEntity/Choice/entity_field/selection.html.twig',
            'component'               => 'entity-field-choice'
        ];

        $resolver->setDefaults(
            [
                'entity'               => null,
                'with_relations'       => false,
                'with_virtual_fields'  => false,
                'choices'              => function (Options $options) {
                    return empty($options['entity']) || $options['skip_load_data']
                        ? [] // return empty list if entity is not specified or skip_load_data = true
                        : $this->getChoices(
                            $options['entity'],
                            $options['with_relations'],
                            $options['with_virtual_fields']
                        );
                },
                'choice_attr'          => function ($choice) {
                    return $this->getChoiceAttributes($choice);
                },
                'placeholder'          => '',
                'skip_load_entities'   => false,
                'skip_load_data'       => false,
                'configs'              => $defaultConfigs,
                'translatable_options' => false
            ]
        );

        $resolver->setNormalizer(
            'configs',
            function (Options $options, $configs) use ($defaultConfigs) {
                return $this->configsNormalizer($options, $configs, $defaultConfigs);
            }
        )
        ->setNormalizer(
            'attr',
            function (Options $options, $attr) {
                return $this->attrNormalizer($options, $attr);
            }
        );
    }

    /**
     * Applies dynamic attributes for 'configs' options
     *
     * @param Options $options
     * @param array   $configs
     * @param array   $defaultConfigs
     *
     * @return array
     */
    protected function configsNormalizer(Options $options, $configs, $defaultConfigs)
    {
        $configs = array_merge($defaultConfigs, $configs);
        if ($options['multiple'] && $configs['placeholder'] === $defaultConfigs['placeholder']) {
            $configs['placeholder'] .= 's';
        }
        if ($options['with_relations'] && !$options['skip_load_entities']) {
            $configs['entities'] = $this->entityProvider->getEntities();
        }

        return $configs;
    }

    /**
     * Applies dynamic attributes for 'attr' options
     *
     * @param Options $options
     * @param array   $attr
     *
     * @return array
     */
    protected function attrNormalizer(Options $options, $attr)
    {
        $attr['data-entity'] = $options['entity'];

        return $attr;
    }

    /**
     * Returns a list of entity fields
     *
     * @param string $entityName        Entity name. Can be full class name or short form: Bundle:Entity.
     * @param bool   $withRelations     Indicates whether association fields should be returned as well.
     * @param bool   $withVirtualFields Indicates whether virtual fields should be returned as well.
     *
     * @return array [{field name} => [{attr1} => {val1}, ...], ...]
     */
    protected function getEntityFields($entityName, $withRelations, $withVirtualFields)
    {
        if (null === $this->itemsCache) {
            $this->itemsCache = [];

            $options = EntityFieldProvider::OPTION_WITH_ENTITY_DETAILS
                | EntityFieldProvider::OPTION_TRANSLATE
                | EntityFieldProvider::OPTION_APPLY_EXCLUSIONS;
            $options |= $withRelations ? EntityFieldProvider::OPTION_WITH_RELATIONS : 0;
            $options |= $withVirtualFields ? EntityFieldProvider::OPTION_WITH_VIRTUAL_FIELDS : 0;
            $fields = $this->entityFieldProvider->getEntityFields($entityName, $options);
            foreach ($fields as $field) {
                $fieldName = $field['name'];
                unset($field['name']);
                $this->itemsCache[$fieldName] = $field;
            }
        }

        return $this->itemsCache;
    }

    /**
     * Returns a list of choices
     *
     * @param string $entityName        Entity name. Can be full class name or short form: Bundle:Entity.
     * @param bool   $withRelations     Indicates whether association fields should be returned as well.
     * @param bool   $withVirtualFields Indicates whether virtual fields should be returned as well.
     *
     * @return array
     */
    protected function getChoices($entityName, $withRelations, $withVirtualFields)
    {
        $choiceFields    = [];
        $choiceRelations = [];
        foreach ($this->getEntityFields($entityName, $withRelations, $withVirtualFields) as $fieldName => $field) {
            if (!isset($field['relation_type'])) {
                $choiceFields[$field['label']] = $fieldName;
            } else {
                $choiceRelations[$field['label']] = $fieldName;
            }
        }

        if (empty($choiceRelations)) {
            return $choiceFields;
        }

        $choices = [];
        if (!empty($choiceFields)) {
            $choices[$this->translator->trans('oro.entity.form.entity_fields')] = $choiceFields;
        }
        $choices[$this->translator->trans('oro.entity.form.entity_related')] = $choiceRelations;

        return $choices;
    }

    /**
     * Returns a list of choice attributes for the given entity field
     *
     * @param string $fieldName
     *
     * @return array
     */
    protected function getChoiceAttributes($fieldName)
    {
        $attributes = [];
        if (null !== $this->itemsCache) {
            foreach ($this->itemsCache[$fieldName] as $key => $val) {
                if ($key === 'related_entity_fields') {
                    continue;
                }
                $attributes['data-' . $key] = $val;
            }
        }

        return $attributes;
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
