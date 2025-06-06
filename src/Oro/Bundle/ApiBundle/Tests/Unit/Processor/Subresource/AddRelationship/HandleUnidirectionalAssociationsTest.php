<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\AddRelationship;

use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Form\Handler\UnidirectionalAssociationHandler;
use Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteDefinition\UnidirectionalAssociationCompleter;
use Oro\Bundle\ApiBundle\Processor\Subresource\AddRelationship\HandleUnidirectionalAssociations;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\ChangeRelationshipProcessorTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\FormInterface;

class HandleUnidirectionalAssociationsTest extends ChangeRelationshipProcessorTestCase
{
    private UnidirectionalAssociationHandler&MockObject $handler;
    private HandleUnidirectionalAssociations $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = $this->createMock(UnidirectionalAssociationHandler::class);

        $this->processor = new HandleUnidirectionalAssociations($this->handler);
    }

    public function testProcessWhenNoParentConfig(): void
    {
        $this->handler->expects(self::never())
            ->method('handleAdd');

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->willReturn(new Config());
        $this->context->setParentClassName('Test\ParentEntity');
        $this->processor->process($this->context);
    }

    public function testProcessWhenNoUnidirectionalAssociations(): void
    {
        $parentConfig = new EntityDefinitionConfig();

        $this->handler->expects(self::never())
            ->method('handleAdd');

        $this->context->setParentClassName('Test\ParentEntity');
        $this->context->setAssociationName('association');
        $this->context->setParentConfig($parentConfig);
        $this->processor->process($this->context);
    }

    public function testProcessWhenCurrentAssociationIsNotUnidirectionalAssociation(): void
    {
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->set(
            UnidirectionalAssociationCompleter::UNIDIRECTIONAL_ASSOCIATIONS,
            ['field1' => 'targetAssociation1']
        );

        $this->handler->expects(self::never())
            ->method('handleAdd');

        $this->context->setParentClassName('Test\ParentEntity');
        $this->context->setAssociationName('association');
        $this->context->setParentConfig($parentConfig);
        $this->processor->process($this->context);
    }

    public function testProcessWithUnidirectionalAssociations(): void
    {
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->set(
            UnidirectionalAssociationCompleter::UNIDIRECTIONAL_ASSOCIATIONS,
            ['field1' => 'targetAssociation1', 'association' => 'targetAssociation']
        );
        $form = $this->createMock(FormInterface::class);

        $this->handler->expects(self::once())
            ->method('handleAdd')
            ->with(
                self::identicalTo($form),
                self::identicalTo($parentConfig),
                ['association' => 'targetAssociation'],
                self::identicalTo($this->context->getRequestType())
            );

        $this->context->setParentClassName('Test\ParentEntity');
        $this->context->setAssociationName('association');
        $this->context->setParentConfig($parentConfig);
        $this->context->setForm($form);
        $this->processor->process($this->context);
    }
}
