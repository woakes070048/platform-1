<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Extension\AdditionalAttrExtension;
use Oro\Bundle\UIBundle\Form\Type\TreeMoveType;
use Oro\Bundle\UIBundle\Model\TreeCollection;
use Oro\Bundle\UIBundle\Model\TreeItem;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;

class TreeMoveTypeTest extends FormIntegrationTestCase
{
    /**
     * @dataProvider submitProvider
     */
    public function testSubmit(TreeCollection $defaultData, array $submittedData, TreeCollection $expectedData)
    {
        $treeItems = [
            'child' => new TreeItem('child', 'Child'),
            'parent' => new TreeItem('parent', 'Parent'),
        ];
        $treeItems['child']->setParent($treeItems['parent']);

        $treeData = [
            ['id' => 'child', 'text' => 'Child'],
            ['id' => 'parent', 'text' => 'Parent'],
        ];

        $form = $this->factory->create(TreeMoveType::class, $defaultData, [
            'tree_items' => $treeItems,
            'tree_data' => $treeData,
        ]);

        $form->submit($submittedData);

        $this->assertEquals(true, $form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedData, $form->getData());
    }

    public function submitProvider(): array
    {
        $parent = new TreeItem('parent', 'Parent');

        $child = new TreeItem('child', 'Child');
        $child->setParent($parent);

        $collection = new TreeCollection();
        $collection->target = $parent;
        $collection->createRedirect = true;

        return [
            'with data' => [
                'defaultData' => new TreeCollection(),
                'submittedData' => [
                    'target' => 'parent',
                    'createRedirect' => true,
                ],
                'expectedData' => $collection,
            ],
            'empty data' => [
                'defaultData' => new TreeCollection(),
                'submittedData' => [],
                'expectedData' => new TreeCollection(),
            ],
        ];
    }

    #[\Override]
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([], [FormType::class => [new AdditionalAttrExtension()]])
        ];
    }
}
