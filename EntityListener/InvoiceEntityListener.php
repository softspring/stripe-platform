<?php

namespace Softspring\PlatformBundle\Stripe\EntityListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Softspring\PaymentBundle\Model\InvoiceInterface;
use Softspring\PlatformBundle\Exception\NotFoundInPlatform;
use Softspring\PlatformBundle\Model\PlatformObjectInterface;
use Softspring\PlatformBundle\Stripe\Adapter\InvoiceAdapter;

class InvoiceEntityListener
{
    /**
     * @var InvoiceAdapter
     */
    protected $invoiceAdapter;

    /**
     * InvoiceEntityListener constructor.
     *
     * @param InvoiceAdapter $invoiceAdapter
     */
    public function __construct(InvoiceAdapter $invoiceAdapter)
    {
        $this->invoiceAdapter = $invoiceAdapter;
    }

    /**
     * @param InvoiceInterface|PlatformObjectInterface $invoice
     * @param LifecycleEventArgs                        $eventArgs
     */
    public function prePersist(InvoiceInterface $invoice, LifecycleEventArgs $eventArgs)
    {
        $this->invoiceAdapter->create($invoice);
    }

    /**
     * @param InvoiceInterface|PlatformObjectInterface $invoice
     * @param PreUpdateEventArgs                        $eventArgs
     */
    public function preUpdate(InvoiceInterface $invoice, PreUpdateEventArgs $eventArgs)
    {
        if (!$invoice->getPlatformId()) {
            $this->invoiceAdapter->create($invoice);
        } else {
            // $this->invoiceAdapter->update($invoice);
        }
    }

    /**
     * @param InvoiceInterface|PlatformObjectInterface $invoice
     * @param LifecycleEventArgs                        $eventArgs
     */
    public function preRemove(InvoiceInterface $invoice, LifecycleEventArgs $eventArgs)
    {
        if ($invoice->getPlatformId()) {
            try {
                // $this->invoiceAdapter->delete($invoice);
            } catch (NotFoundInPlatform $e) {
                // nothing to do, it's already deleted
            }
        }
    }
}