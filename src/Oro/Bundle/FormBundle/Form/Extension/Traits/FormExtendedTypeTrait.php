<?php

namespace Oro\Bundle\FormBundle\Form\Extension\Traits;

use Symfony\Component\Form\Extension\Core\Type\FormType;

trait FormExtendedTypeTrait
{
    public static function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }
}
