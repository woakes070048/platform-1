<?php

namespace Oro\Component\Layout;

use Oro\Bundle\EntityExtendBundle\EntityExtend\PropertyAccessorWithDotArraySyntax;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

/**
 * Block options manipulator layout.
 */
class BlockOptionsManipulator implements BlockOptionsManipulatorInterface
{
    /** @var RawLayout */
    protected $rawLayout;

    protected PropertyAccessorInterface $propertyAccessor;

    public function __construct()
    {
        $this->propertyAccessor = PropertyAccess::createPropertyAccessorWithDotSyntax(
            throw: PropertyAccessorWithDotArraySyntax::THROW_ON_INVALID_INDEX
        );
    }

    #[\Override]
    public function setRawLayout(RawLayout $rawLayout)
    {
        $this->rawLayout = $rawLayout;
    }

    #[\Override]
    public function setOption($id, $optionName, $optionValue)
    {
        $options = $this->rawLayout->getProperty($id, RawLayout::OPTIONS);
        $this->propertyAccessor->setValue($options, $this->getPropertyPath($optionName), $optionValue);
        $this->rawLayout->setProperty($id, RawLayout::OPTIONS, $options);
    }

    #[\Override]
    public function appendOption($id, $optionName, $optionValue)
    {
        $options = $this->rawLayout->getProperty($id, RawLayout::OPTIONS);
        $path    = $this->getPropertyPath($optionName);
        try {
            $value = $this->propertyAccessor->getValue($options, $path);
            if (!$value instanceof OptionValueBag) {
                $value = $this->createOptionValueBag($value);
            }
            $value->add($optionValue);
        } catch (NoSuchPropertyException $ex) {
            $value = $optionValue;
        }
        $this->propertyAccessor->setValue($options, $path, $value);
        $this->rawLayout->setProperty($id, RawLayout::OPTIONS, $options);
    }

    #[\Override]
    public function subtractOption($id, $optionName, $optionValue)
    {
        $options = $this->rawLayout->getProperty($id, RawLayout::OPTIONS);
        $path    = $this->getPropertyPath($optionName);
        try {
            $value = $this->propertyAccessor->getValue($options, $path);
        } catch (NoSuchPropertyException $ex) {
            $value = null;
        }
        if (!$value instanceof OptionValueBag) {
            $value = $this->createOptionValueBag($value);
        }
        $value->remove($optionValue);
        $this->propertyAccessor->setValue($options, $path, $value);
        $this->rawLayout->setProperty($id, RawLayout::OPTIONS, $options);
    }

    #[\Override]
    public function replaceOption($id, $optionName, $oldOptionValue, $newOptionValue)
    {
        $options = $this->rawLayout->getProperty($id, RawLayout::OPTIONS);
        $path    = $this->getPropertyPath($optionName);
        try {
            $value = $this->propertyAccessor->getValue($options, $path);
        } catch (NoSuchPropertyException $ex) {
            $value = null;
        }
        if (!$value instanceof OptionValueBag) {
            $value = $this->createOptionValueBag($value);
        }
        $value->replace($oldOptionValue, $newOptionValue);
        $this->propertyAccessor->setValue($options, $path, $value);
        $this->rawLayout->setProperty($id, RawLayout::OPTIONS, $options);
    }

    #[\Override]
    public function removeOption($id, $optionName)
    {
        $options = $this->rawLayout->getProperty($id, RawLayout::OPTIONS);
        $this->propertyAccessor->remove($options, $this->getPropertyPath($optionName));
        $this->rawLayout->setProperty($id, RawLayout::OPTIONS, $options);
    }

    /**
     * @param string $optionName The option name or path
     *
     * @return PropertyPath
     */
    protected function getPropertyPath($optionName)
    {
        return new PropertyPath($optionName);
    }

    /**
     * @param mixed $initialValue
     *
     * @return OptionValueBag
     */
    protected function createOptionValueBag($initialValue)
    {
        $result = new OptionValueBag();
        if (null !== $initialValue) {
            $result->add($initialValue);
        }

        return $result;
    }
}
