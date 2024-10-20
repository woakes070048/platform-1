<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Context;

use Behat\Mink\Mink;

/**
 * Provides possibility to work with multiple sessions in one behat feature.
 */
class SessionAliasProvider implements MultiSessionAwareInterface
{
    /**
     * @var array|string[]
     */
    private $aliases = [];

    /**
     * @var array
     */
    private $data = [];

    /**
     * @param Mink $mink
     * @param string $sessionName
     * @param string $alias
     */
    public function setSessionAlias(Mink $mink, $sessionName, $alias)
    {
        if (!$mink->hasSession($sessionName)) {
            throw new \RuntimeException(
                sprintf(
                    'Can not register alias `%s` for session `%s` as the session does not exists',
                    $alias,
                    $sessionName
                )
            );
        }
        $this->aliases[$alias] = $sessionName;
    }

    /**
     * @param Mink $mink
     * @param string $alias
     */
    public function switchSessionByAlias(Mink $mink, $alias)
    {
        if ($this->hasRegisteredAlias($alias)) {
            $sessionName = $this->getSessionName($alias);

            $this->switchSession($mink, $sessionName);
        } else {
            throw new \RuntimeException(
                sprintf('Alias `%s` for session is not defined', $alias)
            );
        }
    }

    /**
     * @param Mink $mink
     * @param string $sessionName
     */
    public function switchSession(Mink $mink, $sessionName)
    {
        $mink->setDefaultSessionName($sessionName);

        $session = $mink->getSession($sessionName);
        // start session if needed
        if (!$session->isStarted()) {
            $session->start();
        }

        $session->switchToWindow(0);
    }

    /**
     * @param string $alias
     * @return mixed
     * @throws \OutOfBoundsException
     */
    #[\Override]
    public function getSessionName($alias)
    {
        if (isset($this->aliases[$alias])) {
            return $this->aliases[$alias];
        }

        throw new \OutOfBoundsException(
            sprintf('Unknown session alias `%s`', $alias)
        );
    }

    /**
     * @param string $alias
     * @return bool
     */
    #[\Override]
    public function hasRegisteredAlias($alias)
    {
        return isset($this->aliases[$alias]);
    }

    /**
     * @return array|string[]
     */
    #[\Override]
    public function getAliases()
    {
        return $this->aliases;
    }

    /**
     * @param string $sessionAlias
     * @param string $key
     * @param mixed $value
     */
    #[\Override]
    public function saveSessionValue($sessionAlias, $key, $value)
    {
        $sessionName = $this->getSessionName($sessionAlias);

        if (!isset($this->data[$sessionName])) {
            $this->data[$sessionName] = [];
        }

        $this->data[$sessionName][$key] = $value;
    }

    /**
     * @param string $sessionAlias
     * @param string $key
     * @param null|mixed $default
     * @return mixed
     */
    #[\Override]
    public function getSessionValue($sessionAlias, $key, $default = null)
    {
        $sessionName = $this->getSessionName($sessionAlias);

        if (isset($this->data[$sessionName][$key])) {
            return $this->data[$sessionName][$key];
        }

        return $default;
    }
}
