<?php

namespace Oro\Bundle\CurrencyBundle\Converter;

use Oro\Bundle\CurrencyBundle\Entity\MultiCurrency;

class RateConverter implements RateConverterInterface
{
    /**
     * @param MultiCurrency $currency
     *
     * @return float
     */
    #[\Override]
    public function getBaseCurrencyAmount(MultiCurrency $currency)
    {
        return $currency->getValue();
    }
}
