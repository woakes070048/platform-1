<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Config;

use Oro\Bundle\ConfigBundle\Config\SettingsConverter;
use Oro\Bundle\ConfigBundle\Entity\Config;
use Oro\Bundle\ConfigBundle\Entity\ConfigValue;
use PHPUnit\Framework\TestCase;

class SettingsConverterTest extends TestCase
{
    /**
     * @dataProvider dataProviderForConverter
     */
    public function testConvertToSettings(Config $config, array $expectedSettings): void
    {
        $this->assertEquals($expectedSettings, SettingsConverter::convertToSettings($config));
    }

    public function dataProviderForConverter(): array
    {
        $configWithoutValues = new Config();

        $configWithValues = new Config();
        $configValue1 = (new ConfigValue())
            ->setSection('oro_user')
            ->setName('level')
            ->setValue(2000)
            ->setType('scalar');
        $configValue2 = (new ConfigValue())
            ->setSection('oro_user')
            ->setName('level_second')
            ->setValue(['test'])
            ->setType('array');
        $configWithValues->getValues()->add($configValue1);
        $configWithValues->getValues()->add($configValue2);

        return [
            [$configWithoutValues, []],
            [$configWithValues, ['oro_user' => [
                'level' => [
                    'value' => 2000,
                    'use_parent_scope_value' => false,
                    'createdAt' => null,
                    'updatedAt' => null,
                ],
                'level_second' => [
                    'value' => ['test'],
                    'use_parent_scope_value' => false,
                    'createdAt' => null,
                    'updatedAt' => null,
                ],
            ]]],
        ];
    }
}
