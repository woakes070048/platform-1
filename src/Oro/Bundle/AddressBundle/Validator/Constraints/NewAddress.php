<?php

namespace Oro\Bundle\AddressBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * The constraint that can be used to validate that an entity is created with a new address.
 */
class NewAddress extends Constraint
{
    /** @var string */
    public $message = 'oro.address.validation.existing_address_used';

    #[\Override]
    public function getTargets(): string|array
    {
        return [self::PROPERTY_CONSTRAINT];
    }
}
