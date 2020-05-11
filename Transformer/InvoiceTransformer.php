<?php

namespace Softspring\PlatformBundle\Stripe\Transformer;

use Softspring\PaymentBundle\Model\InvoiceInterface;
use Softspring\PlatformBundle\Transformer\PlatformTransformerInterface;
use Stripe\Invoice;

class InvoiceTransformer extends AbstractPlatformTransformer implements PlatformTransformerInterface
{
    /**
     * @param InvoiceInterface $invoice
     * @param string           $action
     *
     * @return array
     */
    public function transform($invoice, string $action = ''): array
    {

    }

    /**
     * @param Invoice               $stripeInvoice
     * @param InvoiceInterface|null $invoice
     * @param string                $action
     *
     * @return InvoiceInterface
     */
    public function reverseTransform($stripeInvoice, $invoice = null, string $action = ''): InvoiceInterface
    {

    }
}