<?php

namespace Oro\Bundle\DashboardBundle\Form\EventListener;

use Oro\Bundle\DashboardBundle\Model\WidgetConfigs;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Prepare data for form based on widgetDataItems attribute.
 */
class WidgetItemsFormSubscriber implements EventSubscriberInterface
{
    /** @var WidgetConfigs $manager */
    protected $widgetConfigs;

    /** @var TranslatorInterface */
    protected $translator;

    public function __construct(WidgetConfigs $widgetConfigs, TranslatorInterface $translator)
    {
        $this->widgetConfigs = $widgetConfigs;
        $this->translator    = $translator;
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSet',
        ];
    }

    public function preSet(FormEvent $event)
    {
        $widgetName   = $event->getForm()->getConfig()->getOption('widget_name');
        $attributes   = $this->widgetConfigs->getWidgetAttributesForTwig($widgetName);
        $dataItems    = $attributes['widgetDataItems'];
        $originalData = $this->getIndexedData($event->getData());

        $data  = [];
        $order = 1;
        foreach ($dataItems as $id => $item) {
            $oldItem = isset($originalData[$id]) ? $originalData[$id] : null;

            $data[$id] = [
                'id'    => $id,
                'label' => isset($item['label']) ? $this->translator->trans((string) $item['label']) : '',
                'show'  => $oldItem ? $oldItem['show'] : !$originalData,
                'order' => $oldItem ? $oldItem['order'] : $order,
            ];

            $order++;
        }

        usort($data, function ($a, $b) {
            return $a['order'] - $b['order'];
        });

        $event->setData(['items' => array_values($data)]);
    }

    /**
     * @param array|null $data
     *
     * @return array
     */
    protected function getIndexedData(?array $data = null)
    {
        $result = [];

        if (!$data) {
            return $result;
        }

        foreach ($data['items'] as $item) {
            $result[$item['id']] = $item;
        }

        return $result;
    }
}
