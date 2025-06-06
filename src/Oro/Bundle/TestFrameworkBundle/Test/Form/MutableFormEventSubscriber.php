<?php

namespace Oro\Bundle\TestFrameworkBundle\Test\Form;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Allows to mock static getSubscribedEvents() function of event listener class
 * Basically should be used with PHPUnit4 as replacement of mockStatic
 */
class MutableFormEventSubscriber implements EventSubscriberInterface
{
    /** @var array */
    protected static $events = [];

    /** @var EventSubscriberInterface */
    protected $wrapped;

    public function __construct(EventSubscriberInterface $wrapped)
    {
        $this->wrapped = $wrapped;
    }

    /**
     * @return array
     */
    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return self::$events;
    }

    public static function setSubscribedEvents(array $events)
    {
        self::$events = $events;
    }

    /**
     * @param $method
     * @param $args
     *
     * @throws \RuntimeException
     * @return mixed
     */
    public function __call($method, $args)
    {
        if (!method_exists($this->wrapped, $method)) {
            throw new \RuntimeException(sprintf('Unknown method %s', $method));
        }

        return call_user_func_array(
            array($this->wrapped, $method),
            $args
        );
    }
}
