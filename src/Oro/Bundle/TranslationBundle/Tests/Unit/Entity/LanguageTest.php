<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Entity;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use PHPUnit\Framework\TestCase;

class LanguageTest extends TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors(): void
    {
        $this->assertPropertyAccessors(new Language(), [
            ['id', 1],
            ['code', 'test_code'],
            ['enabled', true],
            ['installedBuildDate', new \DateTime()],
            ['organization', new Organization()],
            ['createdAt', new \DateTime()],
            ['updatedAt', new \DateTime()],
            ['localFilesLanguage', true],
        ]);
    }
}
