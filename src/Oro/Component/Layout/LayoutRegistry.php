<?php

namespace Oro\Component\Layout;

use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\Layout\Block\Type\Options;
use Oro\Component\Layout\Extension\ExtensionInterface;

/**
 * The main registry of the Layout component
 */
class LayoutRegistry implements LayoutRegistryInterface
{
    /** @var array */
    private $extensions = [];

    /** @var ExtensionInterface[] */
    private $sorted;

    /** @var BlockTypeInterface[] */
    private $types = [];

    /** @var object[] */
    private $dataProviders = [];

    /** @var BlockTypeExtensionInterface[] */
    private $typeExtensions = [];

    #[\Override]
    public function getTypeNames(): array
    {
        $typeNames = [];
        $extensions = $this->getExtensions();
        foreach ($extensions as $extension) {
            $typeNames[] = $extension->getTypeNames();
        }

        return array_unique(array_merge(...$typeNames));
    }

    #[\Override]
    public function getType($name)
    {
        if (!is_string($name)) {
            throw new Exception\UnexpectedTypeException($name, 'string');
        }

        if (isset($this->types[$name])) {
            return $this->types[$name];
        }

        $type = null;

        $extensions = $this->getExtensions();
        foreach ($extensions as $extension) {
            if ($extension->hasType($name)) {
                $type = $extension->getType($name);
                break;
            }
        }
        if (!$type) {
            throw new Exception\InvalidArgumentException(sprintf('Could not load a block type "%s".', $name));
        }
        $this->types[$name] = $type;

        return $type;
    }

    #[\Override]
    public function getTypeExtensions($name)
    {
        return isset($this->typeExtensions[$name])
            ? $this->typeExtensions[$name]
            : $this->loadTypeExtensions($name);
    }

    #[\Override]
    public function getContextConfigurators()
    {
        $configurators = [];

        $extensions = $this->getExtensions();
        foreach ($extensions as $extension) {
            if ($extension->hasContextConfigurators()) {
                $configurators = array_merge($configurators, $extension->getContextConfigurators());
            }
        }

        return $configurators;
    }

    #[\Override]
    public function findDataProvider($name)
    {
        if (!is_string($name)) {
            throw new Exception\UnexpectedTypeException($name, 'string');
        }

        if (isset($this->dataProviders[$name])) {
            return $this->dataProviders[$name];
        }

        $dataProvider = null;

        $extensions = $this->getExtensions();
        foreach ($extensions as $extension) {
            if ($extension->hasDataProvider($name)) {
                $dataProvider = $extension->getDataProvider($name);
                break;
            }
        }
        $this->dataProviders[$name] = $dataProvider;

        return $dataProvider;
    }

    #[\Override]
    public function configureOptions($name, OptionsResolver $resolver)
    {
        $extensions = isset($this->typeExtensions[$name])
            ? $this->typeExtensions[$name]
            : $this->loadTypeExtensions($name);

        foreach ($extensions as $extension) {
            $extension->configureOptions($resolver);
        }
    }

    #[\Override]
    public function buildBlock($name, BlockBuilderInterface $builder, Options $options)
    {
        $extensions = isset($this->typeExtensions[$name])
            ? $this->typeExtensions[$name]
            : $this->loadTypeExtensions($name);

        foreach ($extensions as $extension) {
            $extension->buildBlock($builder, $options);
        }
    }

    #[\Override]
    public function buildView($name, BlockView $view, BlockInterface $block, Options $options)
    {
        $extensions = isset($this->typeExtensions[$name])
            ? $this->typeExtensions[$name]
            : $this->loadTypeExtensions($name);

        foreach ($extensions as $extension) {
            $extension->buildView($view, $block, $options);
        }
    }

    #[\Override]
    public function finishView($name, BlockView $view, BlockInterface $block)
    {
        $extensions = isset($this->typeExtensions[$name])
            ? $this->typeExtensions[$name]
            : $this->loadTypeExtensions($name);

        foreach ($extensions as $extension) {
            $extension->finishView($view, $block);
        }
    }

    #[\Override]
    public function updateLayout($id, LayoutManipulatorInterface $layoutManipulator, LayoutItemInterface $item)
    {
        $extensions = $this->getExtensions();
        foreach ($extensions as $extension) {
            if ($extension->hasLayoutUpdates($item)) {
                $layoutUpdates = $extension->getLayoutUpdates($item);
                foreach ($layoutUpdates as $layoutUpdate) {
                    $layoutUpdate->updateLayout($layoutManipulator, $item);
                }
            }
        }
    }

    #[\Override]
    public function configureContext(ContextInterface $context)
    {
        $extensions = $this->getExtensions();
        foreach ($extensions as $extension) {
            if ($extension->hasContextConfigurators()) {
                $configurators = $extension->getContextConfigurators();
                foreach ($configurators as $configurator) {
                    $configurator->configureContext($context);
                }
            }
        }
    }

    /**
     * Registers an layout extension.
     *
     * @param ExtensionInterface $extension
     * @param int                $priority
     */
    public function addExtension(ExtensionInterface $extension, $priority = 0)
    {
        $this->extensions[$priority][] = $extension;
        $this->sorted                  = null;
        $this->typeExtensions          = [];
    }

    /**
     * Returns all registered extensions sorted by priority.
     *
     * @return ExtensionInterface[]
     */
    protected function getExtensions()
    {
        if (null === $this->sorted) {
            ksort($this->extensions);
            $this->sorted = !empty($this->extensions)
                ? array_merge(...array_values($this->extensions))
                : [];
        }

        return $this->sorted;
    }

    /**
     * @param string $name
     *
     * @return BlockTypeExtensionInterface[]
     */
    protected function loadTypeExtensions($name)
    {
        $typeExtensions = [];

        $extensions = $this->getExtensions();
        foreach ($extensions as $extension) {
            if ($extension->hasTypeExtensions($name)) {
                $typeExtensions = array_merge($typeExtensions, $extension->getTypeExtensions($name));
            }
        }
        $this->typeExtensions[$name] = $typeExtensions;

        return $typeExtensions;
    }
}
