<?php

namespace Oro\Bundle\FormBundle\Form\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class FixArrayToStringListener implements EventSubscriberInterface
{
    private $delimiter;

    /**
     * @param string $delimiter
     */
    public function __construct($delimiter)
    {
        $this->delimiter = $delimiter;
    }

    public function preSubmit(FormEvent $event)
    {
        $value = $event->getData();
        if (is_array($value)) {
            $event->setData(implode($this->delimiter, $value));
        }
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return array(FormEvents::PRE_SUBMIT => 'preSubmit');
    }
}
