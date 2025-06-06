<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config;

use Oro\Bundle\ApiBundle\Config\ActionConfig;
use Oro\Bundle\ApiBundle\Config\ActionFieldConfig;
use Oro\Bundle\ApiBundle\Config\UpsertConfig;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ActionConfigTest extends TestCase
{
    public function testIsEmpty(): void
    {
        $config = new ActionConfig();
        self::assertTrue($config->isEmpty());
    }

    public function testClone(): void
    {
        $config = new ActionConfig();
        self::assertTrue($config->isEmpty());
        self::assertEmpty($config->toArray());

        $config->set('test', 'value');
        $objValue = new \stdClass();
        $objValue->someProp = 123;
        $config->set('test_object', $objValue);

        $configClone = clone $config;

        self::assertEquals($config, $configClone);
        self::assertNotSame($objValue, $configClone->get('test_object'));
    }

    public function testCloneWithUpsertConfig(): void
    {
        $config = new ActionConfig();
        $config->getUpsertConfig()->setAllowedById(true);
        $config->getUpsertConfig()->addFields(['field1']);

        $configClone = clone $config;

        self::assertEquals($config, $configClone);
        self::assertNotSame($config->getUpsertConfig(), $configClone->getUpsertConfig());
    }

    public function testExcluded(): void
    {
        $config = new ActionConfig();
        self::assertFalse($config->hasExcluded());
        self::assertFalse($config->isExcluded());

        $config->setExcluded();
        self::assertTrue($config->hasExcluded());
        self::assertTrue($config->isExcluded());
        self::assertEquals(['exclude' => true], $config->toArray());

        $config->setExcluded(false);
        self::assertTrue($config->hasExcluded());
        self::assertFalse($config->isExcluded());
        self::assertEquals(['exclude' => false], $config->toArray());
    }

    public function testCustomAttribute(): void
    {
        $attrName = 'test';

        $config = new ActionConfig();
        self::assertFalse($config->has($attrName));
        self::assertNull($config->get($attrName));
        self::assertSame([], $config->keys());

        $config->set($attrName, null);
        self::assertFalse($config->has($attrName));
        self::assertNull($config->get($attrName));
        self::assertEquals([], $config->toArray());
        self::assertSame([], $config->keys());

        $config->set($attrName, false);
        self::assertTrue($config->has($attrName));
        self::assertFalse($config->get($attrName));
        self::assertEquals([$attrName => false], $config->toArray());
        self::assertEquals([$attrName], $config->keys());

        $config->remove($attrName);
        self::assertFalse($config->has($attrName));
        self::assertNull($config->get($attrName));
        self::assertSame([], $config->toArray());
        self::assertSame([], $config->keys());
    }

    public function testDescription(): void
    {
        $config = new ActionConfig();
        self::assertFalse($config->hasDescription());
        self::assertNull($config->getDescription());

        $config->setDescription('text');
        self::assertTrue($config->hasDescription());
        self::assertEquals('text', $config->getDescription());
        self::assertEquals(['description' => 'text'], $config->toArray());

        $config->setDescription(null);
        self::assertFalse($config->hasDescription());
        self::assertNull($config->getDescription());
        self::assertEquals([], $config->toArray());

        $config->setDescription('text');
        $config->setDescription('');
        self::assertFalse($config->hasDescription());
        self::assertNull($config->getDescription());
        self::assertEquals([], $config->toArray());
    }

    public function testDocumentation(): void
    {
        $config = new ActionConfig();
        self::assertFalse($config->hasDocumentation());
        self::assertNull($config->getDocumentation());

        $config->setDocumentation('text');
        self::assertTrue($config->hasDocumentation());
        self::assertEquals('text', $config->getDocumentation());
        self::assertEquals(['documentation' => 'text'], $config->toArray());

        $config->setDocumentation(null);
        self::assertFalse($config->hasDocumentation());
        self::assertNull($config->getDocumentation());
        self::assertEquals([], $config->toArray());

        $config->setDocumentation('text');
        $config->setDocumentation('');
        self::assertFalse($config->hasDocumentation());
        self::assertNull($config->getDocumentation());
        self::assertEquals([], $config->toArray());
    }

    public function testAclResource(): void
    {
        $config = new ActionConfig();
        self::assertFalse($config->hasAclResource());
        self::assertNull($config->getAclResource());

        $config->setAclResource('test_acl_resource');
        self::assertTrue($config->hasAclResource());
        self::assertEquals('test_acl_resource', $config->getAclResource());
        self::assertEquals(['acl_resource' => 'test_acl_resource'], $config->toArray());

        $config->setAclResource(null);
        self::assertTrue($config->hasAclResource());
        self::assertNull($config->getAclResource());
        self::assertEquals(['acl_resource' => null], $config->toArray());
    }

    public function testMaxResults(): void
    {
        $config = new ActionConfig();
        self::assertFalse($config->hasMaxResults());
        self::assertNull($config->getMaxResults());

        $config->setMaxResults(123);
        self::assertTrue($config->hasMaxResults());
        self::assertEquals(123, $config->getMaxResults());
        self::assertEquals(['max_results' => 123], $config->toArray());

        $config->setMaxResults(-1);
        self::assertTrue($config->hasMaxResults());
        self::assertEquals(-1, $config->getMaxResults());
        self::assertEquals(['max_results' => -1], $config->toArray());

        $config->setMaxResults('456');
        self::assertTrue($config->hasMaxResults());
        self::assertSame(456, $config->getMaxResults());
        self::assertEquals(['max_results' => 456], $config->toArray());

        $config->setMaxResults(-2);
        self::assertTrue($config->hasMaxResults());
        self::assertEquals(-1, $config->getMaxResults());
        self::assertEquals(['max_results' => -1], $config->toArray());

        $config->setMaxResults(null);
        self::assertFalse($config->hasMaxResults());
        self::assertNull($config->getMaxResults());
        self::assertEquals([], $config->toArray());
    }

    public function testPageSize(): void
    {
        $config = new ActionConfig();
        self::assertFalse($config->hasPageSize());
        self::assertNull($config->getPageSize());

        $config->setPageSize(123);
        self::assertTrue($config->hasPageSize());
        self::assertEquals(123, $config->getPageSize());
        self::assertEquals(['page_size' => 123], $config->toArray());

        $config->setPageSize(-1);
        self::assertTrue($config->hasPageSize());
        self::assertEquals(-1, $config->getPageSize());
        self::assertEquals(['page_size' => -1], $config->toArray());

        $config->setPageSize('456');
        self::assertTrue($config->hasPageSize());
        self::assertSame(456, $config->getPageSize());
        self::assertEquals(['page_size' => 456], $config->toArray());

        $config->setPageSize(-2);
        self::assertTrue($config->hasPageSize());
        self::assertEquals(-1, $config->getPageSize());
        self::assertEquals(['page_size' => -1], $config->toArray());

        $config->setPageSize(null);
        self::assertFalse($config->hasPageSize());
        self::assertNull($config->getPageSize());
        self::assertEquals([], $config->toArray());
    }

    public function testOrderBy(): void
    {
        $config = new ActionConfig();
        self::assertEquals([], $config->getOrderBy());

        $config->setOrderBy(['field1' => 'DESC']);
        self::assertEquals(['field1' => 'DESC'], $config->getOrderBy());
        self::assertEquals(['order_by' => ['field1' => 'DESC']], $config->toArray());

        $config->setOrderBy([]);
        self::assertEquals([], $config->getOrderBy());
        self::assertEquals([], $config->toArray());
    }

    public function testSortingFlag(): void
    {
        $config = new ActionConfig();
        self::assertFalse($config->hasDisableSorting());
        self::assertTrue($config->isSortingEnabled());

        $config->disableSorting();
        self::assertTrue($config->hasDisableSorting());
        self::assertFalse($config->isSortingEnabled());
        self::assertEquals(['disable_sorting' => true], $config->toArray());

        $config->enableSorting();
        self::assertTrue($config->hasDisableSorting());
        self::assertTrue($config->isSortingEnabled());
        self::assertEquals([], $config->toArray());
    }

    public function testInclusionFlag(): void
    {
        $config = new ActionConfig();
        self::assertFalse($config->hasDisableInclusion());
        self::assertTrue($config->isInclusionEnabled());

        $config->disableInclusion();
        self::assertTrue($config->hasDisableInclusion());
        self::assertFalse($config->isInclusionEnabled());
        self::assertEquals(['disable_inclusion' => true], $config->toArray());

        $config->enableInclusion();
        self::assertTrue($config->hasDisableInclusion());
        self::assertTrue($config->isInclusionEnabled());
        self::assertEquals([], $config->toArray());
    }

    public function testFieldsetFlag(): void
    {
        $config = new ActionConfig();
        self::assertFalse($config->hasDisableFieldset());
        self::assertTrue($config->isFieldsetEnabled());

        $config->disableFieldset();
        self::assertTrue($config->hasDisableFieldset());
        self::assertFalse($config->isFieldsetEnabled());
        self::assertEquals(['disable_fieldset' => true], $config->toArray());

        $config->enableFieldset();
        self::assertTrue($config->hasDisableFieldset());
        self::assertTrue($config->isFieldsetEnabled());
        self::assertEquals([], $config->toArray());
    }

    public function testMetaPropertiesFlag(): void
    {
        $config = new ActionConfig();
        self::assertFalse($config->hasDisableMetaProperties());
        self::assertTrue($config->isMetaPropertiesEnabled());

        $config->disableMetaProperties();
        self::assertTrue($config->hasDisableMetaProperties());
        self::assertFalse($config->isMetaPropertiesEnabled());
        self::assertEquals(['disable_meta_properties' => true], $config->toArray());

        $config->enableMetaProperties();
        self::assertTrue($config->hasDisableMetaProperties());
        self::assertTrue($config->isMetaPropertiesEnabled());
        self::assertEquals([], $config->toArray());
    }

    public function testMetaProperties(): void
    {
        $config = new ActionConfig();
        self::assertFalse($config->hasDisableMetaProperties());
        self::assertSame([], $config->getDisabledMetaProperties());

        $config->disableMetaProperty('prop1');
        self::assertTrue($config->hasDisableMetaProperties());
        self::assertTrue($config->isMetaPropertiesEnabled());
        self::assertEquals(['disabled_meta_properties' => ['prop1']], $config->toArray());
        self::assertFalse($config->isMetaPropertyEnabled('prop1'));
        self::assertSame(['prop1'], $config->getDisabledMetaProperties());

        $config->disableMetaProperty('prop2');
        self::assertTrue($config->hasDisableMetaProperties());
        self::assertTrue($config->isMetaPropertiesEnabled());
        self::assertEquals(['disabled_meta_properties' => ['prop1', 'prop2']], $config->toArray());
        self::assertFalse($config->isMetaPropertyEnabled('prop1'));
        self::assertFalse($config->isMetaPropertyEnabled('prop2'));
        self::assertSame(['prop1', 'prop2'], $config->getDisabledMetaProperties());

        $config->disableMetaProperty('prop1');
        self::assertTrue($config->hasDisableMetaProperties());
        self::assertTrue($config->isMetaPropertiesEnabled());
        self::assertEquals(['disabled_meta_properties' => ['prop1', 'prop2']], $config->toArray());
        self::assertFalse($config->isMetaPropertyEnabled('prop1'));
        self::assertFalse($config->isMetaPropertyEnabled('prop2'));
        self::assertSame(['prop1', 'prop2'], $config->getDisabledMetaProperties());

        $config->enableMetaProperty('prop1');
        self::assertTrue($config->hasDisableMetaProperties());
        self::assertTrue($config->isMetaPropertiesEnabled());
        self::assertEquals(['disabled_meta_properties' => ['prop2']], $config->toArray());
        self::assertTrue($config->isMetaPropertyEnabled('prop1'));
        self::assertFalse($config->isMetaPropertyEnabled('prop2'));
        self::assertSame(['prop2'], $config->getDisabledMetaProperties());

        $config->enableMetaProperty('prop1');
        self::assertTrue($config->hasDisableMetaProperties());
        self::assertTrue($config->isMetaPropertiesEnabled());
        self::assertEquals(['disabled_meta_properties' => ['prop2']], $config->toArray());
        self::assertTrue($config->isMetaPropertyEnabled('prop1'));
        self::assertFalse($config->isMetaPropertyEnabled('prop2'));
        self::assertSame(['prop2'], $config->getDisabledMetaProperties());

        $config->enableMetaProperty('prop2');
        self::assertFalse($config->hasDisableMetaProperties());
        self::assertTrue($config->isMetaPropertiesEnabled());
        self::assertEquals([], $config->toArray());
        self::assertTrue($config->isMetaPropertyEnabled('prop1'));
        self::assertTrue($config->isMetaPropertyEnabled('prop2'));
        self::assertSame([], $config->getDisabledMetaProperties());

        $config->disableMetaProperties();
        self::assertTrue($config->hasDisableMetaProperties());
        self::assertFalse($config->isMetaPropertiesEnabled());
        self::assertEquals(['disable_meta_properties' => true], $config->toArray());
        self::assertFalse($config->isMetaPropertyEnabled('prop1'));
        self::assertFalse($config->isMetaPropertyEnabled('prop2'));
        self::assertSame([], $config->getDisabledMetaProperties());
    }

    public function testFormType(): void
    {
        $config = new ActionConfig();
        self::assertNull($config->getFormType());

        $config->setFormType('test');
        self::assertEquals('test', $config->getFormType());
        self::assertEquals(['form_type' => 'test'], $config->toArray());

        $config->setFormType(null);
        self::assertNull($config->getFormType());
        self::assertEquals([], $config->toArray());
    }

    public function testFormOptions(): void
    {
        $config = new ActionConfig();
        self::assertNull($config->getFormOptions());
        self::assertNull($config->getFormOption('key'));
        self::assertSame('', $config->getFormOption('key', ''));

        $config->setFormOptions(['key' => 'val']);
        self::assertEquals(['key' => 'val'], $config->getFormOptions());
        self::assertEquals(['form_options' => ['key' => 'val']], $config->toArray());
        self::assertSame('val', $config->getFormOption('key'));
        self::assertSame('val', $config->getFormOption('key', ''));

        $config->setFormOptions([]);
        self::assertNull($config->getFormOptions());
        self::assertEquals([], $config->toArray());
        self::assertNull($config->getFormOption('key'));
        self::assertSame('', $config->getFormOption('key', ''));

        $config->setFormOptions(null);
        self::assertNull($config->getFormOptions());
        self::assertEquals([], $config->toArray());
        self::assertNull($config->getFormOption('key'));
        self::assertSame('', $config->getFormOption('key', ''));
    }

    public function testSetFormOption(): void
    {
        $config = new ActionConfig();

        $config->setFormOption('option1', 'value1');
        $config->setFormOption('option2', 'value2');
        self::assertEquals(
            ['option1' => 'value1', 'option2' => 'value2'],
            $config->getFormOptions()
        );

        $config->setFormOption('option1', 'newValue');
        self::assertEquals(
            ['option1' => 'newValue', 'option2' => 'value2'],
            $config->getFormOptions()
        );
    }

    public function testFormConstraints(): void
    {
        $config = new ActionConfig();

        self::assertNull($config->getFormOptions());
        self::assertNull($config->getFormConstraints());

        $config->addFormConstraint(new NotNull());
        self::assertEquals(['constraints' => [new NotNull()]], $config->getFormOptions());
        self::assertEquals([new NotNull()], $config->getFormConstraints());

        $config->addFormConstraint(new NotBlank());
        self::assertEquals(['constraints' => [new NotNull(), new NotBlank()]], $config->getFormOptions());
        self::assertEquals([new NotNull(), new NotBlank()], $config->getFormConstraints());
    }

    public function testRemoveFormConstraint(): void
    {
        $config = new ActionConfig();

        self::assertNull($config->getFormOptions());
        self::assertNull($config->getFormConstraints());

        $config->removeFormConstraint(NotNull::class);
        self::assertNull($config->getFormConstraints());

        $config->setFormOption(
            'constraints',
            [
                new NotNull(),
                new NotBlank(),
                [NotNull::class => ['message' => 'test']]
            ]
        );

        $config->removeFormConstraint(NotNull::class);
        self::assertEquals(['constraints' => [new NotBlank()]], $config->getFormOptions());

        $config->removeFormConstraint(NotBlank::class);
        self::assertNull($config->getFormOptions());
    }

    public function testFormEventSubscribers(): void
    {
        $config = new ActionConfig();
        self::assertNull($config->getFormEventSubscribers());

        $config->setFormEventSubscribers(['subscriber1']);
        self::assertEquals(['subscriber1'], $config->getFormEventSubscribers());
        self::assertEquals(['form_event_subscriber' => ['subscriber1']], $config->toArray());

        $config->setFormEventSubscribers([]);
        self::assertNull($config->getFormOptions());
        self::assertEquals([], $config->toArray());
    }

    public function testSetNullToFormEventSubscribers(): void
    {
        $config = new ActionConfig();
        $config->setFormEventSubscribers(['subscriber1']);

        $config->setFormEventSubscribers(null);
        self::assertNull($config->getFormOptions());
        self::assertEquals([], $config->toArray());
    }

    public function testFields(): void
    {
        $config = new ActionConfig();
        self::assertFalse($config->hasFields());
        self::assertEquals([], $config->getFields());
        self::assertTrue($config->isEmpty());
        self::assertEquals([], $config->toArray());

        $field = $config->addField('field');
        self::assertTrue($config->hasFields());
        self::assertTrue($config->hasField('field'));
        self::assertEquals(['field' => $field], $config->getFields());
        self::assertSame($field, $config->getField('field'));
        self::assertFalse($config->isEmpty());
        self::assertEquals(['fields' => ['field' => null]], $config->toArray());

        $config->removeField('field');
        self::assertFalse($config->hasFields());
        self::assertFalse($config->hasField('field'));
        self::assertEquals([], $config->getFields());
        self::assertTrue($config->isEmpty());
        self::assertEquals([], $config->toArray());
    }

    public function testGetOrAddField(): void
    {
        $config = new ActionConfig();

        $field = $config->getOrAddField('field');
        self::assertSame($field, $config->getField('field'));

        $field1 = $config->getOrAddField('field');
        self::assertSame($field, $field1);
    }

    public function testAddField(): void
    {
        $config = new ActionConfig();

        $field = $config->addField('field');
        self::assertSame($field, $config->getField('field'));

        $field1 = new ActionFieldConfig();
        $field1 = $config->addField('field', $field1);
        self::assertSame($field1, $config->getField('field'));
        self::assertNotSame($field, $field1);
    }

    public function testUpsertConfig(): void
    {
        $config = new ActionConfig();
        self::assertInstanceOf(UpsertConfig::class, $config->getUpsertConfig());
        self::assertSame([], $config->toArray());

        $config->getUpsertConfig()->addFields(['field1']);
        self::assertSame(['upsert' => [['field1']]], $config->toArray());

        $config->getUpsertConfig()->addFields(['field2', 'field3']);
        self::assertSame(['upsert' => [['field1'], ['field2', 'field3']]], $config->toArray());

        $config->getUpsertConfig()->setAllowedById(true);
        self::assertSame(['upsert' => [['id'], ['field1'], ['field2', 'field3']]], $config->toArray());

        $config->getUpsertConfig()->setEnabled(false);
        self::assertSame([], $config->toArray());
    }
}
