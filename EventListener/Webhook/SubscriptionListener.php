<?php

namespace Softspring\PlatformBundle\Stripe\EventListener\Webhook;

use Softspring\PlatformBundle\Model\PlatformObjectInterface;
use Softspring\PlatformBundle\Stripe\Event\StripeWebhookEvent;
use Softspring\PlatformBundle\Stripe\Transformer\SubscriptionTransformer;
use Softspring\SubscriptionBundle\Manager\SubscriptionManagerInterface;
use Softspring\SubscriptionBundle\Model\SubscriptionInterface;
use Softspring\SubscriptionBundle\Model\SubscriptionMultiPlanInterface;
use Stripe\Event;
use Stripe\Subscription;
use Stripe\SubscriptionSchedule;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SubscriptionListener implements EventSubscriberInterface
{
    /**
     * @var SubscriptionManagerInterface
     */
    protected $subscriptionManager;

    /**
     * @var SubscriptionTransformer
     */
    protected $subscriptionTransformer;

    /**
     * SubscriptionListener constructor.
     *
     * @param SubscriptionManagerInterface $subscriptionManager
     * @param SubscriptionTransformer      $subscriptionTransformer
     */
    public function __construct(SubscriptionManagerInterface $subscriptionManager, SubscriptionTransformer $subscriptionTransformer)
    {
        $this->subscriptionManager = $subscriptionManager;
        $this->subscriptionTransformer = $subscriptionTransformer;
    }

    public static function getSubscribedEvents()
    {
        return [
            // @see https://stripe.com/docs/api/events/types#event_types-customer.subscription.created
            // data.object is a subscription
            // Occurs whenever a customer is signed up for a new plan.
            'sfs_platform.stripe_webhook.customer.subscription.created' => [['onSubscriptionCreateOrUpdate', 0]],

            // @see https://stripe.com/docs/api/events/types#event_types-customer.subscription.deleted
            // data.object is a subscription
            // Occurs whenever a customer's subscription ends.
            'sfs_platform.stripe_webhook.customer.subscription.deleted' => [['onSubscriptionCreateOrUpdate', 0]],

            // @see https://stripe.com/docs/api/events/types#event_types-customer.subscription.pending_update_applied
            // data.object is a subscription
            // Occurs whenever a customer's subscription's pending update is applied, and the subscription is updated.
            'sfs_platform.stripe_webhook.customer.subscription.pending_update_applied' => [['onSubscriptionCreateOrUpdate', 0]],

            // @see https://stripe.com/docs/api/events/types#event_types-customer.subscription.pending_update_expired
            // data.object is a subscription
            // Occurs whenever a customer's subscription's pending update expires before the related invoice is paid.
            'sfs_platform.stripe_webhook.customer.subscription.pending_update_expired' => [['onSubscriptionCreateOrUpdate', 0]],

            // @see https://stripe.com/docs/api/events/types#event_types-customer.subscription.trial_will_end
            // data.object is a subscription
            // Occurs three days before a subscription's trial period is scheduled to end, or when a trial is ended immediately (using trial_end=now).
            'sfs_platform.stripe_webhook.customer.subscription.trial_will_end' => [['onSubscriptionCreateOrUpdate', 0]],

            // @see https://stripe.com/docs/api/events/types#event_types-customer.subscription.updated
            // data.object is a subscription
            // Occurs whenever a subscription changes (e.g., switching from one plan to another, or changing the status from trial to active).
            'sfs_platform.stripe_webhook.customer.subscription.updated' => [['onSubscriptionCreateOrUpdate', 0]],

//            // @see https://stripe.com/docs/api/events/types#event_types-subscription_schedule.aborted
//            // data.object is a subscription schedule
//            // Occurs whenever a subscription schedule is canceled due to the underlying subscription being canceled because of delinquency.
//            'sfs_platform.stripe_webhook.subscription_schedule.aborted' => [['onSubscriptionSchedule', 0]],
//
//            // @see https://stripe.com/docs/api/events/types#event_types-subscription_schedule.canceled
//            // data.object is a subscription schedule
//            // Occurs whenever a subscription schedule is canceled.
//            'sfs_platform.stripe_webhook.subscription_schedule.canceled' => [['onSubscriptionSchedule', 0]],
//
//            // @see https://stripe.com/docs/api/events/types#event_types-subscription_schedule.completed
//            // data.object is a subscription schedule
//            // Occurs whenever a new subscription schedule is completed.
//            'sfs_platform.stripe_webhook.subscription_schedule.completed' => [['onSubscriptionSchedule', 0]],
//
//            // @see https://stripe.com/docs/api/events/types#event_types-subscription_schedule.created
//            // data.object is a subscription schedule
//            // Occurs whenever a new subscription schedule is created.
//            'sfs_platform.stripe_webhook.subscription_schedule.created' => [['onSubscriptionSchedule', 0]],
//
//            // @see https://stripe.com/docs/api/events/types#event_types-subscription_schedule.expiring
//            // data.object is a subscription schedule
//            // Occurs 7 days before a subscription schedule will expire.
//            'sfs_platform.stripe_webhook.subscription_schedule.expiring' => [['onSubscriptionSchedule', 0]],
//
//            // @see https://stripe.com/docs/api/events/types#event_types-subscription_schedule.released
//            // data.object is a subscription schedule
//            // Occurs whenever a new subscription schedule is released.
//            'sfs_platform.stripe_webhook.subscription_schedule.released' => [['onSubscriptionSchedule', 0]],
//
//            // @see https://stripe.com/docs/api/events/types#event_types-subscription_schedule.updated
//            // data.object is a subscription schedule
//            // Occurs whenever a subscription schedule is updated.
//            'sfs_platform.stripe_webhook.subscription_schedule.updated' => [['onSubscriptionSchedule', 0]],
        ];
    }

    public function onSubscriptionCreateOrUpdate(StripeWebhookEvent $event)
    {
        /** @var Event $stripeEvent */
        $stripeEvent = $event->getData();
        /** @var Subscription $stripeSubscription */
        $stripeSubscription = $stripeEvent->data->object;

        /** @var SubscriptionInterface|PlatformObjectInterface $dbSubscription */
        if (! ($dbSubscription = $this->subscriptionManager->getRepository()->findOneByPlatformId($stripeSubscription->id))) {
            $dbSubscription = $this->subscriptionManager->createEntity();
        }

        $this->subscriptionTransformer->reverseTransform($stripeSubscription, $dbSubscription);
        $dbSubscription->setPlatformWebhooked(true);

        if ($dbSubscription instanceof SubscriptionMultiPlanInterface) {
            foreach ($dbSubscription->getItems() as $dbSubscriptionItem) {
                $dbSubscriptionItem->setPlatformWebhooked(true);
            }
        }
        $this->subscriptionManager->saveEntity($dbSubscription);
    }

    public function onSubscriptionDeleted(StripeWebhookEvent $event)
    {
        /** @var Event $stripeEvent */
        $stripeEvent = $event->getData();
        /** @var Subscription $stripeSubscription */
        $stripeSubscription = $stripeEvent->data->object;

        /** @var SubscriptionInterface|PlatformObjectInterface $dbSubscription */
        if (! ($dbSubscription = $this->subscriptionManager->getRepository()->findOneByPlatformId($stripeSubscription->id))) {
            return;
        }

        $this->subscriptionManager->deleteEntity($dbSubscription);
    }

    public function onSubscriptionSchedule(StripeWebhookEvent $event)
    {
        /** @var Event $stripeEvent */
        $stripeEvent = $event->getData();
        /** @var SubscriptionSchedule $stripeSubscriptionSchedule */
        $stripeSubscriptionSchedule = $stripeEvent->data->object;
    }
}