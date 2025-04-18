<?php

namespace Oro\Bundle\ApiBundle\Form;

use Oro\Bundle\ApiBundle\Form\Extension\SwitchableDependencyInjectionExtension;
use Symfony\Component\Form\Exception\InvalidArgumentException;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\FormRegistry;
use Symfony\Component\Form\ResolvedFormTypeFactoryInterface;
use Symfony\Component\Form\ResolvedFormTypeInterface;

/**
 * The form registry is used to switch between default forms that are used on UI and API forms.
 * Unfortunately we have to use inheritance instead of aggregation because
 * some 3-rd party bundles can use FormRegistry instead of FormRegistryInterface.
 * An example of such usages is A2lix\TranslationFormBundle\TranslationForm\TranslationForm.
 */
class SwitchableFormRegistry extends FormRegistry implements FormExtensionSwitcherInterface
{
    public const DEFAULT_EXTENSION = 'default';
    public const API_EXTENSION = 'api';

    private SwitchableDependencyInjectionExtension $extension;
    private FormExtensionState $extensionState;
    private int $switchCounter = 0;

    /**
     * @param FormExtensionInterface[]         $extensions
     * @param ResolvedFormTypeFactoryInterface $resolvedTypeFactory
     * @param FormExtensionState               $extensionState
     */
    public function __construct(
        array $extensions,
        ResolvedFormTypeFactoryInterface $resolvedTypeFactory,
        FormExtensionState $extensionState
    ) {
        parent::__construct($extensions, $resolvedTypeFactory);

        if (\count($extensions) !== 1) {
            throw new \InvalidArgumentException('Expected only one form extension.');
        }
        $extension = reset($extensions);
        if (!$extension instanceof SwitchableDependencyInjectionExtension) {
            throw new \InvalidArgumentException(sprintf(
                'Expected type of form extension is "%s", "%s" given.',
                SwitchableDependencyInjectionExtension::class,
                \get_class($extension)
            ));
        }
        $this->extension = $extension;
        $this->extensionState = $extensionState;
    }

    #[\Override]
    public function switchToDefaultFormExtension(): void
    {
        if ($this->switchCounter > 0) {
            $this->switchCounter--;
            if (0 === $this->switchCounter) {
                $this->switchFormExtension(self::DEFAULT_EXTENSION);
                $this->extensionState->switchToDefaultFormExtension();
            }
        }
    }

    #[\Override]
    public function switchToApiFormExtension(): void
    {
        if (0 === $this->switchCounter) {
            $this->switchFormExtension(self::API_EXTENSION);
            $this->extensionState->switchToApiFormExtension();
        }
        $this->switchCounter++;
    }

    private function switchFormExtension(string $extensionName): void
    {
        $this->extension->switchFormExtension($extensionName);
        // clear local cache
        // unfortunately $types and $guesser property are private and there is no other way
        // to reset them except to use the reflection
        $this->setPrivatePropertyValue('types', []);
        $this->setPrivatePropertyValue('guesser', false);
    }

    #[\Override]
    public function getType(string $name): ResolvedFormTypeInterface
    {
        // prevent using of not registered in API form types
        if ($this->extensionState->isApiFormExtensionActivated()) {
            $isKnownType = false;
            $extensions = $this->getExtensions();
            foreach ($extensions as $extension) {
                if ($extension->hasType($name)) {
                    $isKnownType = true;
                    break;
                }
            }
            if (!$isKnownType) {
                throw new InvalidArgumentException(sprintf(
                    'The form type "%s" is not configured to be used in API.',
                    $name
                ));
            }
        }

        return parent::getType($name);
    }

    private function setPrivatePropertyValue(string $propertyName, mixed $value): void
    {
        $r = new \ReflectionClass(FormRegistry::class);
        if (!$r->hasProperty($propertyName)) {
            throw new \RuntimeException(sprintf('The "%s" property does not exist.', $propertyName));
        }
        $p = $r->getProperty($propertyName);
        $p->setAccessible(true);
        $p->setValue($this, $value);
    }
}
