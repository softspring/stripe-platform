<?php

namespace Softspring\PlatformBundle\Stripe\Transformer;

use Softspring\CustomerBundle\Manager\CustomerManagerInterface;
use Softspring\CustomerBundle\Model\CustomerInterface;
use Softspring\PaymentBundle\Manager\PaymentManagerInterface;
use Softspring\PaymentBundle\Model\InvoiceInterface;
use Softspring\PaymentBundle\Model\PaymentInterface;
use Softspring\PlatformBundle\Model\PlatformObjectInterface;
use Softspring\PlatformBundle\Stripe\Adapter\PaymentAdapter;
use Softspring\PlatformBundle\Transformer\PlatformTransformerInterface;
use Stripe\Charge;
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

    /**
     * @var PaymentManagerInterface|null
     */
    protected $paymentManager;

    /**
     * @var CustomerManagerInterface
     */
    protected $customerManager;

    /**
     * @var PaymentAdapter|null
     */
    protected $paymentAdapter;

    /**
     * @var PaymentTransformer|null
     */
    protected $paymentTransformer;

    /**
     * InvoiceTransformer constructor.
     *
     * @param PaymentManagerInterface|null $paymentManager
     * @param CustomerManagerInterface     $customerManager
     * @param PaymentAdapter|null          $paymentAdapter
     * @param PaymentTransformer|null      $paymentTransformer
     */
    public function __construct(?PaymentManagerInterface $paymentManager, CustomerManagerInterface $customerManager, ?PaymentAdapter $paymentAdapter, ?PaymentTransformer $paymentTransformer)
    {
        $this->paymentManager = $paymentManager;
        $this->customerManager = $customerManager;
        $this->paymentAdapter = $paymentAdapter;
        $this->paymentTransformer = $paymentTransformer;
    }

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
                $data['invoice']['auto_advance'] = true;
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

        $invoice->setNumber($stripeInvoice->number);
        $invoice->setStatus(self::MAPPING_STATUSES[$stripeInvoice->status]);
        $invoice->setDate(\DateTime::createFromFormat('U', $stripeInvoice->created));
        $invoice->setTotal($stripeInvoice->total/100);
        $invoice->setCurrency(strtoupper($stripeInvoice->currency));

        /** @var CustomerInterface|null $customer */
        if ($customer = $this->customerManager->getRepository()->findOneBy(['platformId' => $stripeInvoice->customer])) {
            $invoice->setCustomer($customer);
        }

        $stripeChargeId = $stripeInvoice->charge;
        if ($this->paymentTransformer && $stripeChargeId) {
            // find payment
            $charge = $invoice->getPayments()->filter(function (PlatformObjectInterface $payment) use ($stripeChargeId) {
                return $payment->getPlatformId() == $stripeChargeId;
            })->first();

            if (! $charge instanceof PaymentInterface) {
                /** @var PlatformObjectInterface|PaymentInterface $charge */
                $charge = $this->paymentManager->createEntity();
                $charge->setType(PaymentInterface::TYPE_CHARGE);
                $charge->setPlatformId($stripeChargeId);
            }

            $this->paymentAdapter->get($charge); // sync with stripe
            $charge->setPlatformWebhooked(true); // do not sync again because of entity events
            $invoice->addPayment($charge);
        }

        return $invoice;
    }
}