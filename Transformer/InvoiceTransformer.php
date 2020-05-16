<?php

namespace Softspring\PlatformBundle\Stripe\Transformer;

use Softspring\PaymentBundle\Model\InvoiceInterface;
use Softspring\PlatformBundle\Model\PlatformObjectInterface;
use Softspring\PlatformBundle\Transformer\PlatformTransformerInterface;
use Stripe\Invoice;

class InvoiceTransformer extends AbstractPlatformTransformer implements PlatformTransformerInterface
{
    const MAPPING_STATUSES = [
        'draft' => InvoiceInterface::STATUS_DRAFT,
        'open' => InvoiceInterface::STATUS_PENDING,
        'paid' => InvoiceInterface::STATUS_PAID,
        'uncollectible' => InvoiceInterface::STATUS_UNPAID,
        'void' => InvoiceInterface::STATUS_CANCELED,
    ];

    public function supports($invoice): bool
    {
        return $invoice instanceof InvoiceInterface;
    }

    /**
     * @param InvoiceInterface $invoice
     * @param string           $action
     *
     * @return array
     */
    public function transform($invoice, string $action = ''): array
    {
        $data = [];

        if ($action == 'create') {
            $data['invoice'] = [
                'customer' => $invoice->getCustomer()->getPlatformId(),
            ];

            if ($invoice->getDate()->format('Ymd') > date('Ymd')) {
                // future invoice
                $data['invoice']['collection_method'] = 'send_invoice';
                // $data['invoice']['days_until_due'] = $invoice->getDate()->diff(new \DateTime('today'))->format('%a');
                $data['invoice']['due_date'] = max([$invoice->getDate()->format('U'), time()+10]);
            } else {
                // invoice now
                $data['invoice']['collection_method'] = 'charge_automatically';
            }

            // description
            // subscription
            // custom_fields
            // default_payment_method
            // default_source
            // default_tax_rates
            // footer
            // statement_descriptor
            // tax_percent
        }

        return $data;
    }

    /**
     * @param Invoice                                       $stripeInvoice
     * @param InvoiceInterface|PlatformObjectInterface|null $invoice
     * @param string                                        $action
     *
     * @return InvoiceInterface
     */
    public function reverseTransform($stripeInvoice, $invoice = null, string $action = ''): InvoiceInterface
    {
        if (null === $invoice) {
            // TODO CALL MANAGER TO CREATE ONE CONCEPT OBJECT
        }

        $this->checkSupports($invoice);
        $this->reverseTransformPlatformObject($invoice, $stripeInvoice);

        $invoice->setStatus(self::MAPPING_STATUSES[$stripeInvoice->status]);
        $invoice->setDate(\DateTime::createFromFormat('U', $stripeInvoice->created));
        $invoice->setTotal($stripeInvoice->total/100);

        return $invoice;
    }
}