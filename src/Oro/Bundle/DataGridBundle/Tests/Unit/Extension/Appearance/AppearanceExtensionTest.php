<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Appearance;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Extension\Appearance\AppearanceExtension;
use Oro\Bundle\DataGridBundle\Extension\Appearance\Configuration;
use Symfony\Contracts\Translation\TranslatorInterface;

class AppearanceExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var AppearanceExtension */
    private $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->extension = new AppearanceExtension(
            new Configuration(),
            $this->translator
        );
        $this->extension->setParameters(new ParameterBag());
    }

    public function testIsApplicable()
    {
        $config = DatagridConfiguration::create([
            AppearanceExtension::APPEARANCE_CONFIG_PATH => [
                'grid' => [],
                'board' => [],
            ]
        ]);
        $this->assertTrue($this->extension->isApplicable($config));
    }

    public function testIsNotApplicable()
    {
        $config = DatagridConfiguration::create([]);
        $this->assertFalse($this->extension->isApplicable($config));
    }

    public function testSetParameters()
    {
        $parameters = new ParameterBag(
            [
                ParameterBag::MINIFIED_PARAMETERS => [
                    AppearanceExtension::MINIFIED_APPEARANCE_TYPE_PARAM => 'board',
                    AppearanceExtension::MINIFIED_APPEARANCE_DATA_PARAM => ['id' => 'board id']
                ]
            ]
        );
        $this->extension->setParameters($parameters);
        $expected = [
            AppearanceExtension::APPEARANCE_TYPE_PARAM => 'board',
            AppearanceExtension::APPEARANCE_DATA_PARAM => ['id' => 'board id']
        ];
        $params = $this->extension->getParameters()->get(AppearanceExtension::APPEARANCE_ROOT_PARAM);
        $this->assertEquals($expected, $params);
    }

    public function testProcessConfigs()
    {
        $config = DatagridConfiguration::create([
            AppearanceExtension::APPEARANCE_CONFIG_PATH => [
                'grid' => [
                    'label' => 'grid label',
                    Configuration::DEFAULT_PROCESSING_KEY => true
                ],
                'board' => [],
            ]
        ]);
        $this->translator->expects($this->once())
            ->method('trans')
            ->with('grid label')
            ->willReturn('translated grid label');
        $this->extension->processConfigs($config);
        $expected = [
            'grid' => [
                'label' => 'translated grid label'
            ],
            'board' => []
        ];
        $config->offsetGetByPath(AppearanceExtension::APPEARANCE_OPTION_PATH, $expected);
    }

    public function testVisitMetadata()
    {
        $config = DatagridConfiguration::create([
            AppearanceExtension::APPEARANCE_CONFIG_PATH => [
                'grid' => [],
                'board' => [],
            ]
        ]);
        $data = MetadataObject::create([]);
        $parameters = new ParameterBag(
            [
                AppearanceExtension::APPEARANCE_ROOT_PARAM => [
                    AppearanceExtension::APPEARANCE_TYPE_PARAM => 'board',
                    AppearanceExtension::APPEARANCE_DATA_PARAM => ['id' => 'board id']
                ]
            ]
        );
        $this->extension->setParameters($parameters);
        $this->extension->visitMetadata($config, $data);
        $initialState = [
            'appearanceType' => Configuration::GRID_APPEARANCE_TYPE,
            'appearanceData' => []
        ];
        $this->assertEquals($initialState, $data->offsetGet('initialState'));
        $state = [
            'appearanceType' => 'board',
            'appearanceData' => ['id' => 'board id']
        ];
        $this->assertEquals($state, $data->offsetGet('state'));
    }

    public function testVisitMetadataGridOnly()
    {
        $config = DatagridConfiguration::create([
            AppearanceExtension::APPEARANCE_CONFIG_PATH => [
                'grid' => []
            ]
        ]);
        $data = MetadataObject::create([]);
        $data->offsetSetByPath(AppearanceExtension::APPEARANCE_OPTION_PATH, ['some options']);
        $this->extension->setParameters(new ParameterBag());
        $this->extension->visitMetadata($config, $data);
        $this->assertEmpty($data->offsetGetByPath(AppearanceExtension::APPEARANCE_OPTION_PATH));

        $state = [
            'appearanceType' => Configuration::GRID_APPEARANCE_TYPE,
            'appearanceData' => []
        ];
        $this->assertEquals($state, $data->offsetGet('state'));
    }
}
