<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Provider\ChainEntityOverrideProvider;
use Oro\Bundle\ApiBundle\Provider\MutableEntityOverrideProvider;
use PHPUnit\Framework\TestCase;

class ChainEntityOverrideProviderTest extends TestCase
{
    private ChainEntityOverrideProvider $chainEntityOverrideProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->chainEntityOverrideProvider = new ChainEntityOverrideProvider([
            new MutableEntityOverrideProvider([
                'Test\Entity1' => 'Test\Model1_1',
                'Test\Entity2' => 'Test\Model2_1'
            ]),
            new MutableEntityOverrideProvider([
                'Test\Entity2' => 'Test\Model2_2',
                'Test\Entity3' => 'Test\Model3_2'
            ])
        ]);
    }

    public function testGetSubstituteEntityClassWhenSubstitutionExistsInFirstProvider(): void
    {
        self::assertEquals(
            'Test\Model1_1',
            $this->chainEntityOverrideProvider->getSubstituteEntityClass('Test\Entity1')
        );
    }

    public function testGetSubstituteEntityClassWhenSubstitutionExistsInBothProviders(): void
    {
        self::assertEquals(
            'Test\Model2_1',
            $this->chainEntityOverrideProvider->getSubstituteEntityClass('Test\Entity2')
        );
    }

    public function testGetSubstituteEntityClassWhenSubstitutionExistsInSecondProvider(): void
    {
        self::assertEquals(
            'Test\Model3_2',
            $this->chainEntityOverrideProvider->getSubstituteEntityClass('Test\Entity3')
        );
    }

    public function testGetSubstituteEntityClassWhenSubstitutionDoesNotExist(): void
    {
        self::assertNull(
            $this->chainEntityOverrideProvider->getSubstituteEntityClass('Test\Entity4')
        );
    }

    public function testGetEntityClassWhenSubstitutionExistsInFirstProvider(): void
    {
        self::assertEquals(
            'Test\Entity1',
            $this->chainEntityOverrideProvider->getEntityClass('Test\Model1_1')
        );
    }

    public function testGetEntityClassWhenSubstitutionExistsInBothProviders(): void
    {
        self::assertEquals(
            'Test\Entity2',
            $this->chainEntityOverrideProvider->getEntityClass('Test\Model2_1')
        );
    }

    public function testGetEntityClassWhenSubstitutionExistsInSecondProvider(): void
    {
        self::assertEquals(
            'Test\Entity3',
            $this->chainEntityOverrideProvider->getEntityClass('Test\Model3_2')
        );
    }

    public function testGetEntityClassWhenSubstitutionDoesNotExist(): void
    {
        self::assertNull(
            $this->chainEntityOverrideProvider->getEntityClass('Test\Model4')
        );
    }
}
