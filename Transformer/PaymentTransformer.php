<?php

namespace Softspring\PlatformBundle\Stripe\Transformer;

use Softspring\CustomerBundle\Manager\CustomerManagerInterface;
use Softspring\CustomerBundle\Manager\SourceManagerInterface;
use Softspring\CustomerBundle\Model\CustomerInterface;
use Softspring\CustomerBundle\Model\SourceInterface;
use Softspring\PaymentBundle\Manager\InvoiceManagerInterface;
use Softspring\PaymentBundle\Model\PaymentInterface;
use Softspring\PaymentBundle\Model\PaymentRefersInvoiceInterface;
use Softspring\PlatformBundle\Exception\PlatformException;
use Softspring\PlatformBundle\Exception\TransformException;
use Softspring\PlatformBundle\Model\PlatformObjectInterface;
use Softspring\PlatformBundle\Transformer\PlatformTransformerInterface;
use Stripe\Charge;
use Stripe\Refund;

class PaymentTransformer extends AbstractPlatformTransformer implements PlatformTransformerInterface
{
    const MAPPING_STATUSES = [
        'pending' => PaymentInterface::STATUS_PENDING,
        'succeeded' => PaymentInterface::STATUS_DONE,
        'failed' => PaymentInterface::STATUS_FAILED,
    ];

    /**
     * @var CustomerManagerInterface
     */
    protected $customerManager;

    /**
     * @var SourceManagerInterface
     */
    protected $sourceManager;

    /**
     * @var InvoiceManagerInterface
     */
    protected $invoiceManager;

    /**
     * PaymentTransformer constructor.
     *
     * @param CustomerManagerInterface $customerManager
     * @param SourceManagerInterface   $sourceManager
     * @param InvoiceManagerInterface  $invoiceManager
     */
    public function __construct(CustomerManagerInterface $customerManager, SourceManagerInterface $sourceManager, InvoiceManagerInterface $invoiceManager)
    {
        $this->customerManager = $customerManager;
        $this->sourceManager = $sourceManager;
        $this->invoiceManager = $invoiceManager;
    }

    public function supports($payment): bool
    {
        return $payment instanceof PaymentInterface;
    }

    /**
     * @param PaymentInterface|PlatformObjectInterface $payment
     * @param string                                   $action
     *
     * @return array
     * @throws PlatformException
     */
    public function transform($payment, string $action = ''): array
    {
        $this->checkSupports($payment);

        $data = [];

        /** @var CustomerInterface|PlatformObjectInterface $customer */
        $customer = $payment->getCustomer();
        /** @var SourceInterface|PlatformObjectInterface $source */
        $source = $payment->getSource();

        switch ($payment->getType()) {
            case PaymentInterface::TYPE_CHARGE:
                $data['charge'] = [
                    'customer' => $customer->getPlatformId(),
                    'source' => $source->getPlatformId(),
                    'amount' => (int) round($payment->getAmount() * 100),
                    'currency' => strtolower($payment->getCurrency()),
                ];

                if ($payment->getConcept()) {
                    $data['charge']['description'] = $payment->getConcept();
                }

                break;

            case PaymentInterface::TYPE_REFUND:
                $data['refund'] = [
                    'charge' => $payment->getRefundPayment()->getPlatformId(),
                    'amount' => (int) round($payment->getAmount() * 100),
                ];
                break;

            default:
                throw new PlatformException('stripe', 'Bad payment type');
        }

        return $data;
    }

    /**
     * @param Charge|Refund                                 $stripePayment
     * @param PaymentInterface|PlatformObjectInterface|null $payment
     * @param string                                        $action
     *
     * @return PaymentInterface
     * @throws TransformException
     */
    public function reverseTransform($stripePayment, $payment = null, string $action = ''): PaymentInterface
    {
        $this->checkSupports($payment);
        $this->reverseTransformPlatformObject($payment, $stripePayment);

        if ($stripePayment instanceof Charge) {
            $payment->setStatus(self::MAPPING_STATUSES[$stripePayment->status]);
            $payment->setDate(\DateTime::createFromFormat('U', $stripePayment->created));
            $payment->setConcept($stripePayment->description);
            $payment->setType(PaymentInterface::TYPE_CHARGE);
            $payment->setCurrency(strtoupper($stripePayment->currency));
            $payment->setAmount($stripePayment->amount / 100);

            /** @var CustomerInterface|null $customer */
            if ($customer = $this->customerManager->getRepository()->findOneBy(['platformId' => $stripePayment->customer])) {
                $payment->setCustomer($customer);
            }

            /** @var SourceInterface|null $source */
            if ($source = $this->sourceManager->getRepository()->findOneBy(['platformId' => $stripePayment->source->id])) {
                $payment->setSource($source);
            }

            if ($payment instanceof PaymentRefersInvoiceInterface && $stripePayment->invoice && !$payment->getInvoice()) {
                $payment->setInvoice($this->invoiceManager->getRepository()->findOneBy(['platformId' => $stripePayment->invoice]));
            }
        }

        if ($stripePayment instanceof Refund) {
            $payment->setType(PaymentInterface::TYPE_REFUND);
            $payment->setStatus(self::MAPPING_STATUSES[$stripePayment->status]);
            $payment->setDate(\DateTime::createFromFormat('U', $stripePayment->created));

            // TODO FIND REFUND PAYMENT
            // $payment->setRefundPayment();
        }

        return $payment;
    }
}