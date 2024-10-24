<?php

namespace Oro\Bundle\SecurityBundle\Acl\Extension;

use Oro\Bundle\SecurityBundle\Acl\Exception\InvalidAclMaskException;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * The base class for ACL extensions.
 */
abstract class AbstractAclExtension implements AclExtensionInterface
{
    /** @var array [permission => [mask, ...], ...] */
    protected $map;

    #[\Override]
    public function getFieldExtension()
    {
        return null;
    }

    #[\Override]
    public function getMasks($permission)
    {
        return $this->map[$permission] ?? null;
    }

    #[\Override]
    public function hasMasks($permission)
    {
        return \array_key_exists($permission, $this->map);
    }

    #[\Override]
    public function adaptRootMask($rootMask, $object)
    {
        return $rootMask;
    }

    #[\Override]
    public function getDefaultPermission()
    {
        return '';
    }

    #[\Override]
    public function decideIsGranting($triggeredMask, $object, TokenInterface $securityToken)
    {
        return true;
    }

    /**
     * Split the given object identity descriptor
     *
     * @param string $descriptor The ACL descriptor to parse
     * @param string $type       [output]
     * @param string $id         [output]
     * @param string $group      [output]
     *
     * @throws \InvalidArgumentException
     */
    protected function parseDescriptor($descriptor, &$type, &$id, &$group)
    {
        $delim = strpos($descriptor, ObjectIdentityHelper::IDENTITY_TYPE_DELIMITER);
        if (!$delim) {
            throw new \InvalidArgumentException(
                sprintf('Incorrect descriptor: %s. Expected "ExtensionKey:Class".', $descriptor)
            );
        }

        $id = strtolower(substr($descriptor, 0, $delim));
        list($type, $group) = ObjectIdentityHelper::parseType(ltrim(substr($descriptor, $delim + 1), ' '));
    }

    /**
     * Builds InvalidAclMaskException object
     *
     * @param int         $mask
     * @param mixed       $object
     * @param string|null $errorDescription
     *
     * @return InvalidAclMaskException
     */
    protected function createInvalidAclMaskException($mask, $object, $errorDescription = null)
    {
        $objectDescription = \is_object($object) && !($object instanceof ObjectIdentityInterface)
            ? \get_class($object)
            : (string)$object;
        $msg = sprintf(
            'Invalid ACL mask "%s" for %s.',
            $this->getMaskPattern($mask),
            $objectDescription
        );
        if (!empty($errorDescription)) {
            $msg = sprintf('%s %s', $errorDescription, $msg);
        }

        return new InvalidAclMaskException($msg);
    }
}
