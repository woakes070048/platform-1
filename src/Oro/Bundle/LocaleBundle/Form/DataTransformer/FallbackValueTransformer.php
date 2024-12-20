<?php

namespace Oro\Bundle\LocaleBundle\Form\DataTransformer;

use Oro\Bundle\LocaleBundle\Model\FallbackType;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Transform data for FallbackValueType
 */
class FallbackValueTransformer implements DataTransformerInterface
{
    #[\Override]
    public function transform($value)
    {
        $result = [
            'value' => null,
            'fallback' => null,
            'use_fallback' => true,
        ];

        if ($value instanceof FallbackType) {
            $result['fallback'] = $value->getType();
        } else {
            $result['use_fallback'] = false;
            $result['value'] = $value;
        }

        return $result;
    }

    #[\Override]
    public function reverseTransform($value)
    {
        if (!$value) {
            return null;
        }

        if (!empty($value['fallback']) && !empty($value['use_fallback'])) {
            return new FallbackType($value['fallback']);
        }

        return $value['value'] ?? '';
    }
}
