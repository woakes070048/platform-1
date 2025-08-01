<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\ApiBundle\Collection\IncludedEntityCollection;
use Oro\Bundle\ApiBundle\Collection\IncludedEntityData;
use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Form\Type\ArrayType;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\CustomizeFormData\CustomizeFormDataProcessorTestCase;
use Oro\Bundle\EntityBundle\Api\Processor\ValidateEntityFallback;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Oro\Bundle\EntityBundle\Tests\Unit\Fallback\Stub\FallbackContainingEntity;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ValidateEntityFallbackTest extends CustomizeFormDataProcessorTestCase
{
    private EntityFallbackResolver&MockObject $fallbackResolver;
    private ValidateEntityFallback $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->fallbackResolver = $this->createMock(EntityFallbackResolver::class);

        $this->processor = new ValidateEntityFallback(
            $this->fallbackResolver,
            PropertyAccess::createPropertyAccessor()
        );
    }

    private function getEntityFieldFallbackValueForm(EntityFieldFallbackValue $fallbackValue): FormInterface
    {
        $formBuilder = $this->createFormBuilder()->create(
            '',
            FormType::class,
            ['data_class' => EntityFieldFallbackValue::class]
        );
        $formBuilder
            ->add('fallback', TextType::class)
            ->add('scalarValue', TextType::class)
            ->add('arrayValue', ArrayType::class);

        $formBuilder->setData($fallbackValue);

        return $formBuilder->getForm();
    }

    private function getIncludedEntityCollection(
        object $primaryEntity,
        EntityMetadata $primaryEntityMetadata,
        EntityFieldFallbackValue $fallbackValue
    ): IncludedEntityCollection {
        $includedEntities = new IncludedEntityCollection();
        $includedEntities->setPrimaryEntityId(get_class($primaryEntity), null);
        $includedEntities->setPrimaryEntity($primaryEntity, $primaryEntityMetadata);
        $includedEntities->add(
            $fallbackValue,
            get_class($fallbackValue),
            'fallback_value',
            new IncludedEntityData('/included/0', 0)
        );

        return $includedEntities;
    }

    private static function assertFormSubmittedAndValid(FormInterface $form)
    {
        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isSubmitted());
        self::assertTrue($form->isValid());
    }

    private function expectRequiredFallbackFieldByType(
        object $entity,
        string $associationName,
        string $valueType,
        string $requiredFieldType
    ): void {
        $this->fallbackResolver->expects(self::once())
            ->method('getType')
            ->with(self::identicalTo($entity), $associationName)
            ->willReturn($valueType);
        $this->fallbackResolver->expects(self::once())
            ->method('getRequiredFallbackFieldByType')
            ->with($valueType)
            ->willReturn($requiredFieldType);
    }

    public function testProcessWhenFormAlreadyContainsValidationErrors(): void
    {
        $fallbackValue = new EntityFieldFallbackValue();
        $form = $this->getEntityFieldFallbackValueForm($fallbackValue);
        $form->submit([]);

        FormUtil::addFormError($form, 'some error');

        // guard
        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isSubmitted());
        self::assertFalse($form->isValid());

        $this->context->setForm($form);
        $this->context->setData($fallbackValue);
        $this->processor->process($this->context);

        self::assertCount(1, $form->getErrors(true));
    }

    /**
     * @dataProvider invalidNumberOfAttributesDataProvider
     */
    public function testProcessWhenFallbackValueHasInvalidNumberOfAttributes(array $submittedData): void
    {
        $fallbackValue = new EntityFieldFallbackValue();
        $form = $this->getEntityFieldFallbackValueForm($fallbackValue);
        $form->submit($submittedData);

        // guard
        self::assertFormSubmittedAndValid($form);

        $this->context->setForm($form);
        $this->context->setData($fallbackValue);
        $this->processor->process($this->context);

        $errors = $form->getErrors(true);
        self::assertCount(1, $errors);
        self::assertEquals(
            'Either "fallback", "scalarValue" or "arrayValue" property should be specified.',
            $errors[0]->getMessage()
        );
        self::assertEquals('', $errors[0]->getCause()->getPropertyPath());
    }

    public function invalidNumberOfAttributesDataProvider(): array
    {
        return [
            'empty'                    => [
                'submittedData' => []
            ],
            'all attributes empty'     => [
                'submittedData' => [
                    'fallback'    => null,
                    'scalarValue' => null,
                    'arrayValue'  => []
                ]
            ],
            'fallback + scalarValue'   => [
                'submittedData' => [
                    'fallback'    => 'fallbackValue',
                    'scalarValue' => 'scalarValue'
                ]
            ],
            'fallback + arrayValue'    => [
                'submittedData' => [
                    'fallback'   => 'fallbackValue',
                    'arrayValue' => ['key' => 'value']
                ]
            ],
            'scalarValue + arrayValue' => [
                'submittedData' => [
                    'scalarValue' => 'scalarValue',
                    'arrayValue'  => ['key' => 'value']
                ]
            ],
            'all attributes'           => [
                'submittedData' => [
                    'fallback'    => 'fallbackValue',
                    'scalarValue' => 'scalarValue',
                    'arrayValue'  => ['key' => 'value']
                ]
            ]
        ];
    }

    public function testProcessWhenFallbackValueHasOnlyFallback(): void
    {
        $submittedData = [
            'fallback' => 'fallbackValue'
        ];
        $fallbackValue = new EntityFieldFallbackValue();
        $form = $this->getEntityFieldFallbackValueForm($fallbackValue);
        $form->submit($submittedData);

        // guard
        self::assertFormSubmittedAndValid($form);

        $this->context->setForm($form);
        $this->context->setData($fallbackValue);
        $this->processor->process($this->context);

        self::assertCount(0, $form->getErrors(true));
        self::assertEquals($submittedData['fallback'], $fallbackValue->getFallback());
    }

    public function testProcessWhenFallbackValueHasOnlyScalarValue(): void
    {
        $submittedData = [
            'scalarValue' => 'scalarValue'
        ];
        $fallbackValue = new EntityFieldFallbackValue();
        $form = $this->getEntityFieldFallbackValueForm($fallbackValue);
        $form->submit($submittedData);

        // guard
        self::assertFormSubmittedAndValid($form);

        $this->context->setForm($form);
        $this->context->setData($fallbackValue);
        $this->processor->process($this->context);

        self::assertCount(0, $form->getErrors(true));
        self::assertEquals($submittedData['scalarValue'], $fallbackValue->getScalarValue());
    }

    public function testProcessWhenFallbackValueHasOnlyArrayValue(): void
    {
        $submittedData = [
            'arrayValue' => ['key' => 'value']
        ];
        $fallbackValue = new EntityFieldFallbackValue();
        $form = $this->getEntityFieldFallbackValueForm($fallbackValue);
        $form->submit($submittedData);

        // guard
        self::assertFormSubmittedAndValid($form);

        $this->context->setForm($form);
        $this->context->setData($fallbackValue);
        $this->processor->process($this->context);

        self::assertCount(0, $form->getErrors(true));
        self::assertEquals($submittedData['arrayValue'], $fallbackValue->getArrayValue());
    }

    public function testProcessWhenFallbackValueHasNotAcceptableFallback(): void
    {
        $submittedData = [
            'fallback' => 'fallback3'
        ];
        $fallbackValue = new EntityFieldFallbackValue();
        $form = $this->getEntityFieldFallbackValueForm($fallbackValue);
        $form->submit($submittedData);

        // guard
        self::assertFormSubmittedAndValid($form);

        $primaryEntity = new FallbackContainingEntity($fallbackValue);
        $primaryEntityMetadata = new EntityMetadata('Test\Entity');
        $primaryEntityMetadata->addAssociation(new AssociationMetadata('testProperty'))
            ->setTargetClassName(EntityFieldFallbackValue::class);

        $includedEntities = $this->getIncludedEntityCollection($primaryEntity, $primaryEntityMetadata, $fallbackValue);

        $this->fallbackResolver->expects(self::once())
            ->method('getFallbackConfig')
            ->with(self::identicalTo($primaryEntity), 'testProperty', EntityFieldFallbackValue::FALLBACK_LIST)
            ->willReturn([
                'fallback1' => [],
                'fallback2' => []
            ]);

        $this->context->setForm($form);
        $this->context->setData($fallbackValue);
        $this->context->setIncludedEntities($includedEntities);
        $this->processor->process($this->context);

        $errors = $form->getErrors(true);
        self::assertCount(1, $errors);
        self::assertEquals(
            'The value is not valid. Acceptable values: fallback1,fallback2.',
            $errors[0]->getMessage()
        );
        self::assertEquals('children[fallback]', $errors[0]->getCause()->getPropertyPath());
    }

    public function testProcessWhenFallbackValueHasAcceptableFallback(): void
    {
        $submittedData = [
            'fallback' => 'fallback1'
        ];
        $fallbackValue = new EntityFieldFallbackValue();
        $form = $this->getEntityFieldFallbackValueForm($fallbackValue);
        $form->submit($submittedData);

        // guard
        self::assertFormSubmittedAndValid($form);

        $primaryEntity = new FallbackContainingEntity($fallbackValue);
        $primaryEntityMetadata = new EntityMetadata('Test\Entity');
        $primaryEntityMetadata->addAssociation(new AssociationMetadata('testProperty'))
            ->setTargetClassName(EntityFieldFallbackValue::class);

        $includedEntities = $this->getIncludedEntityCollection($primaryEntity, $primaryEntityMetadata, $fallbackValue);

        $this->fallbackResolver->expects(self::once())
            ->method('getFallbackConfig')
            ->with(self::identicalTo($primaryEntity), 'testProperty', EntityFieldFallbackValue::FALLBACK_LIST)
            ->willReturn([
                'fallback1' => [],
                'fallback2' => []
            ]);

        $this->context->setForm($form);
        $this->context->setData($fallbackValue);
        $this->context->setIncludedEntities($includedEntities);
        $this->processor->process($this->context);

        self::assertCount(0, $form->getErrors(true));
        self::assertEquals($submittedData['fallback'], $fallbackValue->getFallback());
    }

    public function testProcessWhenFallbackValueHasScalarValueButShouldBeArrayValue(): void
    {
        $submittedData = [
            'scalarValue' => 'test'
        ];
        $fallbackValue = new EntityFieldFallbackValue();
        $form = $this->getEntityFieldFallbackValueForm($fallbackValue);
        $form->submit($submittedData);

        // guard
        self::assertFormSubmittedAndValid($form);

        $primaryEntity = new FallbackContainingEntity($fallbackValue);
        $primaryEntityMetadata = new EntityMetadata('Test\Entity');
        $primaryEntityMetadata->addAssociation(new AssociationMetadata('testProperty'))
            ->setTargetClassName(EntityFieldFallbackValue::class);

        $includedEntities = $this->getIncludedEntityCollection($primaryEntity, $primaryEntityMetadata, $fallbackValue);

        $this->expectRequiredFallbackFieldByType(
            $primaryEntity,
            'testProperty',
            EntityFallbackResolver::TYPE_ARRAY,
            EntityFieldFallbackValue::FALLBACK_ARRAY_FIELD
        );

        $this->context->setForm($form);
        $this->context->setData($fallbackValue);
        $this->context->setIncludedEntities($includedEntities);
        $this->processor->process($this->context);

        $errors = $form->getErrors(true);
        self::assertCount(1, $errors);
        self::assertEquals('The value should not be blank.', $errors[0]->getMessage());
        self::assertEquals('children[arrayValue]', $errors[0]->getCause()->getPropertyPath());
    }

    public function testProcessWhenFallbackValueHasArrayValueButShouldBeScalarValue(): void
    {
        $submittedData = [
            'arrayValue' => ['key' => 'value']
        ];
        $fallbackValue = new EntityFieldFallbackValue();
        $form = $this->getEntityFieldFallbackValueForm($fallbackValue);
        $form->submit($submittedData);

        // guard
        self::assertFormSubmittedAndValid($form);

        $primaryEntity = new FallbackContainingEntity($fallbackValue);
        $primaryEntityMetadata = new EntityMetadata('Test\Entity');
        $primaryEntityMetadata->addAssociation(new AssociationMetadata('testProperty'))
            ->setTargetClassName(EntityFieldFallbackValue::class);

        $includedEntities = $this->getIncludedEntityCollection($primaryEntity, $primaryEntityMetadata, $fallbackValue);

        $this->expectRequiredFallbackFieldByType(
            $primaryEntity,
            'testProperty',
            EntityFallbackResolver::TYPE_STRING,
            EntityFieldFallbackValue::FALLBACK_SCALAR_FIELD
        );

        $this->context->setForm($form);
        $this->context->setData($fallbackValue);
        $this->context->setIncludedEntities($includedEntities);
        $this->processor->process($this->context);

        $errors = $form->getErrors(true);
        self::assertCount(1, $errors);
        self::assertEquals('The value should not be null.', $errors[0]->getMessage());
        self::assertEquals('children[scalarValue]', $errors[0]->getCause()->getPropertyPath());
    }

    public function testProcessWhenFallbackValueHasScalarValueAndScalarValueExpected(): void
    {
        $submittedData = [
            'scalarValue' => 'test'
        ];
        $fallbackValue = new EntityFieldFallbackValue();
        $form = $this->getEntityFieldFallbackValueForm($fallbackValue);
        $form->submit($submittedData);

        // guard
        self::assertFormSubmittedAndValid($form);

        $primaryEntity = new FallbackContainingEntity($fallbackValue);
        $primaryEntityMetadata = new EntityMetadata('Test\Entity');
        $primaryEntityMetadata->addAssociation(new AssociationMetadata('testProperty'))
            ->setTargetClassName(EntityFieldFallbackValue::class);

        $includedEntities = $this->getIncludedEntityCollection($primaryEntity, $primaryEntityMetadata, $fallbackValue);

        $this->expectRequiredFallbackFieldByType(
            $primaryEntity,
            'testProperty',
            EntityFallbackResolver::TYPE_STRING,
            EntityFieldFallbackValue::FALLBACK_SCALAR_FIELD
        );

        $this->context->setForm($form);
        $this->context->setData($fallbackValue);
        $this->context->setIncludedEntities($includedEntities);
        $this->processor->process($this->context);

        self::assertCount(0, $form->getErrors(true));
        self::assertEquals($submittedData['scalarValue'], $fallbackValue->getScalarValue());
    }

    public function testProcessWhenFallbackValueHasArrayValueAndArrayValueExpected(): void
    {
        $submittedData = [
            'arrayValue' => ['key' => 'value']
        ];
        $fallbackValue = new EntityFieldFallbackValue();
        $form = $this->getEntityFieldFallbackValueForm($fallbackValue);
        $form->submit($submittedData);

        // guard
        self::assertFormSubmittedAndValid($form);

        $primaryEntity = new FallbackContainingEntity($fallbackValue);
        $primaryEntityMetadata = new EntityMetadata('Test\Entity');
        $primaryEntityMetadata->addAssociation(new AssociationMetadata('testProperty'))
            ->setTargetClassName(EntityFieldFallbackValue::class);

        $includedEntities = $this->getIncludedEntityCollection($primaryEntity, $primaryEntityMetadata, $fallbackValue);

        $this->expectRequiredFallbackFieldByType(
            $primaryEntity,
            'testProperty',
            EntityFallbackResolver::TYPE_ARRAY,
            EntityFieldFallbackValue::FALLBACK_ARRAY_FIELD
        );

        $this->context->setForm($form);
        $this->context->setData($fallbackValue);
        $this->context->setIncludedEntities($includedEntities);
        $this->processor->process($this->context);

        self::assertCount(0, $form->getErrors(true));
        self::assertEquals($submittedData['arrayValue'], $fallbackValue->getArrayValue());
    }

    public function testProcessWhenAssociationNotFound(): void
    {
        $submittedData = [
            'scalarValue' => 'test'
        ];
        $fallbackValue = new EntityFieldFallbackValue();
        $form = $this->getEntityFieldFallbackValueForm($fallbackValue);
        $form->submit($submittedData);

        // guard
        self::assertFormSubmittedAndValid($form);

        $primaryEntity = new FallbackContainingEntity($fallbackValue);
        $primaryEntityMetadata = new EntityMetadata('Test\Entity');
        $primaryEntityMetadata->addAssociation(new AssociationMetadata('testProperty'))
            ->setTargetClassName(\stdClass::class);

        $includedEntities = $this->getIncludedEntityCollection($primaryEntity, $primaryEntityMetadata, $fallbackValue);

        $this->fallbackResolver->expects(self::never())
            ->method('getType');
        $this->fallbackResolver->expects(self::never())
            ->method('getRequiredFallbackFieldByType');

        $this->context->setForm($form);
        $this->context->setData($fallbackValue);
        $this->context->setIncludedEntities($includedEntities);
        $this->processor->process($this->context);

        self::assertCount(0, $form->getErrors(true));
        self::assertEquals($submittedData['scalarValue'], $fallbackValue->getScalarValue());
    }

    public function testProcessWhenAnotherIncludedEntityAssociatedWithFallbackValue(): void
    {
        $submittedData = [
            'scalarValue' => 'test'
        ];
        $fallbackValue = new EntityFieldFallbackValue();
        $form = $this->getEntityFieldFallbackValueForm($fallbackValue);
        $form->submit($submittedData);

        // guard
        self::assertFormSubmittedAndValid($form);

        $primaryEntity = new FallbackContainingEntity();
        $primaryEntityMetadata = new EntityMetadata('Test\Entity');
        $primaryEntityMetadata->addAssociation(new AssociationMetadata('testProperty'))
            ->setTargetClassName(EntityFieldFallbackValue::class);

        $includedEntities = $this->getIncludedEntityCollection($primaryEntity, $primaryEntityMetadata, $fallbackValue);

        $anotherIncludedEntity = new FallbackContainingEntity($fallbackValue);
        $anotherIncludedEntityMetadata = new EntityMetadata('Test\Entity');
        $anotherIncludedEntityMetadata->addAssociation(new AssociationMetadata('testProperty'))
            ->setTargetClassName(EntityFieldFallbackValue::class);
        $anotherIncludedEntityData = new IncludedEntityData('/included/1', 1);
        $anotherIncludedEntityData->setMetadata($anotherIncludedEntityMetadata);
        $includedEntities->add(
            $anotherIncludedEntity,
            get_class($anotherIncludedEntity),
            'another',
            $anotherIncludedEntityData
        );

        $this->expectRequiredFallbackFieldByType(
            $anotherIncludedEntity,
            'testProperty',
            EntityFallbackResolver::TYPE_STRING,
            EntityFieldFallbackValue::FALLBACK_SCALAR_FIELD
        );

        $this->context->setForm($form);
        $this->context->setData($fallbackValue);
        $this->context->setIncludedEntities($includedEntities);
        $this->processor->process($this->context);

        self::assertCount(0, $form->getErrors(true));
        self::assertEquals($submittedData['scalarValue'], $fallbackValue->getScalarValue());
    }
}
