<?php

namespace Softspring\PlatformBundle\Stripe\Adapter;

use Psr\Log\LoggerInterface;
use Softspring\PaymentBundle\Model\InvoiceInterface;
use Softspring\PlatformBundle\Exception\PlatformException;
use Softspring\PlatformBundle\Adapter\InvoiceAdapterInterface;
use Softspring\PlatformBundle\Model\PlatformObjectInterface;
use Softspring\PlatformBundle\Stripe\Client\StripeClientProvider;
use Softspring\PlatformBundle\Stripe\Transformer\InvoiceTransformer;
use Stripe\Invoice;

class InvoiceAdapter implements InvoiceAdapterInterface
{
    /**
     * @var StripeClientProvider
     */
    protected $stripeClientProvider;

    /**
     * @var InvoiceTransformer
     */
    protected $invoiceTransformer;

    /**
     * @var ConceptAdapter
     */
    protected $conceptAdapter;

    /**
     * @var LoggerInterface|null
     */
    protected $logger;

    /**
     * InvoiceAdapter constructor.
     *
     * @param StripeClientProvider $stripeClientProvider
     * @param InvoiceTransformer   $invoiceTransformer
     * @param ConceptAdapter       $conceptAdapter
     * @param LoggerInterface|null $logger
     */
    public function __construct(StripeClientProvider $stripeClientProvider, InvoiceTransformer $invoiceTransformer, ConceptAdapter $conceptAdapter, ?LoggerInterface $logger)
    {
        $this->stripeClientProvider = $stripeClientProvider;
        $this->invoiceTransformer = $invoiceTransformer;
        $this->conceptAdapter = $conceptAdapter;
        $this->logger = $logger;
    }

    /**
     * @param InvoiceInterface|PlatformObjectInterface $invoice
     *
     * @return Invoice
     * @throws PlatformException
     */
    public function create(InvoiceInterface $invoice)
    {
        $data = $this->invoiceTransformer->transform($invoice, 'create');

        // draft invoice
        foreach ($invoice->getConcepts() as $concept) {
            $this->conceptAdapter->create($concept);
        }
        $invoiceStripe = $this->stripeClientProvider->getClient($invoice)->invoiceCreate($data['invoice']);
        $invoiceStripe->finalizeInvoice();
        $invoiceStripe->pay();

        $this->logger && $this->logger->info(sprintf('Stripe created invoice %s', $invoiceStripe->id));

        $this->invoiceTransformer->reverseTransform($invoiceStripe, $invoice);

        return $invoiceStripe;
    }

    /**
     * @param InvoiceInterface|PlatformObjectInterface $invoice
     *
     * @return Invoice
     * @throws PlatformException
     */
    public function get(InvoiceInterface $invoice)
    {
        $invoiceStripe = $this->stripeClientProvider->getClient($invoice)->invoiceRetrieve($invoice->getPlatformId());

        $this->invoiceTransformer->reverseTransform($invoiceStripe, $invoice);

        return $invoiceStripe;
    }
}