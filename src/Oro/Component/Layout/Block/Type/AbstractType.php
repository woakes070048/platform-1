<?php

namespace Oro\Component\Layout\Block\Type;

use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\Layout\BlockBuilderInterface;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockTypeInterface;
use Oro\Component\Layout\BlockView;

abstract class AbstractType implements BlockTypeInterface
{
    #[\Override]
    public function buildBlock(BlockBuilderInterface $builder, Options $options)
    {
    }

    #[\Override]
    public function buildView(BlockView $view, BlockInterface $block, Options $options)
    {
    }

    #[\Override]
    public function finishView(BlockView $view, BlockInterface $block)
    {
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
    }

    #[\Override]
    public function getParent()
    {
        return BaseType::NAME;
    }
}
