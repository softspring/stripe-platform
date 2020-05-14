<?php

namespace Softspring\PlatformBundle\Stripe\EventListener\Webhook;

use Softspring\CustomerBundle\Manager\CustomerManagerInterface;
use Softspring\CustomerBundle\Model\CustomerInterface;
use Softspring\PlatformBundle\Model\PlatformObjectInterface;
use Softspring\PlatformBundle\Stripe\Event\StripeWebhookEvent;
use Softspring\PlatformBundle\Stripe\Transformer\CustomerTransformer;
use Stripe\Event;
use Stripe\Customer;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CustomerListener implements EventSubscriberInterface
{
    /**
     * @var CustomerManagerInterface
     */
    protected $customerManager;

    /**
     * @var CustomerTransformer
     */
    protected $customerTransformer;

    /**
     * CustomerListener constructor.
     *
     * @param CustomerManagerInterface $customerManager
     * @param CustomerTransformer      $customerTransformer
     */
    public function __construct(CustomerManagerInterface $customerManager, CustomerTransformer $customerTransformer)
    {
        $this->customerManager = $customerManager;
        $this->customerTransformer = $customerTransformer;
    }

    public static function getSubscribedEvents()
    {
        return [
            // @see https://stripe.com/docs/api/events/types#event_types-customer.created
            // data.object is a customer
            // Occurs whenever a new customer is created.
            'sfs_platform.stripe_webhook.customer.created' => [['onCustomerCreateOrUpdate', 0]],

            // @see https://stripe.com/docs/api/events/types#event_types-customer.deleted
            // data.object is a customer
            // Occurs whenever a customer is deleted.
            'sfs_platform.stripe_webhook.customer.deleted' => [['onCustomerDeleted', 0]],

            // @see https://stripe.com/docs/api/events/types#event_types-customer.updated
            // data.object is a customer
            // Occurs whenever any property of a customer changes.
            'sfs_platform.stripe_webhook.customer.updated' => [['onCustomerCreateOrUpdate', 0]],

            // @see https://stripe.com/docs/api/events/types#event_types-customer.discount.created
            // data.object is a discount
            // Occurs whenever a coupon is attached to a customer.
            // 'sfs_platform.stripe_webhook.customer.discount.created' => [[]],

            // @see https://stripe.com/docs/api/events/types#event_types-customer.discount.deleted
            // data.object is a discount
            // Occurs whenever a coupon is removed from a customer.
            // 'sfs_platform.stripe_webhook.customer.discount.deleted' => [[]],

            // @see https://stripe.com/docs/api/events/types#event_types-customer.discount.updated
            // data.object is a discount
            // Occurs whenever a customer is switched from one coupon to another.
            // 'sfs_platform.stripe_webhook.customer.discount.updated' => [[]],

            // @see https://stripe.com/docs/api/events/types#event_types-customer.source.created
            // data.object is a source (e.g., card)
            // Occurs whenever a new source is created for a customer.
            // 'sfs_platform.stripe_webhook.customer.source.created' => [[]],

            // @see https://stripe.com/docs/api/events/types#event_types-customer.source.deleted
            // data.object is a source (e.g., card)
            // Occurs whenever a source is removed from a customer.
            // 'sfs_platform.stripe_webhook.customer.source.deleted' => [[]],

            // @see https://stripe.com/docs/api/events/types#event_types-customer.source.expiring
            // data.object is a source (e.g., card)
            // Occurs whenever a card or source will expire at the end of the month.
            // 'sfs_platform.stripe_webhook.customer.source.expiring' => [[]],

            // @see https://stripe.com/docs/api/events/types#event_types-customer.source.updated
            // data.object is a source (e.g., card)
            // Occurs whenever a source's details are changed.
            // 'sfs_platform.stripe_webhook.customer.source.updated' => [[]],

            // @see https://stripe.com/docs/api/events/types#event_types-customer.subscription.created
            // ,data.object is a subscription
            // Occurs whenever a customer is signed up for a new plan.
            // 'sfs_platform.stripe_webhook.customer.subscription.created' => [[]],

            // @see https://stripe.com/docs/api/events/types#event_types-customer.subscription.deleted
            // data.object is a subscription
            // Occurs whenever a customer's subscription ends.
            // 'sfs_platform.stripe_webhook.customer.subscription.deleted' => [[]],

            // @see https://stripe.com/docs/api/events/types#event_types-customer.subscription.pending_update_applied
            // ,data.object is a subscription
            // Occurs whenever a customer's subscription's pending update is applied, and the subscription is updated.
            // 'sfs_platform.stripe_webhook.customer.subscription.pending_update_applied' => [[]],

            // @see https://stripe.com/docs/api/events/types#event_types-customer.subscription.pending_update_expired
            // data.object is a subscription
            // Occurs whenever a customer's subscription's pending update expires before the related invoice is paid.
            // 'sfs_platform.stripe_webhook.customer.subscription.pending_update_expired' => [[]],

            // @see https://stripe.com/docs/api/events/types#event_types-customer.subscription.trial_will_end
            // data.object is a subscription
            // Occurs three days before a subscription's trial period is scheduled to end, or when a trial is ended immediately (using trial_end=now).
            // 'sfs_platform.stripe_webhook.customer.subscription.trial_will_end' => [[]],

            // @see https://stripe.com/docs/api/events/types#event_types-customer.subscription.updated
            // ,data.object is a subscription
            // Occurs whenever a subscription changes (e.g., switching from one plan to another, or changing the status from trial to active).
            // 'sfs_platform.stripe_webhook.customer.subscription.updated' => [[]],

            // @see https://stripe.com/docs/api/events/types#event_types-customer.tax_id.created
            // data.object is a tax id
            // Occurs whenever a tax ID is created for a customer.
            // 'sfs_platform.stripe_webhook.customer.tax_id.created' => [[]],

            // @see https://stripe.com/docs/api/events/types#event_types-customer.tax_id.deleted
            // data.object is a tax id
            // Occurs whenever a tax ID is deleted from a customer.
            // 'sfs_platform.stripe_webhook.customer.tax_id.deleted' => [[]],

            // @see https://stripe.com/docs/api/events/types#event_types-customer.tax_id.updated
            // data.object is a tax id
            // Occurs whenever a customer's tax ID is updated.
            // 'sfs_platform.stripe_webhook.customer.tax_id.updated' => [[]],
        ];
    }

    public function onCustomerCreateOrUpdate(StripeWebhookEvent $event)
    {
        /** @var Event $stripeEvent */
        $stripeEvent = $event->getData();
        /** @var Customer $customer */
        $stripeCustomer = $stripeEvent->data->object;

        /** @var CustomerInterface|PlatformObjectInterface $dbCustomer */
        if (! ($dbCustomer = $this->customerManager->getRepository()->findOneByPlatformId($stripeCustomer->id))) {
            $dbCustomer = $this->customerManager->createEntity();
        }

        $this->customerTransformer->reverseTransform($stripeCustomer, $dbCustomer);
        $dbCustomer->setPlatformWebhooked(true);
        $this->customerManager->saveEntity($dbCustomer);
    }

    public function onCustomerDeleted(StripeWebhookEvent $event)
    {
        /** @var Event $stripeEvent */
        $stripeEvent = $event->getData();
        /** @var Customer $customer */
        $stripeCustomer = $stripeEvent->data->object;

        /** @var CustomerInterface|PlatformObjectInterface $dbCustomer */
        if (! ($dbCustomer = $this->customerManager->getRepository()->findOneByPlatformId($stripeCustomer->id))) {
            return;
        }

        $this->customerManager->deleteEntity($dbCustomer);
    }
}