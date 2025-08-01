<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Voter;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Domain\DomainObjectWrapper;
use Oro\Bundle\SecurityBundle\Acl\Domain\OneShotIsGrantedObserver;
use Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionInterface;
use Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionSelector;
use Oro\Bundle\SecurityBundle\Acl\Group\AclGroupProviderInterface;
use Oro\Bundle\SecurityBundle\Acl\Voter\AclVoter;
use Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\CmsAddress;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Model\AclProviderInterface;
use Symfony\Component\Security\Acl\Model\ObjectIdentityRetrievalStrategyInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityRetrievalStrategyInterface;
use Symfony\Component\Security\Acl\Permission\PermissionMapInterface;
use Symfony\Component\Security\Acl\Voter\FieldVote;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AclVoterTest extends TestCase
{
    private PermissionMapInterface&MockObject $permissionMap;
    private AclExtensionSelector&MockObject $extensionSelector;
    private AclGroupProviderInterface&MockObject $groupProvider;
    private TokenInterface&MockObject $securityToken;
    private AclExtensionInterface&MockObject $extension;
    private AclVoter $voter;

    #[\Override]
    protected function setUp(): void
    {
        $this->permissionMap = $this->createMock(PermissionMapInterface::class);
        $this->extensionSelector = $this->createMock(AclExtensionSelector::class);
        $this->groupProvider = $this->createMock(AclGroupProviderInterface::class);

        $this->voter = new AclVoter(
            $this->createMock(AclProviderInterface::class),
            $this->createMock(ObjectIdentityRetrievalStrategyInterface::class),
            $this->createMock(SecurityIdentityRetrievalStrategyInterface::class),
            $this->permissionMap
        );
        $this->voter->setAclExtensionSelector($this->extensionSelector);
        $this->voter->setAclGroupProvider($this->groupProvider);

        $this->securityToken = $this->createMock(TokenInterface::class);
        $this->extension = $this->createMock(AclExtensionInterface::class);
    }

    public function testOneShotIsGrantedObserver(): void
    {
        $object = new \stdClass();

        $this->extensionSelector->expects(self::exactly(2))
            ->method('select')
            ->with(self::identicalTo($object))
            ->willReturn($this->extension);
        $this->permissionMap->expects(self::exactly(2))
            ->method('contains')
            ->with('test')
            ->willReturn(true);
        $this->permissionMap->expects(self::exactly(2))
            ->method('getMasks')
            ->willReturnCallback(function () {
                $this->voter->setTriggeredMask(1, AccessLevel::LOCAL_LEVEL);

                return null;
            });

        $isGrantedObserver = $this->createMock(OneShotIsGrantedObserver::class);
        $isGrantedObserver->expects(self::once())
            ->method('setAccessLevel')
            ->with(AccessLevel::LOCAL_LEVEL);

        $this->voter->addOneShotIsGrantedObserver($isGrantedObserver);
        $this->voter->vote($this->securityToken, $object, ['test']);

        // call the vote method one more time to ensure that OneShotIsGrantedObserver was removed from the voter
        $this->voter->vote($this->securityToken, $object, ['test']);
    }

    public function testInitialState(): void
    {
        self::assertNull($this->voter->getSecurityToken());
        self::assertNull($this->voter->getObject());
        self::assertNull($this->voter->getAclExtension());
    }

    public function testClearStateAfterVote(): void
    {
        $object = new \stdClass();

        $this->extensionSelector->expects(self::once())
            ->method('select')
            ->with(self::identicalTo($object))
            ->willReturn($this->extension);
        $this->permissionMap->expects(self::once())
            ->method('contains')
            ->with('test')
            ->willReturn(true);
        $this->permissionMap->expects(self::once())
            ->method('getMasks')
            ->willReturn(null);

        $this->voter->vote($this->securityToken, $object, ['test']);

        self::assertNull($this->voter->getSecurityToken());
        self::assertNull($this->voter->getObject());
        self::assertNull($this->voter->getAclExtension());
    }

    public function testClearStateAfterVoteEvenIfExceptionOccurred(): void
    {
        $object = new \stdClass();
        $exception = new \Exception('some error');

        $this->extensionSelector->expects(self::once())
            ->method('select')
            ->with(self::identicalTo($object))
            ->willReturn($this->extension);
        $this->permissionMap->expects(self::once())
            ->method('contains')
            ->with('test')
            ->willReturn(true);
        $this->permissionMap->expects(self::once())
            ->method('getMasks')
            ->willThrowException($exception);

        try {
            $this->voter->vote($this->securityToken, $object, ['test']);
            self::fail('Expected that the exception is not handled');
        } catch (\Exception $e) {
            self::assertSame($exception, $e);
        }

        self::assertNull($this->voter->getSecurityToken());
        self::assertNull($this->voter->getObject());
        self::assertNull($this->voter->getAclExtension());
    }

    public function testStateOfVote(): void
    {
        $object = new \stdClass();

        $inVoteToken = null;
        $inVoteObject = null;
        $inVoteExtension = null;

        $this->extensionSelector->expects(self::once())
            ->method('select')
            ->with(self::identicalTo($object))
            ->willReturn($this->extension);
        $this->permissionMap->expects(self::once())
            ->method('contains')
            ->with('test')
            ->willReturn(true);
        $this->permissionMap->expects(self::once())
            ->method('getMasks')
            ->willReturnCallback(function () use (&$inVoteToken, &$inVoteObject, &$inVoteExtension) {
                $inVoteToken = $this->voter->getSecurityToken();
                $inVoteObject = $this->voter->getObject();
                $inVoteExtension = $this->voter->getAclExtension();

                return null;
            });

        $this->voter->vote($this->securityToken, $object, ['test']);

        self::assertSame($this->securityToken, $inVoteToken);
        self::assertSame($object, $inVoteObject);
        self::assertSame($this->extension, $inVoteExtension);
    }

    public function testAclExtensionNotFound(): void
    {
        $object = new \stdClass();

        $this->extensionSelector->expects(self::once())
            ->method('select')
            ->with(self::identicalTo($object))
            ->willReturn(null);
        $this->permissionMap->expects(self::never())
            ->method('contains');

        self::assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($this->securityToken, $object, ['test'])
        );
    }

    public function testVoteAccessAbstain(): void
    {
        $object = new \stdClass();

        $this->extensionSelector->expects(self::once())
            ->method('select')
            ->with(self::identicalTo($object))
            ->willReturn($this->extension);
        $this->permissionMap->expects(self::once())
            ->method('contains')
            ->with('test')
            ->willReturn(true);
        $this->permissionMap->expects(self::once())
            ->method('getMasks')
            ->willReturn(null);

        self::assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($this->securityToken, $object, ['test'])
        );
    }

    public function testVoteAccessGranted(): void
    {
        $object = new \stdClass();

        $this->extensionSelector->expects(self::once())
            ->method('select')
            ->with(self::identicalTo($object))
            ->willReturn($this->extension);
        $this->permissionMap->expects(self::once())
            ->method('contains')
            ->with('test')
            ->willReturn(true);
        $this->permissionMap->expects(self::once())
            ->method('getMasks')
            ->willReturn(1);

        self::assertEquals(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($this->securityToken, $object, ['test'])
        );
    }

    public function testVoteForObjectIdentityObject(): void
    {
        $object = new ObjectIdentity('stdClass', 'entity');

        $this->extensionSelector->expects(self::once())
            ->method('select')
            ->with(self::identicalTo($object))
            ->willReturn($this->extension);

        $this->groupProvider->expects(self::once())
            ->method('getGroup')
            ->willReturn(AclGroupProviderInterface::DEFAULT_SECURITY_GROUP);
        $this->extension->expects(self::once())
            ->method('getPermissions')
            ->with(null, false, true)
            ->willReturn(['test']);

        $this->permissionMap->expects(self::exactly(2))
            ->method('contains')
            ->with('test')
            ->willReturn(true);
        $this->permissionMap->expects(self::once())
            ->method('getMasks')
            ->willReturn(null);

        self::assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($this->securityToken, $object, ['test'])
        );
    }

    public function testVoteForObjectIdentityObjectWhenObjectGroupIsNotEqualCurrentGroup(): void
    {
        $object = new ObjectIdentity('stdClass', 'test_group@entity');

        $this->extensionSelector->expects(self::once())
            ->method('select')
            ->with(self::identicalTo($object))
            ->willReturn($this->extension);

        $this->groupProvider->expects(self::once())
            ->method('getGroup')
            ->willReturn(AclGroupProviderInterface::DEFAULT_SECURITY_GROUP);
        $this->extension->expects(self::never())
            ->method('getPermissions');

        $this->permissionMap->expects(self::never())
            ->method('contains');
        $this->permissionMap->expects(self::never())
            ->method('getMasks');

        self::assertEquals(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($this->securityToken, $object, ['test'])
        );
    }

    public function testVoteForObjectIdentityObjectWhenObjectGroupIsEqualCurrentGroup(): void
    {
        $object = new ObjectIdentity('stdClass', 'test_group@entity');

        $this->extensionSelector->expects(self::once())
            ->method('select')
            ->with(self::identicalTo($object))
            ->willReturn($this->extension);

        $this->groupProvider->expects(self::once())
            ->method('getGroup')
            ->willReturn('test_group');
        $this->extension->expects(self::once())
            ->method('getPermissions')
            ->with(null, false, true)
            ->willReturn(['test']);

        $this->permissionMap->expects(self::exactly(2))
            ->method('contains')
            ->with('test')
            ->willReturn(true);
        $this->permissionMap->expects(self::once())
            ->method('getMasks')
            ->willReturn(null);

        self::assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($this->securityToken, $object, ['test'])
        );
    }

    public function testVoteForObjectIdentityObjectWhenExtensionDoesNotSupportGivenPermission(): void
    {
        $object = new ObjectIdentity('stdClass', 'entity');

        $this->extensionSelector->expects(self::once())
            ->method('select')
            ->with(self::identicalTo($object))
            ->willReturn($this->extension);

        $this->groupProvider->expects(self::once())
            ->method('getGroup')
            ->willReturn(AclGroupProviderInterface::DEFAULT_SECURITY_GROUP);
        $this->extension->expects(self::once())
            ->method('getPermissions')
            ->with(null, false, true)
            ->willReturn(['test1']);

        $this->permissionMap->expects(self::once())
            ->method('contains')
            ->with('test')
            ->willReturn(true);
        $this->permissionMap->expects(self::never())
            ->method('getMasks');

        self::assertEquals(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($this->securityToken, $object, ['test'])
        );
    }

    public function testVoteForDomainObjectWrapperObject(): void
    {
        $object = new DomainObjectWrapper(new CmsAddress(), new ObjectIdentity('stdClass', 'entity'));

        $this->extensionSelector->expects(self::once())
            ->method('select')
            ->with(self::identicalTo($object))
            ->willReturn($this->extension);

        $this->groupProvider->expects(self::once())
            ->method('getGroup')
            ->willReturn(AclGroupProviderInterface::DEFAULT_SECURITY_GROUP);
        $this->extension->expects(self::once())
            ->method('getPermissions')
            ->with(null, false, true)
            ->willReturn(['test']);

        $this->permissionMap->expects(self::exactly(2))
            ->method('contains')
            ->with('test')
            ->willReturn(true);
        $this->permissionMap->expects(self::once())
            ->method('getMasks')
            ->willReturn(1);

        self::assertEquals(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($this->securityToken, $object, ['test'])
        );
    }

    public function testVoteForDomainObjectWrapperObjectWhenObjectGroupIsNotEqualCurrentGroup(): void
    {
        $object = new DomainObjectWrapper(new CmsAddress(), new ObjectIdentity('stdClass', 'test_group@entity'));

        $this->extensionSelector->expects(self::once())
            ->method('select')
            ->with(self::identicalTo($object))
            ->willReturn($this->extension);

        $this->groupProvider->expects(self::once())
            ->method('getGroup')
            ->willReturn(AclGroupProviderInterface::DEFAULT_SECURITY_GROUP);
        $this->extension->expects(self::never())
            ->method('getPermissions');

        $this->permissionMap->expects(self::never())
            ->method('contains');
        $this->permissionMap->expects(self::never())
            ->method('getMasks');

        self::assertEquals(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($this->securityToken, $object, ['test'])
        );
    }

    public function testVoteForDomainObjectWrapperObjectWhenObjectGroupIsEqualCurrentGroup(): void
    {
        $object = new DomainObjectWrapper(new CmsAddress(), new ObjectIdentity('stdClass', 'test_group@entity'));

        $this->extensionSelector->expects(self::once())
            ->method('select')
            ->with(self::identicalTo($object))
            ->willReturn($this->extension);

        $this->groupProvider->expects(self::once())
            ->method('getGroup')
            ->willReturn('test_group');
        $this->extension->expects(self::once())
            ->method('getPermissions')
            ->with(null, false, true)
            ->willReturn(['test']);

        $this->permissionMap->expects(self::exactly(2))
            ->method('contains')
            ->with('test')
            ->willReturn(true);
        $this->permissionMap->expects(self::once())
            ->method('getMasks')
            ->willReturn(1);

        self::assertEquals(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($this->securityToken, $object, ['test'])
        );
    }

    public function testVoteForDomainObjectWrapperObjectWhenExtensionDoesNotSupportGivenPermission(): void
    {
        $object = new DomainObjectWrapper(new CmsAddress(), new ObjectIdentity('stdClass', 'entity'));

        $this->extensionSelector->expects(self::once())
            ->method('select')
            ->with(self::identicalTo($object))
            ->willReturn($this->extension);

        $this->groupProvider->expects(self::once())
            ->method('getGroup')
            ->willReturn(AclGroupProviderInterface::DEFAULT_SECURITY_GROUP);
        $this->extension->expects(self::once())
            ->method('getPermissions')
            ->with(null, false, true)
            ->willReturn(['test1']);

        $this->permissionMap->expects(self::once())
            ->method('contains')
            ->with('test')
            ->willReturn(true);
        $this->permissionMap->expects(self::never())
            ->method('getMasks');

        self::assertEquals(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($this->securityToken, $object, ['test'])
        );
    }

    public function testVoteForFieldVoteWithObjectAsDomainObject(): void
    {
        $domainObject = new CmsAddress();
        $object = new FieldVote($domainObject, 'testField');

        $this->extensionSelector->expects(self::once())
            ->method('select')
            ->with(self::identicalTo($object))
            ->willReturn($this->extension);
        $this->permissionMap->expects(self::once())
            ->method('contains')
            ->with('test')
            ->willReturn(true);
        $this->permissionMap->expects(self::once())
            ->method('getMasks')
            ->willReturn(1);

        self::assertEquals(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($this->securityToken, $object, ['test'])
        );
    }

    public function testVoteForFieldVoteWithDomainObjectWrapperAsDomainObject(): void
    {
        $address = new CmsAddress();
        $domainObject = new DomainObjectWrapper($address, new ObjectIdentity('stdClass', 'test_group@entity'));
        $object = new FieldVote($domainObject, 'testField');

        $strategy = $this->createMock(ObjectIdentityRetrievalStrategyInterface::class);

        $voter = new AclVoter(
            $this->createMock(AclProviderInterface::class),
            $strategy,
            $this->createMock(SecurityIdentityRetrievalStrategyInterface::class),
            $this->permissionMap
        );
        $voter->setAclExtensionSelector($this->extensionSelector);
        $voter->setAclGroupProvider($this->groupProvider);

        $this->extensionSelector->expects(self::once())
            ->method('select')
            ->with(self::identicalTo($object))
            ->willReturn($this->extension);
        $this->groupProvider->expects(self::once())
            ->method('getGroup')
            ->willReturn('test_group');
        $this->extension->expects(self::once())
            ->method('getPermissions')
            ->with(null, false, true)
            ->willReturn(['test']);
        $strategy->expects($this->once())
            ->method('getObjectIdentity')
            ->with(new DomainObjectWrapper($address, new ObjectIdentity('stdClass', 'entity')));
        $this->permissionMap->expects(self::exactly(2))
            ->method('contains')
            ->with('test')
            ->willReturn(true);
        $this->permissionMap->expects(self::once())
            ->method('getMasks')
            ->willReturn(1);

        self::assertEquals(
            VoterInterface::ACCESS_GRANTED,
            $voter->vote($this->securityToken, $object, ['test'])
        );
    }
}
