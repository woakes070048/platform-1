<?php

namespace Oro\Bundle\DataGridBundle\Tests\Functional\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\DataGridBundle\Entity\AbstractGridView;
use Oro\Bundle\DataGridBundle\Entity\AbstractGridViewUser;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\AbstractUser;

abstract class AbstractDataGridRepositoryTest extends WebTestCase
{
    /** @var EntityRepository */
    protected $repository;

    /** @var AclHelper */
    protected $aclHelper;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->initClient();
        $this->aclHelper = self::getContainer()->get('oro_security.acl_helper');
    }

    /**
     * @param AbstractGridView|AbstractGridViewUser     $needle
     * @param AbstractGridView[]|AbstractGridViewUser[] $haystack
     */
    protected function assertGridViewExists(AbstractGridView|AbstractGridViewUser $needle, array $haystack): void
    {
        $found = false;
        foreach ($haystack as $view) {
            if ($view->getId() === $needle->getId()) {
                $found = true;
                break;
            }
        }

        self::assertTrue(
            $found,
            sprintf(
                'GridView with id "%d" not found in array "%s"',
                $needle->getId(),
                implode(', ', array_map(static fn ($item) => $item->getId(), $haystack))
            )
        );
    }

    abstract protected function getUserReference(): string;

    protected function getUser(): AbstractUser
    {
        /** @var AbstractUser $user */
        $user = $this->getReference($this->getUserReference());

        $this->setSecurityToken($user);

        return $user;
    }

    protected function setSecurityToken(AbstractUser $user): void
    {
        self::getContainer()->get('security.token_storage')->setToken(new UsernamePasswordOrganizationToken(
            $user,
            'main',
            $user->getOrganization(),
            $user->getUserRoles()
        ));
    }
}
