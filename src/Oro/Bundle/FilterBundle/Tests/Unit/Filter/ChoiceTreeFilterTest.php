<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Filter;

use Oro\Bundle\FilterBundle\Filter\ChoiceTreeFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Component\Testing\Unit\ORM\OrmTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Routing\RouterInterface;

class ChoiceTreeFilterTest extends OrmTestCase
{
    private FormFactoryInterface&MockObject $formFactory;
    private RouterInterface&MockObject $router;
    private EventDispatcherInterface&MockObject $dispatcher;
    private ChoiceTreeFilter $filter;

    #[\Override]
    protected function setUp(): void
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->router = $this->createMock(RouterInterface::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->filter = new ChoiceTreeFilter(
            $this->formFactory,
            new FilterUtility(),
            $this->router,
            $this->dispatcher
        );
    }

    public function testGetMetadata(): void
    {
        $this->initMockFormFactory();
        $expectedMetadata = $this->getDefaultExpectedData();
        $params = [];
        $this->filter->init('filter', $params);
        $metadata = $this->filter->getMetadata();

        self::assertEquals($expectedMetadata, $metadata);
    }

    public function testMetadataParameterLazyTrue(): void
    {
        $this->initMockFormFactory();

        $params = [
            'options' => [
                'lazy' => true
            ]
        ];

        $expectedMetadata = $this->getDefaultExpectedData();
        $expectedMetadata['lazy'] = true;

        $this->filter->init('filter', $params);
        $metadata = $this->filter->getMetadata();

        self::assertEquals($expectedMetadata, $metadata);
    }

    public function testMetadataParameterAutocompleteAlias(): void
    {
        $this->initMockFormFactory();

        $params = [
            'autocomplete_alias' => 'test_alias'
        ];

        $expectedMetadata = $this->getDefaultExpectedData();
        $expectedMetadata['autocomplete_alias'] = 'test_alias';

        $this->filter->init('filter', $params);
        $metadata = $this->filter->getMetadata();

        self::assertEquals($expectedMetadata, $metadata);
    }

    public function testMetadataParameterAutocompleteUrl(): void
    {
        $this->initMockFormFactory();
        $this->router->expects(self::once())
            ->method('generate')
            ->with('test_route_name')
            ->willReturn('test_url');

        $params = [
            'autocomplete_url' => 'test_route_name'
        ];

        $expectedMetadata = $this->getDefaultExpectedData();
        $expectedMetadata['autocomplete_url'] = 'test_url';

        $this->filter->init('filter', $params);
        $metadata = $this->filter->getMetadata();

        self::assertEquals($expectedMetadata, $metadata);
    }

    public function testMetadataParameterRenderedPropertyName(): void
    {
        $this->initMockFormFactory();
        $params = [
            'renderedPropertyName' => 'test_field_name'
        ];

        $expectedMetadata = $this->getDefaultExpectedData();
        $expectedMetadata['renderedPropertyName'] = 'test_field_name';

        $this->filter->init('filter', $params);
        $metadata = $this->filter->getMetadata();

        self::assertEquals($expectedMetadata, $metadata);
    }

    private function getDefaultExpectedData()
    {
        return [
            'name' => 'filter',
            'label' => 'Filter',
            'choices' => [],
            'lazy' => false,
            'type' => 'choice-tree',
            'data' => [],
            'autocomplete_alias' => false,
            'autocomplete_url' => null,
            'renderedPropertyName' => false,
        ];
    }

    private function initMockFormFactory()
    {
        $form = $this->createMock(Form::class);
        $formViewType = $this->createMock(FormView::class);
        $formViewType->vars['choices'] = [];
        $formView = $this->createMock(FormView::class);
        $formView->children['type'] = $formViewType;
        $form->expects(self::once())
            ->method('createView')
            ->willReturn($formView);
        $this->formFactory->expects(self::once())
            ->method('create')
            ->willReturn($form);
    }

    public function testPrepareData(): void
    {
        $data = [];
        self::assertSame($data, $this->filter->prepareData($data));
    }
}
