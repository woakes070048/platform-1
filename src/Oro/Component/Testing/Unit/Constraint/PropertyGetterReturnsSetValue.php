<?php

namespace Oro\Component\Testing\Unit\Constraint;

/**
 * Constraint that asserts that a get*() or is*() getter returns the value
 * set by a corresponding set*() setter.
 */
class PropertyGetterReturnsSetValue extends \PHPUnit\Framework\Constraint\Constraint
{
    /**
     * @var string
     */
    private $getterName;

    /**
     * @var string
     */
    private $propertyName;

    /**
     * @var mixed
     */
    private $testValue;

    /**
     * @param string $propertyName
     * @param mixed $testValue
     */
    public function __construct($propertyName, $testValue)
    {
        $this->propertyName = $propertyName;
        $this->testValue = $testValue;
    }

    #[\Override]
    public function toString(): string
    {
        return sprintf(
            'getter %s for property %s returns the previously set value %s',
            $this->getterName,
            $this->propertyName,
            $this->exporter()->export($this->testValue)
        );
    }
    #[\Override]
    protected function failureDescription($other): string
    {
        return sprintf(
            'getter %s for property %s of class %s returns the previously set value %s',
            $this->getterName,
            $this->propertyName,
            get_class($other),
            $this->exporter()->export($this->testValue)
        );
    }

    #[\Override]
    protected function matches($other): bool
    {
        $setter = 'set' . ucfirst($this->propertyName);
        if (method_exists($other, $setter)) {
            call_user_func_array([$other, $setter], array($this->testValue));
        } else {
            $class = new \ReflectionClass($other);
            $prop = $class->getProperty($this->propertyName);
            $prop->setAccessible(true);
            $prop->setValue($other, $this->testValue);
        }

        $this->getterName = 'get' . ucfirst($this->propertyName);
        if (!method_exists($other, $this->getterName)) {
            $this->getterName = 'is' . ucfirst($this->propertyName);
        }
        if (!method_exists($other, $this->getterName)) {
            $message = sprintf(
                "Class %s doesn't have %s() or %s() getters for property %s",
                get_class($other),
                'get' . ucfirst($this->propertyName),
                'is' . ucfirst($this->propertyName),
                $this->propertyName
            );
            throw new \PHPUnit\Framework\Exception($message);
        }

        return call_user_func_array([$other, $this->getterName], []) === $this->testValue;
    }
}
