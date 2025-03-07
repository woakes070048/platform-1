<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\Shared\SetHttpAllowHeader as BaseSetHttpAllowHeader;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Provider\SubresourcesProvider;

/**
 * A base implementation for processors that set "Allow" HTTP header for sub-resources
 * if the response status code is 405 (Method Not Allowed).
 */
abstract class SetHttpAllowHeader extends BaseSetHttpAllowHeader
{
    private SubresourcesProvider $subresourcesProvider;

    public function __construct(ResourcesProvider $resourcesProvider, SubresourcesProvider $subresourcesProvider)
    {
        parent::__construct($resourcesProvider);
        $this->subresourcesProvider = $subresourcesProvider;
    }

    #[\Override]
    protected function getHttpMethodToActionsMapForResourceWithoutIdentifier(): array
    {
        return $this->getHttpMethodToActionsMap();
    }

    #[\Override]
    protected function getExcludeActions(Context $context): array
    {
        /** @var SubresourceContext $context */

        $excludeActions = $this->getExcludeActionsForClass(
            $context->getParentClassName(),
            $context->getVersion(),
            $context->getRequestType()
        );
        $subresource = $this->subresourcesProvider->getSubresource(
            $context->getParentClassName(),
            $context->getAssociationName(),
            $context->getVersion(),
            $context->getRequestType()
        );
        if (null !== $subresource) {
            $subresourceExcludedActions = $subresource->getExcludedActions();
            if (!empty($subresourceExcludedActions)) {
                $excludeActions = array_unique(array_merge($excludeActions, $subresourceExcludedActions));
            }
        }

        return $excludeActions;
    }
}
