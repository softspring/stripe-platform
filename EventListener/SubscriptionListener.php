<?php

namespace Softspring\PlatformBundle\Stripe\EventListener;

use Softspring\PlatformBundle\Adapter\SubscriptionAdapterInterface;
use Softspring\PlatformBundle\Manager\AdapterManagerInterface;
use Softspring\SubscriptionBundle\Event\SubscriptionEvent;
use Softspring\SubscriptionBundle\Event\SubscriptionUpgradeEvent;
use Softspring\SubscriptionBundle\SfsSubscriptionEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SubscriptionListener implements EventSubscriberInterface
{
    /**
     * @var AdapterManagerInterface
     */
    protected $adapterManager;

    /**
     * SubscriptionListener constructor.
     *
     * @param AdapterManagerInterface $adapterManager
     */
    public function __construct(AdapterManagerInterface $adapterManager)
    {
        $this->adapterManager = $adapterManager;
    }

    public static function getSubscribedEvents()
    {
        return [
            SfsSubscriptionEvents::SUBSCRIPTION_SUBSCRIBE => 'onSubscribe',
            SfsSubscriptionEvents::SUBSCRIPTION_ADD_PLAN => 'onAddPlan',
            SfsSubscriptionEvents::SUBSCRIPTION_UNSUBSCRIBE => 'onUnsubscribe',
            SfsSubscriptionEvents::SUBSCRIPTION_CANCEL_RENOVATION => 'onCancelRenovation',
            SfsSubscriptionEvents::SUBSCRIPTION_UNCANCEL_RENOVATION => 'onUncancelRenovation',
            SfsSubscriptionEvents::SUBSCRIPTION_UPGRADE => 'onUpgrade',
            SfsSubscriptionEvents::SUBSCRIPTION_CANCEL => 'onCancel',
            SfsSubscriptionEvents::SUBSCRIPTION_SYNC => 'onSync',
        ];
    }

    public function onSubscribe(SubscriptionEvent $event)
    {
        /** @var SubscriptionAdapterInterface $adapter */
        if (! ($adapter = $this->adapterManager->get('stripe', 'subscription'))) {
            return;
        }

        $adapter->create($event->getSubscription());
    }

    public function onUpgrade(SubscriptionUpgradeEvent $event)
    {
        /** @var SubscriptionAdapterInterface $adapter */
        if (! ($adapter = $this->adapterManager->get('stripe', 'subscription'))) {
            return;
        }

        $adapter->upgradePlan($event->getSubscription(), $event->getOldPlan(), $event->getNewPlan());
    }

    public function onAddPlan(SubscriptionEvent $event)
    {
        /** @var SubscriptionAdapterInterface $adapter */
        if (! ($adapter = $this->adapterManager->get('stripe', 'subscription'))) {
            return;
        }

        // TODO
    }

    public function onUnsubscribe(SubscriptionEvent $event)
    {
        /** @var SubscriptionAdapterInterface $adapter */
        if (! ($adapter = $this->adapterManager->get('stripe', 'subscription'))) {
            return;
        }

        // TODO
    }

    public function onCancelRenovation(SubscriptionEvent $event)
    {
        /** @var SubscriptionAdapterInterface $adapter */
        if (! ($adapter = $this->adapterManager->get('stripe', 'subscription'))) {
            return;
        }

        $adapter->cancelRenovation($event->getSubscription());
    }

    public function onUncancelRenovation(SubscriptionEvent $event)
    {
        /** @var SubscriptionAdapterInterface $adapter */
        if (! ($adapter = $this->adapterManager->get('stripe', 'subscription'))) {
            return;
        }

        $adapter->uncancelRenovation($event->getSubscription());
    }

    public function onCancel(SubscriptionEvent $event)
    {
        /** @var SubscriptionAdapterInterface $adapter */
        if (! ($adapter = $this->adapterManager->get('stripe', 'subscription'))) {
            return;
        }

        $adapter->cancel($event->getSubscription());
    }

    public function onSync(SubscriptionEvent $event)
    {
        /** @var SubscriptionAdapterInterface $adapter */
        if (! ($adapter = $this->adapterManager->get('stripe', 'subscription'))) {
            return;
        }

        $adapter->get($event->getSubscription());
    }
}