<?php

namespace Softspring\PlatformBundle\Stripe\EventListener\Webhook;

use Softspring\PaymentBundle\Manager\PaymentManagerInterface;
use Softspring\PaymentBundle\Model\PaymentInterface;
use Softspring\PlatformBundle\Model\PlatformObjectInterface;
use Softspring\PlatformBundle\Stripe\Event\StripeWebhookEvent;
use Softspring\PlatformBundle\Stripe\Transformer\PaymentTransformer;
use Stripe\Event;
use Stripe\Charge;
use Stripe\Refund;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PaymentListener implements EventSubscriberInterface
{
    /**
     * @var PaymentManagerInterface
     */
    protected $paymentManager;

    /**
     * @var PaymentTransformer
     */
    protected $paymentTransformer;

    /**
     * PaymentListener constructor.
     *
     * @param PaymentManagerInterface $paymentManager
     * @param PaymentTransformer      $paymentTransformer
     */
    public function __construct(PaymentManagerInterface $paymentManager, PaymentTransformer $paymentTransformer)
    {
        $this->paymentManager = $paymentManager;
        $this->paymentTransformer = $paymentTransformer;
    }

    public static function getSubscribedEvents()
    {
        return [

            // @see https://stripe.com/docs/api/events/types#event_types-charge.captured
            // data.object is a charge
            // Occurs whenever a previously uncaptured charge is captured.
            'sfs_platform.stripe_webhook.charge.captured' => [['onPaymentCreateOrUpdate', 0]],

            // @see https://stripe.com/docs/api/events/types#event_types-
            // data.object is a chargehttps://stripe.com/do
            'sfs_platform.stripe_webhook.charge.expired' => [['onPaymentCreateOrUpdate', 0]],

            // @see https://stripe.com/docs/api/events/types#event_types-charge.failed
            // data.object is a charge
            // Occurs whenever a failed charge attempt occurs.
            'sfs_platform.stripe_webhook.charge.failed' => [['onPaymentCreateOrUpdate', 0]],

            // @see https://stripe.com/docs/api/events/types#event_types-charge.pending
            // data.object is a charge
            // Occurs whenever a pending charge is created.
            'sfs_platform.stripe_webhook.charge.pending' => [['onPaymentCreateOrUpdate', 0]],

            // @see https://stripe.com/docs/api/events/types#event_types-charge.refunded
            // data.object is a charge
            // Occurs whenever a charge is refunded, including partial refunds.
            // 'sfs_platform.stripe_webhook.charge.refunded' => [['onPaymentCreateOrUpdate', 0]],

            // @see https://stripe.com/docs/api/events/types#event_types-charge.succeeded
            // data.object is a charge
            // Occurs whenever a new charge is created and is successful.
            'sfs_platform.stripe_webhook.charge.succeeded' => [['onPaymentCreateOrUpdate', 0]],

            // @see https://stripe.com/docs/api/events/types#event_types-charge.updated
            // data.object is a charge
            // Occurs whenever a charge description or metadata is updated.
            'sfs_platform.stripe_webhook.charge.updated' => [['onPaymentCreateOrUpdate', 0]],

            // @see https://stripe.com/docs/api/events/types#event_types-stripe_webhook.dispute.closed
            // data.object is a dispute
            // Occurs when a dispute is closed and the dispute status changes to lost, warning_closed, or won.
            // 'sfs_platform.stripe_webhook.charge.dispute.closed' => [['onPaymentCreateOrUpdate', 0]],

            // @see https://stripe.com/docs/api/events/types#event_types-stripe_webhook.dispute.created
            // data.object is a dispute
            // Occurs whenever a payment disputes a charge with their bank.
            // 'sfs_platform.stripe_webhook.charge.dispute.created' => [['onPaymentCreateOrUpdate', 0]],

            // @see https://stripe.com/docs/api/events/types#event_types-stripe_webhook.dispute.funds_reinstated
            // data.object is a dispute
            // Occurs when funds are reinstated to your account after a dispute is closed. This includes partially refunded payments.
            // 'sfs_platform.stripe_webhook.charge.dispute.funds_reinstated' => [['onPaymentCreateOrUpdate', 0]],

            // @see https://stripe.com/docs/api/events/types#event_types-stripe_webhook.dispute.funds_withdrawn
            // data.object is a dispute
            // Occurs when funds are removed from your account due to a dispute.
            // 'sfs_platform.stripe_webhook.charge.dispute.funds_withdrawn' => [['onPaymentCreateOrUpdate', 0]],

            // @see https://stripe.com/docs/api/events/types#event_types-stripe_webhook.dispute.updated
            // data.object is a dispute
            // Occurs when the dispute is updated (usually with evidence).
            // 'sfs_platform.stripe_webhook.charge.dispute.updated' => [['onPaymentCreateOrUpdate', 0]],

            // @see https://stripe.com/docs/api/events/types#event_types-stripe_webhook.refund.updated
            // data.object is a refund
            // Occurs whenever a refund is updated, on selected payment methods.
            // 'sfs_platform.stripe_webhook.charge.refund.updated' => [['onPaymentCreateOrUpdate', 0]],
        ];
    }

    public function onPaymentCreateOrUpdate(StripeWebhookEvent $event)
    {
        /** @var Event $stripeEvent */
        $stripeEvent = $event->getData();
        /** @var Charge|Refund $payment */
        $stripePayment = $stripeEvent->data->object;

        /** @var PaymentInterface|PlatformObjectInterface $dbPayment */
        if (! ($dbPayment = $this->paymentManager->getRepository()->findOneByPlatformId($stripePayment->id))) {
            $dbPayment = $this->paymentManager->createEntity();
        }

        $this->paymentTransformer->reverseTransform($stripePayment, $dbPayment);
        $dbPayment->setPlatformWebhooked(true);
        $this->paymentManager->saveEntity($dbPayment);
    }

    public function onPaymentDeleted(StripeWebhookEvent $event)
    {
        /** @var Event $stripeEvent */
        $stripeEvent = $event->getData();
        /** @var Charge|Refund $payment */
        $stripePayment = $stripeEvent->data->object;

        /** @var PaymentInterface|PlatformObjectInterface $dbPayment */
        if (! ($dbPayment = $this->paymentManager->getRepository()->findOneByPlatformId($stripePayment->id))) {
            return;
        }

        $this->paymentManager->deleteEntity($dbPayment);
    }
}