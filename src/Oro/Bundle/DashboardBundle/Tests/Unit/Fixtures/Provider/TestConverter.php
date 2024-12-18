<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Fixtures\Provider;

use Oro\Bundle\DashboardBundle\Provider\ConfigValueConverterAbstract;

class TestConverter extends ConfigValueConverterAbstract
{
    #[\Override]
    public function getConvertedValue(array $widgetConfig, $value = null, array $config = [], array $options = [])
    {
        return 'test value';
    }

    #[\Override]
    public function getViewValue($value)
    {
        return 'test view value';
    }

    #[\Override]
    public function getFormValue(array $converterAttributes, $value)
    {
        return $converterAttributes['value'];
    }
}
