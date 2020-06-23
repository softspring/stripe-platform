<?php

namespace Softspring\PlatformBundle\Stripe\EventListener;

use Softspring\PlatformBundle\Adapter\InvoiceAdapterInterface;
use Softspring\PlatformBundle\Manager\AdapterManagerInterface;
use Softspring\PaymentBundle\Event\InvoiceEvent;
use Softspring\PaymentBundle\SfsPaymentEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class InvoiceListener implements EventSubscriberInterface
{
    /**
     * @var AdapterManagerInterface
     */
    protected $adapterManager;

    /**
     * InvoiceListener constructor.
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
            SfsPaymentEvents::INVOICE_SYNC => [['onSync', 255]],
        ];
    }

    public function onSync(InvoiceEvent $event)
    {
        /** @var InvoiceAdapterInterface $adapter */
        if (! ($adapter = $this->adapterManager->get('stripe', 'invoice'))) {
            return;
        }

        $adapter->get($event->getInvoice());
    }
}