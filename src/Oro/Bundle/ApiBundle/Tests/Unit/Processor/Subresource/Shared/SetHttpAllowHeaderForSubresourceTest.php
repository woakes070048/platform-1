<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\SetHttpAllowHeaderForSubresource;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Provider\SubresourcesProvider;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\ApiSubresource;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\GetSubresourceProcessorTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class SetHttpAllowHeaderForSubresourceTest extends GetSubresourceProcessorTestCase
{
    private ResourcesProvider&MockObject $resourcesProvider;
    private SubresourcesProvider&MockObject $subresourcesProvider;
    private SetHttpAllowHeaderForSubresource $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->resourcesProvider = $this->createMock(ResourcesProvider::class);
        $this->subresourcesProvider = $this->createMock(SubresourcesProvider::class);

        $this->processor = new SetHttpAllowHeaderForSubresource(
            $this->resourcesProvider,
            $this->subresourcesProvider
        );
    }

    public function testProcessWhenResponseStatusCodeIsNot405(): void
    {
        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);

        $this->resourcesProvider->expects(self::never())
            ->method('getResourceExcludeActions');
        $this->subresourcesProvider->expects(self::never())
            ->method('getSubresource');

        $this->context->setResponseStatusCode(404);
        $this->context->setParentClassName('Test\Class');
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        self::assertFalse($this->context->getResponseHeaders()->has('Allow'));
    }

    public function testProcessWhenAllowResponseHeaderAlreadySet(): void
    {
        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);

        $this->resourcesProvider->expects(self::never())
            ->method('getResourceExcludeActions');
        $this->subresourcesProvider->expects(self::never())
            ->method('getSubresource');

        $this->context->setResponseStatusCode(405);
        $this->context->getResponseHeaders()->set('Allow', 'GET');
        $this->context->setParentClassName('Test\Class');
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        self::assertEquals('GET', $this->context->getResponseHeaders()->get('Allow'));
    }

    public function testProcessWhenAllActionsDisabled(): void
    {
        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);

        $this->resourcesProvider->expects(self::once())
            ->method('getResourceExcludeActions')
            ->with('Test\Class', $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn([
                ApiAction::GET_SUBRESOURCE,
                ApiAction::UPDATE_SUBRESOURCE,
                ApiAction::ADD_SUBRESOURCE,
                ApiAction::DELETE_SUBRESOURCE
            ]);
        $this->subresourcesProvider->expects(self::once())
            ->method('getSubresource')
            ->with('Test\Class', 'testAssociation', $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn(null);

        $this->context->setResponseStatusCode(405);
        $this->context->setParentClassName('Test\Class');
        $this->context->setIsCollection(false);
        $this->context->setAssociationName('testAssociation');
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        self::assertEquals(404, $this->context->getResponseStatusCode());
        self::assertFalse($this->context->getResponseHeaders()->has('Allow'));
    }

    public function testProcessWhenAllActionsEnabled(): void
    {
        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);

        $subresource = new ApiSubresource();

        $this->resourcesProvider->expects(self::once())
            ->method('getResourceExcludeActions')
            ->with('Test\Class', $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn([]);
        $this->subresourcesProvider->expects(self::once())
            ->method('getSubresource')
            ->with('Test\Class', 'testAssociation', $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn($subresource);

        $this->context->setResponseStatusCode(405);
        $this->context->setParentClassName('Test\Class');
        $this->context->setIsCollection(false);
        $this->context->setAssociationName('testAssociation');
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        self::assertEquals('OPTIONS, GET, PATCH, POST, DELETE', $this->context->getResponseHeaders()->get('Allow'));
    }

    public function testProcessWhenOnlyGetSubresourceEnabled(): void
    {
        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);

        $this->resourcesProvider->expects(self::once())
            ->method('getResourceExcludeActions')
            ->with('Test\Class', $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn([
                ApiAction::UPDATE_SUBRESOURCE,
                ApiAction::ADD_SUBRESOURCE,
                ApiAction::DELETE_SUBRESOURCE
            ]);
        $this->subresourcesProvider->expects(self::once())
            ->method('getSubresource')
            ->with('Test\Class', 'testAssociation', $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn(null);

        $this->context->setResponseStatusCode(405);
        $this->context->setParentClassName('Test\Class');
        $this->context->setIsCollection(false);
        $this->context->setAssociationName('testAssociation');
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        self::assertEquals('OPTIONS, GET', $this->context->getResponseHeaders()->get('Allow'));
    }

    public function testProcessWhenOnlyUpdateSubresourceEnabled(): void
    {
        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);

        $this->resourcesProvider->expects(self::once())
            ->method('getResourceExcludeActions')
            ->with('Test\Class', $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn([
                ApiAction::GET_SUBRESOURCE,
                ApiAction::ADD_SUBRESOURCE,
                ApiAction::DELETE_SUBRESOURCE
            ]);
        $this->subresourcesProvider->expects(self::once())
            ->method('getSubresource')
            ->with('Test\Class', 'testAssociation', $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn(null);

        $this->context->setResponseStatusCode(405);
        $this->context->setParentClassName('Test\Class');
        $this->context->setIsCollection(false);
        $this->context->setAssociationName('testAssociation');
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        self::assertEquals('OPTIONS, PATCH', $this->context->getResponseHeaders()->get('Allow'));
    }

    public function testProcessWhenEntityDoesNotHaveIdentifierFields(): void
    {
        $metadata = new EntityMetadata('Test\Entity');

        $this->resourcesProvider->expects(self::once())
            ->method('getResourceExcludeActions')
            ->with('Test\Class', $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn([
                ApiAction::UPDATE_SUBRESOURCE,
                ApiAction::ADD_SUBRESOURCE,
                ApiAction::DELETE_SUBRESOURCE
            ]);
        $this->subresourcesProvider->expects(self::once())
            ->method('getSubresource')
            ->with('Test\Class', 'testAssociation', $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn(null);

        $this->context->setResponseStatusCode(405);
        $this->context->setParentClassName('Test\Class');
        $this->context->setIsCollection(false);
        $this->context->setAssociationName('testAssociation');
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        self::assertEquals('OPTIONS, GET', $this->context->getResponseHeaders()->get('Allow'));
    }

    public function testProcessWhenActionDisabledForParticularAssociation(): void
    {
        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);

        $subresource = new ApiSubresource();
        $subresource->setExcludedActions([ApiAction::UPDATE_SUBRESOURCE]);

        $this->resourcesProvider->expects(self::once())
            ->method('getResourceExcludeActions')
            ->with('Test\Class', $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn([ApiAction::ADD_SUBRESOURCE, ApiAction::DELETE_SUBRESOURCE]);
        $this->subresourcesProvider->expects(self::once())
            ->method('getSubresource')
            ->with('Test\Class', 'testAssociation', $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn($subresource);

        $this->context->setResponseStatusCode(405);
        $this->context->setParentClassName('Test\Class');
        $this->context->setIsCollection(false);
        $this->context->setAssociationName('testAssociation');
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        self::assertEquals('OPTIONS, GET', $this->context->getResponseHeaders()->get('Allow'));
    }
}
