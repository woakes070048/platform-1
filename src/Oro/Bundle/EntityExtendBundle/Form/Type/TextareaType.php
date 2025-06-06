<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\TextareaType as SymfonyTextareaType;

/**
 * This form type is just a wrapper around standard 'textarea' form type, but
 * this form type can handle 'require_schema_update' option that
 * allows to mark an entity as "Required Update" in case when a value of
 * an entity config attribute is changed.
 * An example of usage in entity_config.yml:
 * my_attr:
 *      options:
 *          require_schema_update: true
 *      form:
 *          type: oro_entity_extend_textarea
 */
class TextareaType extends AbstractConfigType
{
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_entity_extend_textarea';
    }

    #[\Override]
    public function getParent(): ?string
    {
        return SymfonyTextareaType::class;
    }
}
