<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Collection\IncludedEntityCollection;
use Oro\Bundle\ApiBundle\Config\ConfigAccessorInterface;
use Oro\Bundle\ApiBundle\Form\FormExtensionSwitcherInterface;
use Oro\Bundle\ApiBundle\Form\Guesser\MetadataTypeGuesser;
use Oro\Bundle\ApiBundle\Metadata\MetadataAccessorInterface;
use Oro\Bundle\ApiBundle\Processor\ContextConfigAccessor;
use Oro\Bundle\ApiBundle\Processor\ContextMetadataAccessor;
use Oro\Bundle\ApiBundle\Processor\Shared\InitializeApiFormExtension;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class InitializeApiFormExtensionTest extends FormProcessorTestCase
{
    private FormExtensionSwitcherInterface&MockObject $formExtensionSwitcher;
    private MetadataTypeGuesser&MockObject $metadataTypeGuesser;
    private InitializeApiFormExtension $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->formExtensionSwitcher = $this->createMock(FormExtensionSwitcherInterface::class);
        $this->metadataTypeGuesser = $this->createMock(MetadataTypeGuesser::class);

        $this->processor = new InitializeApiFormExtension(
            $this->formExtensionSwitcher,
            $this->metadataTypeGuesser
        );
    }

    public function testProcessWhenApiFormExtensionIsNotActivated(): void
    {
        $this->formExtensionSwitcher->expects(self::once())
            ->method('switchToApiFormExtension');
        $this->metadataTypeGuesser->expects(self::once())
            ->method('getIncludedEntities')
            ->willReturn(null);
        $this->metadataTypeGuesser->expects(self::once())
            ->method('getMetadataAccessor')
            ->willReturn(null);
        $this->metadataTypeGuesser->expects(self::once())
            ->method('getConfigAccessor')
            ->willReturn(null);
        $this->metadataTypeGuesser->expects(self::once())
            ->method('setIncludedEntities')
            ->with(null);
        $this->metadataTypeGuesser->expects(self::once())
            ->method('setMetadataAccessor')
            ->with(self::isInstanceOf(ContextMetadataAccessor::class));
        $this->metadataTypeGuesser->expects(self::once())
            ->method('setConfigAccessor')
            ->with(self::isInstanceOf(ContextConfigAccessor::class));

        $this->processor->process($this->context);

        self::assertFalse($this->context->has('previousIncludedEntities'));
        self::assertFalse($this->context->has('previousMetadataAccessor'));
        self::assertFalse($this->context->has('previousConfigAccessor'));
    }

    public function testProcessWhenApiFormExtensionIsActivated(): void
    {
        $this->formExtensionSwitcher->expects(self::never())
            ->method('switchToApiFormExtension');
        $this->metadataTypeGuesser->expects(self::never())
            ->method('getIncludedEntities');
        $this->metadataTypeGuesser->expects(self::never())
            ->method('getMetadataAccessor');
        $this->metadataTypeGuesser->expects(self::never())
            ->method('getConfigAccessor');
        $this->metadataTypeGuesser->expects(self::never())
            ->method('setIncludedEntities');
        $this->metadataTypeGuesser->expects(self::never())
            ->method('setMetadataAccessor');
        $this->metadataTypeGuesser->expects(self::never())
            ->method('setConfigAccessor');

        $this->context->set('apiFormExtensionActivated', true);
        $this->processor->process($this->context);
    }

    public function testProcessWhenMetadataTypeGuesserHasContext(): void
    {
        $currentIncludedEntities = $this->createMock(IncludedEntityCollection::class);

        $includedEntities = $this->createMock(IncludedEntityCollection::class);
        $metadataAccessor = $this->createMock(MetadataAccessorInterface::class);
        $configAccessor = $this->createMock(ConfigAccessorInterface::class);

        $this->formExtensionSwitcher->expects(self::once())
            ->method('switchToApiFormExtension');
        $this->metadataTypeGuesser->expects(self::once())
            ->method('getIncludedEntities')
            ->willReturn($includedEntities);
        $this->metadataTypeGuesser->expects(self::once())
            ->method('getMetadataAccessor')
            ->willReturn($metadataAccessor);
        $this->metadataTypeGuesser->expects(self::once())
            ->method('getConfigAccessor')
            ->willReturn($configAccessor);
        $this->metadataTypeGuesser->expects(self::once())
            ->method('setIncludedEntities')
            ->with(self::identicalTo($currentIncludedEntities));
        $this->metadataTypeGuesser->expects(self::once())
            ->method('setMetadataAccessor')
            ->with(self::isInstanceOf(ContextMetadataAccessor::class));
        $this->metadataTypeGuesser->expects(self::once())
            ->method('setConfigAccessor')
            ->with(self::isInstanceOf(ContextConfigAccessor::class));

        $this->context->setIncludedEntities($currentIncludedEntities);
        $this->processor->process($this->context);

        self::assertSame($includedEntities, $this->context->get('previousIncludedEntities'));
        self::assertSame($metadataAccessor, $this->context->get('previousMetadataAccessor'));
        self::assertSame($configAccessor, $this->context->get('previousConfigAccessor'));
    }
}
