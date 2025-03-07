<?php

namespace Oro\Bundle\ApiBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * Transforms a value between a number (the string data type) and a string.
 */
class NumberToStringTransformer implements DataTransformerInterface
{
    private ?int $scale;

    public function __construct(?int $scale = null)
    {
        $this->scale = $scale;
    }

    #[\Override]
    public function transform($value)
    {
        if (null === $value) {
            return '';
        }

        if (!is_numeric($value)) {
            throw new TransformationFailedException('Expected a numeric.');
        }

        return (string)$value;
    }

    #[\Override]
    public function reverseTransform($value)
    {
        if (!\is_string($value)) {
            throw new TransformationFailedException('Expected a string.');
        }

        if ('' === $value) {
            return null;
        }

        return $this->transformStringToNumber($value);
    }

    /**
     * @throws TransformationFailedException if the given string cannot be converted to a number
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function transformStringToNumber(string $value): string
    {
        if (0 === $this->scale) {
            if (!preg_match('/^-?\d+$/', $value)) {
                throw new TransformationFailedException(sprintf(
                    '"%s" cannot be converted to an integer number.',
                    $value
                ));
            }
        } elseif (!preg_match('/^-?\d*\.?\d+$/', $value)) {
            throw new TransformationFailedException(sprintf('"%s" cannot be converted to a number.', $value));
        }

        $delimiter = strpos($value, '.');
        if (false !== $delimiter) {
            if ($this->scale > 0) {
                $numberToRound = substr($value, $delimiter + 1);
                if (\strlen($numberToRound) > $this->scale) {
                    $numberToRound = substr($numberToRound, 0, $this->scale + 1);
                    $value = substr($value, 0, $delimiter + 1) . $this->round($numberToRound);
                }
            }
            if (0 === $delimiter) {
                $value = '0' . $value;
            } elseif (1 === $delimiter && '-' === $value[0]) {
                $value = '-0' . substr($value, 1);
            }
        }

        return $value;
    }

    /**
     * Rounds a string contains an integer and returns a string contains the rounded value.
     */
    private function round(string $value): string
    {
        return (string)floor(round((string)((int)$value / 10), 0, PHP_ROUND_HALF_UP));
    }
}
