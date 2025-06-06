<?php

namespace Oro\Bundle\AddressBundle\Entity;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\FormBundle\Entity\EmptyItem;
use Oro\Bundle\FormBundle\Entity\PrimaryItem;

/**
* AbstractEmail abstract class
*
*/
#[ORM\MappedSuperclass]
abstract class AbstractEmail implements PrimaryItem, EmptyItem
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'email', type: Types::STRING, length: 255, nullable: false)]
    #[ConfigField(defaultValues: ['importexport' => ['identity' => true], 'dataaudit' => ['auditable' => true]])]
    protected ?string $email = null;

    #[ORM\Column(name: 'is_primary', type: Types::BOOLEAN, nullable: true)]
    protected ?bool $primary = null;

    /**
     * @param string|null $email
     */
    public function __construct($email = null)
    {
        $this->email = $email;
        $this->primary = false;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set id
     *
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return AbstractEmail
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param bool $primary
     * @return AbstractEmail
     */
    #[\Override]
    public function setPrimary($primary)
    {
        $this->primary = (bool)$primary;

        return $this;
    }

    /**
     * @return bool
     */
    #[\Override]
    public function isPrimary()
    {
        return $this->primary;
    }

    /**
     * @return string
     */
    #[\Override]
    public function __toString()
    {
        return (string)$this->getEmail();
    }

    /**
     * Check if entity is empty.
     *
     * @return bool
     */
    #[\Override]
    public function isEmpty()
    {
        return empty($this->email);
    }

    /**
     * @param mixed $other
     * @return bool
     */
    public function isEqual($other)
    {
        $class = ClassUtils::getClass($this);

        /** @var AbstractAddress $other */
        if (!$other instanceof $class) {
            return false;
        } elseif ($this->getId() && $other->getId()) {
            return $this->getId() == $other->getId();
        } elseif ($this->getId() || $other->getId()) {
            return false;
        } else {
            return $this == $other;
        }
    }
}
