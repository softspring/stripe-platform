<?php

namespace Softspring\PlatformBundle\Stripe\EventListener\Webhook;

use Softspring\PlatformBundle\Stripe\Event\StripeWebhookEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class InvoiceItemListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
//            // @see https://stripe.com/docs/api/events/types#event_types-invoiceitem.created
//            // Occurs whenever an invoice item is created.
//            'invoiceitem.created',
//
//            // @see https://stripe.com/docs/api/events/types#event_types-invoiceitem.deleted
//            // Occurs whenever an invoice item is deleted.
//            'invoiceitem.deleted',
//
//            // @see https://stripe.com/docs/api/events/types#event_types-invoiceitem.updated
//            // Occurs whenever an invoice item is updated.
//            'invoiceitem.updated',
        ];
    }
}