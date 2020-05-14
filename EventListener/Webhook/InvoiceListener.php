<?php

namespace Softspring\PlatformBundle\Stripe\EventListener\Webhook;

use Softspring\PaymentBundle\Manager\InvoiceManagerInterface;
use Softspring\PaymentBundle\Model\InvoiceInterface;
use Softspring\PlatformBundle\Model\PlatformObjectInterface;
use Softspring\PlatformBundle\Stripe\Event\StripeWebhookEvent;
use Softspring\PlatformBundle\Stripe\Transformer\InvoiceTransformer;
use Stripe\Event;
use Stripe\Invoice;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class InvoiceListener implements EventSubscriberInterface
{
    /**
     * @var InvoiceManagerInterface
     */
    protected $invoiceManager;

    /**
     * @var InvoiceTransformer
     */
    protected $invoiceTransformer;

    /**
     * InvoiceListener constructor.
     *
     * @param InvoiceManagerInterface $invoiceManager
     * @param InvoiceTransformer      $invoiceTransformer
     */
    public function __construct(InvoiceManagerInterface $invoiceManager, InvoiceTransformer $invoiceTransformer)
    {
        $this->invoiceManager = $invoiceManager;
        $this->invoiceTransformer = $invoiceTransformer;
    }

    public static function getSubscribedEvents()
    {
        return [
            // @see https://stripe.com/docs/api/events/types#event_types-invoice.created
            // Occurs whenever a new invoice is created. To learn how webhooks can be used with this event, and how
            // they can affect it, see Using Webhooks with Subscriptions.
            'sfs_platform.stripe_webhook.invoice.created' => [['onInvoiceCreateOrUpdate', 0]],

            // @see https://stripe.com/docs/api/events/types#event_types-invoice.deleted
            // Occurs whenever a draft invoice is deleted.
            'sfs_platform.stripe_webhook.invoice.deleted' => [['onInvoiceDeleted', 0]],

            // @see https://stripe.com/docs/api/events/types#event_types-invoice.finalized
            // Occurs whenever a draft invoice is finalized and updated to be an open invoice.
            'sfs_platform.stripe_webhook.invoice.finalized' => [['onInvoiceCreateOrUpdate', 0]],

            // @see https://stripe.com/docs/api/events/types#event_types-invoice.marked_uncollectible
            // Occurs whenever an invoice is marked uncollectible.
            'sfs_platform.stripe_webhook.invoice.marked_uncollectible' => [['onInvoiceCreateOrUpdate', 0]],

            // @see https://stripe.com/docs/api/events/types#event_types-invoice.payment_failed
            // Occurs whenever an invoice payment attempt fails, due either to a declined payment or to the lack of a
            // stored payment method.
            'sfs_platform.stripe_webhook.invoice.payment_failed' => [['onInvoiceCreateOrUpdate', 0]],

            // @see https://stripe.com/docs/api/events/types#event_types-invoice.payment_succeeded
            // Occurs whenever an invoice payment attempt succeeds.
            'sfs_platform.stripe_webhook.invoice.payment_succeeded' => [['onInvoiceCreateOrUpdate', 0]],

            // @see https://stripe.com/docs/api/events/types#event_types-invoice.sent
            // Occurs whenever an invoice email is sent out.
            'sfs_platform.stripe_webhook.invoice.sent' => [['onInvoiceCreateOrUpdate', 0]],

            // @see https://stripe.com/docs/api/events/types#event_types-invoice.updated
            // Occurs whenever an invoice changes (e.g., the invoice amount).
            'sfs_platform.stripe_webhook.invoice.updated' => [['onInvoiceCreateOrUpdate', 0]],

            // @see https://stripe.com/docs/api/events/types#event_types-invoice.voided
            // Occurs whenever an invoice is voided.
            'sfs_platform.stripe_webhook.invoice.voided' => [['onInvoiceCreateOrUpdate', 0]],

//            // @see https://stripe.com/docs/api/events/types#event_types-invoice.payment_action_required
//            // Occurs whenever an invoice payment attempt requires further user action to complete.
//            'sfs_platform.stripe_webhook.invoice.payment_action_required' => [['', 0]],

//            // @see https://stripe.com/docs/api/events/types#event_types-invoice.upcoming
//            // Occurs X number of days before a subscription is scheduled to create an invoice that is automatically
//            // charged—where X is determined by your subscriptions settings. Note: The received Invoice object will
//            // not have an invoice ID.
//            'sfs_platform.stripe_webhook.invoice.upcoming' => [['', 0]],
        ];
    }

    public function onInvoiceCreateOrUpdate(StripeWebhookEvent $event)
    {
        /** @var Event $stripeEvent */
        $stripeEvent = $event->getData();
        /** @var Invoice $invoice */
        $stripeInvoice = $stripeEvent->data->object;

        /** @var InvoiceInterface|PlatformObjectInterface $dbInvoice */
        if (! ($dbInvoice = $this->invoiceManager->getRepository()->findOneByPlatformId($stripeInvoice->id))) {
            $dbInvoice = $this->invoiceManager->createEntity();
        }

        $this->invoiceTransformer->reverseTransform($stripeInvoice, $dbInvoice);
        $dbInvoice->setPlatformWebhooked(true);
        $this->invoiceManager->saveEntity($dbInvoice);
    }

    public function onInvoiceDeleted(StripeWebhookEvent $event)
    {
        /** @var Event $stripeEvent */
        $stripeEvent = $event->getData();
        /** @var Invoice $invoice */
        $stripeInvoice = $stripeEvent->data->object;

        /** @var InvoiceInterface|PlatformObjectInterface $dbInvoice */
        if (! ($dbInvoice = $this->invoiceManager->getRepository()->findOneByPlatformId($stripeInvoice->id))) {
            return;
        }

        $this->invoiceManager->deleteEntity($dbInvoice);
    }
}