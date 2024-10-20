<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\ChangeSubresource;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeSubresourceContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\PropertyAccess\Exception\AccessException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Prepares the form data for a change sub-resource request.
 */
class PrepareFormData implements ProcessorInterface
{
    private PropertyAccessorInterface $propertyAccessor;

    public function __construct(PropertyAccessorInterface $propertyAccessor)
    {
        $this->propertyAccessor = $propertyAccessor;
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var ChangeSubresourceContext $context */

        if ($context->hasResult()) {
            // the form data are already prepared
            return;
        }

        $associationName = $context->getAssociationName();

        $data = $context->getResult();
        if (\is_array($data) && \array_key_exists($associationName, $data) && \count($data) !== 1) {
            // the form data are already prepared
            return;
        }

        $associationData = null;
        if ($this->tryGetAssociationData($context, $associationData)) {
            $context->setRequestData([$associationName => $context->getRequestData()]);
            $context->setResult([$associationName => $associationData]);
        }
    }

    private function tryGetAssociationData(ChangeSubresourceContext $context, mixed &$associationData): bool
    {
        try {
            $associationData = $this->propertyAccessor->getValue(
                $context->getParentEntity(),
                $this->getEntityFieldName($context->getAssociationName(), $context->getParentConfig())
            );
        } catch (AccessException $e) {
            // this processor should do nothing if an association does not exist
            return false;
        }

        return true;
    }

    private function getEntityFieldName(string $fieldName, ?EntityDefinitionConfig $config): string
    {
        if (null === $config) {
            return $fieldName;
        }

        $name = $config->findFieldNameByPropertyPath($fieldName);
        if (!$name) {
            $name = $fieldName;
        }

        return $name;
    }
}
