<?php

namespace Oro\Bundle\CurrencyBundle\Rounding;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CurrencyBundle\Exception\InvalidRoundingTypeException;

/**
 * The base class for rounding services.
 */
abstract class AbstractRoundingService implements RoundingServiceInterface
{
    /**
     * @var ConfigManager
     */
    protected $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @param float|int $value
     * @param int $precision
     * @param int|null $roundType
     * @return float|int
     * @throws InvalidRoundingTypeException
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    #[\Override]
    public function round($value, $precision = null, $roundType = null)
    {
        if (null === $roundType) {
            $roundType = (int)$this->getRoundType();
        }

        if (null === $precision) {
            $precision = (int)$this->getPrecision();
        }

        // shift number to maintain the correct scale during rounding
        /** @var int $roundingCoef */
        $roundingCoef = pow(10, $precision);
        $value = (float)$value * $roundingCoef;

        switch ($roundType) {
            case self::ROUND_CEILING:
                $value = ceil($value);
                break;
            case self::ROUND_FLOOR:
                $value = floor($value);
                break;
            case self::ROUND_UP:
                $value = $value > 0 ? ceil($value) : floor($value);
                break;
            case self::ROUND_DOWN:
                $value = $value > 0 ? floor($value) : ceil($value);
                break;
            case self::ROUND_HALF_EVEN:
                $value = round($value, 0, PHP_ROUND_HALF_EVEN);
                break;
            case self::ROUND_HALF_UP:
                $value = round($value, 0, PHP_ROUND_HALF_UP);
                break;
            case self::ROUND_HALF_DOWN:
                $value = round($value, 0, PHP_ROUND_HALF_DOWN);
                break;
            default:
                throw new InvalidRoundingTypeException('The type of the rounding is not valid "intl" rounding mode.');
                break;
        }

        $value /= $roundingCoef;

        return $value;
    }

    /**
     * @return int
     */
    #[\Override]
    abstract public function getRoundType();

    /**
     * @return int
     */
    #[\Override]
    abstract public function getPrecision();
}
