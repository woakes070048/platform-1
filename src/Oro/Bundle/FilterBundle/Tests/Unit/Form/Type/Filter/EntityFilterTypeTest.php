<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Form\Type\Filter;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FilterBundle\Form\Type\Filter\ChoiceFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\EntityFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\FilterType;
use Oro\Bundle\FilterBundle\Tests\Unit\Fixtures\CustomFormExtension;
use Oro\Bundle\FilterBundle\Tests\Unit\Form\Type\AbstractTypeTestCase;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;

class EntityFilterTypeTest extends AbstractTypeTestCase
{
    /** @var EntityFilterType */
    private $type;

    #[\Override]
    protected function setUp(): void
    {
        $translator = $this->createMockTranslator();

        $registry = $this->createMock(ManagerRegistry::class);

        $types = [
            new FilterType($translator),
            new ChoiceFilterType($translator),
            new EntityType($registry)
        ];

        $this->formExtensions[] = new CustomFormExtension($types);

        parent::setUp();

        $this->type = new EntityFilterType($translator);
    }

    #[\Override]
    protected function getTestFormType(): AbstractType
    {
        return $this->type;
    }

    public function testGetParent()
    {
        $this->assertEquals(ChoiceFilterType::class, $this->type->getParent());
    }

    #[\Override]
    public function configureOptionsDataProvider(): array
    {
        return [
            [
                'defaultOptions' => [
                    'field_type' => EntityType::class,
                    'field_options' => [],
                    'translatable'  => false,
                ]
            ]
        ];
    }

    /**
     * @dataProvider bindDataProvider
     */
    #[\Override]
    public function testBindData(
        array $bindData,
        array $formData,
        array $viewData,
        array $customOptions = []
    ) {
        // bind method should be tested in functional test
    }

    #[\Override]
    public function bindDataProvider(): array
    {
        return [];
    }
}
